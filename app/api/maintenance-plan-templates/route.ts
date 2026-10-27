import { desc, eq } from "drizzle-orm";

import { apiInternalError, apiSuccess } from "@/lib/api/response";
import { requireAuthenticatedUser } from "@/lib/auth/route-guards";
import { db } from "@/lib/db";
import { maintenancePlanTemplates } from "@/lib/db/schema/maintenance-plans";

export async function GET(request: Request) {
  try {
    const guard = await requireAuthenticatedUser(request);
    if ("error" in guard) return guard.error;

    const templates = await db.select({
      id: maintenancePlanTemplates.id,
      name: maintenancePlanTemplates.name,
      description: maintenancePlanTemplates.description,
      planType: maintenancePlanTemplates.planType,
      billingCycle: maintenancePlanTemplates.billingCycle,
      monthlyRate: maintenancePlanTemplates.monthlyRate,
      currency: maintenancePlanTemplates.currency,
      includedHours: maintenancePlanTemplates.includedHours,
      hourlyRateOverage: maintenancePlanTemplates.hourlyRateOverage,
      rolloverEnabled: maintenancePlanTemplates.rolloverEnabled,
      isActive: maintenancePlanTemplates.isActive,
      servicesIncluded: maintenancePlanTemplates.servicesIncluded,
    }).from(maintenancePlanTemplates)
      .where(eq(maintenancePlanTemplates.isActive, true))
      .orderBy(desc(maintenancePlanTemplates.createdAt));
    return apiSuccess(request, templates);
  } catch (error) {
    console.error("Error loading client catalog:", error);
    return apiInternalError(request, "Unable to load available services");
  }
}
