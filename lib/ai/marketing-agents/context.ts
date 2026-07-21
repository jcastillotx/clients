import { and, desc, eq, isNull } from "drizzle-orm";
import { db } from "@/lib/db";
import { parseClientBrandGuideContent } from "@/lib/brand/client-brand-guide";
import { brandGuides } from "@/lib/db/schema/brand-monitoring";
import { clients } from "@/lib/db/schema/clients";

export interface MarketingAgentClientContext {
  clientId: string;
  companyName: string;
  industry: string | null;
  website: string | null;
  marketingStrategy: {
    targetAudience?: string;
    channels?: string[];
    budget?: number;
  } | null;
  brandGuide: ReturnType<typeof parseClientBrandGuideContent>;
  brandGuideStatus: "draft" | "published" | "missing";
}

export async function loadMarketingAgentClientContext(
  clientId: string,
): Promise<MarketingAgentClientContext> {
  const [client] = await db
    .select({
      id: clients.id,
      companyName: clients.companyName,
      industry: clients.industry,
      website: clients.website,
      logoUrl: clients.logoUrl,
      marketingStrategy: clients.marketingStrategy,
    })
    .from(clients)
    .where(and(eq(clients.id, clientId), isNull(clients.deletedAt)))
    .limit(1);

  if (!client) {
    throw new Error("Client not found.");
  }

  const [guide] = await db
    .select({ status: brandGuides.status, meta: brandGuides.meta })
    .from(brandGuides)
    .where(eq(brandGuides.clientId, clientId))
    .orderBy(desc(brandGuides.updatedAt))
    .limit(1);

  return {
    clientId,
    companyName: client.companyName,
    industry: client.industry,
    website: client.website,
    marketingStrategy: client.marketingStrategy,
    brandGuide: parseClientBrandGuideContent(
      guide?.meta,
      client.companyName,
      client.logoUrl,
    ),
    brandGuideStatus: guide?.status ?? "missing",
  };
}

export function serializeClientContext(
  context: MarketingAgentClientContext,
): string {
  return JSON.stringify(
    {
      companyName: context.companyName,
      industry: context.industry,
      website: context.website,
      marketingStrategy: context.marketingStrategy,
      brandGuideStatus: context.brandGuideStatus,
      brandGuide: context.brandGuide,
    },
    null,
    2,
  );
}
