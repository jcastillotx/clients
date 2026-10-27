import { desc, eq } from "drizzle-orm";

import { apiInternalError, apiSuccess } from "@/lib/api/response";
import { requireAuthenticatedUser } from "@/lib/auth/route-guards";
import { db } from "@/lib/db";
import { serviceTemplates } from "@/lib/db/schema/proposals";

export async function GET(request: Request) {
  try {
    const guard = await requireAuthenticatedUser(request);
    if ("error" in guard) return guard.error;

    const templates = await db.select({
      id: serviceTemplates.id,
      name: serviceTemplates.name,
      description: serviceTemplates.description,
      category: serviceTemplates.category,
      currency: serviceTemplates.currency,
      totalAmount: serviceTemplates.totalAmount,
      isActive: serviceTemplates.isActive,
      lineItems: serviceTemplates.lineItems,
      metadata: serviceTemplates.metadata,
    }).from(serviceTemplates)
      .where(eq(serviceTemplates.isActive, true))
      .orderBy(desc(serviceTemplates.createdAt));
    return apiSuccess(request, templates.map((template) => ({
      ...template,
      metadata: template.metadata ? {
        features: template.metadata.features,
        deliverables: template.metadata.deliverables,
        estimatedTimeline: template.metadata.estimatedTimeline,
      } : null,
    })));
  } catch (error) {
    console.error("Error loading client catalog:", error);
    return apiInternalError(request, "Unable to load available services");
  }
}
