import { and, desc, eq } from "drizzle-orm";
import { db } from "@/lib/db";
import { whiteLabelConfigs } from "@/lib/db/schema/additional-features";

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
