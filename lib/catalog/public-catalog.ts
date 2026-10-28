import { createHash } from "node:crypto";
import { eq } from "drizzle-orm";
import { db } from "@/lib/db";
import { serviceTemplates } from "@/lib/db/schema/proposals";
import { publicPackageSchema } from "./schema";

type CatalogRow = {
  id: string;
  name: string;
  description: string | null;
  category: string | null;
  metadata: unknown;
};
/** Explicit projection: pricing, terms, notes, tenant records and credentials never leave this boundary. */
export function projectPublicCatalog(rows: CatalogRow[]) {
  const services = rows.map((row) => ({
    id: row.id,
    name: row.name,
    description: row.description,
    category: row.category,
  }));
  const packages: Array<{
    id: string;
    name: string;
    group: string;
    fit: string;
    includes: string[];
    service: string;
    serviceId: string;
  }> = [];
  const ids = new Set<string>();
  for (const row of rows) {
    const metadata =
      row.metadata && typeof row.metadata === "object"
        ? (row.metadata as { publicPackages?: unknown })
        : {};
    if (!Array.isArray(metadata.publicPackages)) continue;
    for (const item of metadata.publicPackages) {
      const result = publicPackageSchema.safeParse(item);
      if (!result.success || !result.data.published) continue;
      const { published, ...value } = result.data;
      void published;
      if (ids.has(value.id))
        throw new Error("Duplicate public package identifier");
      ids.add(value.id);
      packages.push({ ...value, service: row.name, serviceId: row.id });
    }
  }
  packages.sort((a, b) => a.id.localeCompare(b.id));
  services.sort((a, b) => a.id.localeCompare(b.id));
  const data = { schemaVersion: 1 as const, services, packages };
  return {
    ...data,
    version: createHash("sha256").update(JSON.stringify(data)).digest("hex"),
  };
}
export async function getPublicServiceCatalog() {
  const rows = await db
    .select({
      id: serviceTemplates.id,
      name: serviceTemplates.name,
      description: serviceTemplates.description,
      category: serviceTemplates.category,
      metadata: serviceTemplates.metadata,
    })
    .from(serviceTemplates)
    .where(eq(serviceTemplates.isActive, true));
  return projectPublicCatalog(rows);
}
