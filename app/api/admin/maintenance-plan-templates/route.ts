import { NextRequest } from "next/server";
import { db, isDatabaseConfigurationError } from "@/lib/db";
import { maintenancePlanTemplates } from "@/lib/db/schema/maintenance-plans";
import { desc } from "drizzle-orm";
import { requireAdminUser } from "@/lib/auth/route-guards";
import {
  apiError,
  apiInternalError,
  apiSuccess,
} from "@/lib/api/response";

/**
 * GET /api/admin/maintenance-plan-templates
 * List all maintenance plan templates (admin)
 */
export async function GET(request: NextRequest) {
  try {
    const guard = await requireAdminUser(request);
    if ("error" in guard) {
      return guard.error;
    }

    const templates = await db
      .select()
      .from(maintenancePlanTemplates)
      .orderBy(desc(maintenancePlanTemplates.createdAt));

    return apiSuccess(request, templates, {
      extra: { count: templates.length },
    });
  } catch (error) {
    console.error("Error fetching maintenance plan templates:", error);
    if (isDatabaseConfigurationError(error)) {
      return apiError(request, {
        status: 503,
        code: "SERVICE_UNAVAILABLE",
        message: "Failed to fetch templates",
      });
    }
    return apiInternalError(
      request,
      error instanceof Error ? error.message : "Failed to fetch templates",
    );
  }
}

/**
 * POST /api/admin/maintenance-plan-templates
 * Create a new maintenance plan template (admin only)
 */
export async function POST(request: NextRequest) {
  try {
    const guard = await requireAdminUser(request);
    if ("error" in guard) {
      return guard.error;
    }

    const body = await request.json();

    const requiredFields = ["name", "monthlyRate", "includedHours", "hourlyRateOverage"];
    for (const field of requiredFields) {
      if (!body[field] && body[field] !== 0) {
        return apiError(request, {
          status: 400,
          code: "BAD_REQUEST",
          message: `Missing required field: ${field}`,
        });
      }
    }

    const [template] = await db
      .insert(maintenancePlanTemplates)
      .values({
        name: body.name,
        description: body.description,
        planType: body.planType || "standard",
        isActive: body.isActive !== false,
        billingCycle: body.billingCycle || "monthly",
        monthlyRate: body.monthlyRate.toString(),
        currency: body.currency || "USD",
        includedHours: body.includedHours.toString(),
        hourlyRateOverage: body.hourlyRateOverage.toString(),
        autoRenew: body.autoRenew !== false,
        rolloverEnabled: body.rolloverEnabled || false,
        maxRolloverHours: body.maxRolloverHours?.toString() ?? null,
        overageBillingEnabled: body.overageBillingEnabled !== false,
        overageApprovalRequired: body.overageApprovalRequired || false,
        overageNotificationThreshold: body.overageNotificationThreshold?.toString() || "90",
        renewalTermMonths: body.renewalTermMonths || 12,
        servicesIncluded: body.servicesIncluded || [],
        metadata: body.metadata || {},
        createdBy: guard.user.id,
      })
      .returning();

    return apiSuccess(request, template, {
      status: 201,
      extra: { message: "Template created successfully" },
    });
  } catch (error) {
    console.error("Error creating maintenance plan template:", error);
    if (isDatabaseConfigurationError(error)) {
      return apiError(request, {
        status: 503,
        code: "SERVICE_UNAVAILABLE",
        message: "Failed to create template",
      });
    }
    return apiInternalError(
      request,
      error instanceof Error ? error.message : "Failed to create template",
    );
  }
}
