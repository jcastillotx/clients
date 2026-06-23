import { NextRequest } from "next/server";
import { db, isDatabaseConfigurationError } from "@/lib/db";
import { maintenancePlans, maintenancePlanTemplates } from "@/lib/db/schema/maintenance-plans";
import { eq } from "drizzle-orm";
import { requireAuthenticatedUser } from "@/lib/auth/route-guards";
import {
  apiError,
  apiInternalError,
  apiNotFound,
  apiSuccess,
} from "@/lib/api/response";

/**
 * POST /api/maintenance-plans/subscribe
 */
export async function POST(request: NextRequest) {
  try {
    const guard = await requireAuthenticatedUser(request);
    if ("error" in guard) {
      return guard.error;
    }

    const body = await request.json();

    if (!body.templateId) {
      return apiError(request, {
        status: 400,
        code: "BAD_REQUEST",
        message: "Missing required field: templateId",
      });
    }
    if (!body.clientId) {
      return apiError(request, {
        status: 400,
        code: "BAD_REQUEST",
        message: "Missing required field: clientId",
      });
    }

    const [template] = await db
      .select()
      .from(maintenancePlanTemplates)
      .where(eq(maintenancePlanTemplates.id, body.templateId))
      .limit(1);

    if (!template) {
      return apiNotFound(request, "Plan template not found");
    }

    if (!template.isActive) {
      return apiError(request, {
        status: 400,
        code: "BAD_REQUEST",
        message: "This plan is no longer available",
      });
    }

    const startDate = new Date();
    const nextBillingDate = new Date(startDate);
    switch (template.billingCycle) {
      case "monthly":
        nextBillingDate.setMonth(nextBillingDate.getMonth() + 1);
        break;
      case "quarterly":
        nextBillingDate.setMonth(nextBillingDate.getMonth() + 3);
        break;
      case "semi_annual":
        nextBillingDate.setMonth(nextBillingDate.getMonth() + 6);
        break;
      case "annual":
        nextBillingDate.setFullYear(nextBillingDate.getFullYear() + 1);
        break;
    }

    const [plan] = await db
      .insert(maintenancePlans)
      .values({
        clientId: body.clientId,
        createdBy: guard.user.id,
        templateId: template.id,
        name: template.name,
        description: template.description,
        planType: template.planType,
        status: "active",
        startDate: startDate.toISOString().split("T")[0],
        nextBillingDate: nextBillingDate.toISOString().split("T")[0],
        billingCycle: template.billingCycle,
        monthlyRate: template.monthlyRate,
        currency: template.currency,
        autoRenew: template.autoRenew,
        includedHours: template.includedHours,
        usedHours: "0",
        hourlyRateOverage: template.hourlyRateOverage,
        rolloverEnabled: template.rolloverEnabled,
        maxRolloverHours: template.maxRolloverHours,
        rolloverHoursAvailable: "0",
        servicesIncluded: template.servicesIncluded || [],
        overageBillingEnabled: template.overageBillingEnabled,
        overageApprovalRequired: template.overageApprovalRequired,
        overageNotificationThreshold: template.overageNotificationThreshold,
        renewalTermMonths: template.renewalTermMonths,
      })
      .returning();

    return apiSuccess(request, plan, {
      status: 201,
      extra: { message: "Successfully subscribed to maintenance plan" },
    });
  } catch (error) {
    console.error("Error subscribing to maintenance plan:", error);
    if (isDatabaseConfigurationError(error)) {
      return apiError(request, {
        status: 503,
        code: "SERVICE_UNAVAILABLE",
        message: "Failed to subscribe to plan",
      });
    }
    return apiInternalError(
      request,
      error instanceof Error ? error.message : "Failed to subscribe to plan",
    );
  }
}
