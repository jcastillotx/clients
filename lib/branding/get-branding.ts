import { and, desc, eq, inArray } from "drizzle-orm";
import { db } from "@/lib/db";
import { whiteLabelConfigs } from "@/lib/db/schema/additional-features";
import { clients } from "@/lib/db/schema/clients";

export interface PortalBranding {
  logoUrl: string | null;
}

export async function getPortalBranding(hostname?: string): Promise<PortalBranding> {
  try {
    if (hostname) {
      const byDomain = await db
        .select({ logoUrl: whiteLabelConfigs.logoUrl })
        .from(whiteLabelConfigs)
        .where(and(eq(whiteLabelConfigs.domain, hostname), eq(whiteLabelConfigs.isActive, true)))
        .limit(1);

      if (byDomain[0]) {
        return { logoUrl: byDomain[0].logoUrl };
      }
    }

    const parentClientIds = (process.env.PARENT_CLIENT_IDS ?? "")
      .split(",")
      .map((value) => value.trim())
      .filter(Boolean);

    if (parentClientIds.length > 0) {
      const byParentClient = await db
        .select({ logoUrl: whiteLabelConfigs.logoUrl })
        .from(whiteLabelConfigs)
        .where(and(eq(whiteLabelConfigs.isActive, true), inArray(whiteLabelConfigs.clientId, parentClientIds)))
        .orderBy(desc(whiteLabelConfigs.updatedAt))
        .limit(1);

      if (byParentClient[0]) {
        return { logoUrl: byParentClient[0].logoUrl };
      }
    }

    const parentCompanyNames = (process.env.PARENT_COMPANY_NAMES ?? "Kre8ivTech,Kre8iv Designs")
      .split(",")
      .map((value) => value.trim())
      .filter(Boolean);

    if (parentCompanyNames.length > 0) {
      const parentClients = await db
        .select({ id: clients.id })
        .from(clients)
        .where(inArray(clients.companyName, parentCompanyNames))
        .limit(5);
      const parentClientIdsByName = parentClients.map((row) => row.id);

      if (parentClientIdsByName.length > 0) {
        const byParentName = await db
          .select({ logoUrl: whiteLabelConfigs.logoUrl })
          .from(whiteLabelConfigs)
          .where(and(eq(whiteLabelConfigs.isActive, true), inArray(whiteLabelConfigs.clientId, parentClientIdsByName)))
          .orderBy(desc(whiteLabelConfigs.updatedAt))
          .limit(1);

        if (byParentName[0]) {
          return { logoUrl: byParentName[0].logoUrl };
        }
      }
    }

    const fallback = await db
      .select({ logoUrl: whiteLabelConfigs.logoUrl })
      .from(whiteLabelConfigs)
      .where(eq(whiteLabelConfigs.isActive, true))
      .orderBy(desc(whiteLabelConfigs.updatedAt))
      .limit(1);

    return { logoUrl: fallback[0]?.logoUrl ?? null };
  } catch {
    return { logoUrl: null };
  }
}
