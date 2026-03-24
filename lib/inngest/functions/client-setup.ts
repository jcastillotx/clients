import { inngest } from "../client";
import { db } from "@/lib/db";
import { folders, clients } from "@/lib/db/schema";
import { eq, and, isNull } from "drizzle-orm";

export const setupClientFolders = inngest.createFunction(
  { id: "setup-client-folders" },
  { event: "client.created" },
  async ({ event, step }) => {
    const { clientId, companyName } = event.data;

    await step.run("create-brand-management-folder", async () => {
      // Check if folder already exists (to be safe)
      const existing = await db.query.folders.findFirst({
        where: and(
          eq(folders.clientId, clientId),
          eq(folders.type, "brand_management")
        ),
      });

      if (!existing) {
        await db.insert(folders).values({
          clientId,
          name: "Brand Management",
          description: `Brand assets for ${companyName}`,
          type: "brand_management",
        });
      }
    });

    return { success: true };
  }
);

export const backfillBrandFolders = inngest.createFunction(
  { id: "backfill-brand-folders" },
  { event: "client.backfill" },
  async ({ step }) => {
    const allClients = await step.run("get-all-clients", async () => {
      return db.select({ id: clients.id, companyName: clients.companyName })
        .from(clients)
        .where(isNull(clients.deletedAt));
    });

    const results = [];
    for (const client of allClients) {
      const result = await step.run(`backfill-${client.id}`, async () => {
        const existing = await db.query.folders.findFirst({
          where: and(
            eq(folders.clientId, client.id),
            eq(folders.type, "brand_management")
          ),
        });

        if (!existing) {
          await db.insert(folders).values({
            clientId: client.id,
            name: "Brand Management",
            description: `Brand assets for ${client.companyName}`,
            type: "brand_management",
          });
          return { clientId: client.id, status: "created" };
        }
        return { clientId: client.id, status: "exists" };
      });
      results.push(result);
    }

    return { processed: results.length, results };
  }
);
