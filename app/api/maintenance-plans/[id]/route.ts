import { NextRequest } from "next/server";
import { db, isDatabaseConfigurationError } from "@/lib/db";
import {
  maintenancePlans,
  maintenancePlanUsage,
} from "@/lib/db/schema/maintenance-plans";
import { eq, sql, desc } from "drizzle-orm";
import { createClient } from "@/lib/supabase/server";
import { canAccessClient, resolveRouteAccess } from "@/lib/auth/route-access";
import {
  apiError,
  apiForbidden,
  apiInternalError,
  apiNotFound,
  apiSuccess,
  apiUnauthorized,
} from "@/lib/api/response";

async function requirePlanAccess(request: Request, planId: string) {
  const supabase = await createClient();
  const {
    data: { user },
    error: authError,
  } = await supabase.auth.getUser();

  if (authError || !user) {
    return { error: apiUnauthorized(request) };
  }

  const access = await resolveRouteAccess(supabase, user);
  const [plan] = await db
    .select({ clientId: maintenancePlans.clientId })
    .from(maintenancePlans)
    .where(eq(maintenancePlans.id, planId))
    .limit(1);

  if (!plan) {
    return { error: apiNotFound(request, "Maintenance plan not found") };
  }

  if (!canAccessClient(access, plan.clientId)) {
    return { error: apiForbidden(request) };
  }

  return { user, access };
}

/**
 * GET /api/maintenance-plans/[id]
 * Get a specific maintenance plan with usage details
 */
export async function GET(
  request: NextRequest,
  { params }: { params: Promise<{ id: string }> },
) {
  const { id } = await params;
  try {
    const guard = await requirePlanAccess(request, id);
    if ("error" in guard) {
      return guard.error;
    }

    // Get plan details with aggregated usage
    const [plan] = await db
      .select({
        plan: maintenancePlans,
        client: {
          id: sql`clients.id`,
          companyName: sql`clients.company_name`,
          email: sql`clients.email`,
          phone: sql`clients.phone`,
        },
        creator: {
          id: sql`users.id`,
          name: sql`users.name`,
          email: sql`users.email`,
        },
        hoursRemaining: sql<number>`
          (${maintenancePlans.includedHours} + ${maintenancePlans.rolloverHoursAvailable} - ${maintenancePlans.usedHours})
        `.as("hours_remaining"),
        utilizationPercent: sql<number>`
          CASE
            WHEN (${maintenancePlans.includedHours} + ${maintenancePlans.rolloverHoursAvailable}) > 0
            THEN ROUND((${maintenancePlans.usedHours} / (${maintenancePlans.includedHours} + ${maintenancePlans.rolloverHoursAvailable})) * 100, 2)
            ELSE 0
          END
        `.as("utilization_percent"),
      })
      .from(maintenancePlans)
      .leftJoin(sql`clients`, sql`clients.id = ${maintenancePlans.clientId}`)
      .leftJoin(sql`users`, sql`users.id = ${maintenancePlans.createdBy}`)
      .where(eq(maintenancePlans.id, id))
      .limit(1);

    if (!plan) {
      return apiNotFound(request, "Maintenance plan not found");
    }

    // Get recent usage logs
    const usageLogs = await db
      .select({
        usage: maintenancePlanUsage,
        logger: {
          id: sql`users.id`,
          name: sql`users.name`,
          email: sql`users.email`,
        },
        approver: {
          id: sql`approver.id`,
          name: sql`approver.name`,
          email: sql`approver.email`,
        },
      })
      .from(maintenancePlanUsage)
      .leftJoin(sql`users`, sql`users.id = ${maintenancePlanUsage.loggedBy}`)
      .leftJoin(
        sql`users as approver`,
        sql`approver.id = ${maintenancePlanUsage.approvedBy}`,
      )
      .where(eq(maintenancePlanUsage.planId, id))
      .orderBy(desc(maintenancePlanUsage.loggedAt))
      .limit(50);

    return apiSuccess(request, { ...plan, usageLogs });
  } catch (error) {
    console.error("Error fetching maintenance plan:", error);
    if (isDatabaseConfigurationError(error)) {
      return apiError(request, {
        status: 503,
        code: "SERVICE_UNAVAILABLE",
        message: "Failed to fetch maintenance plan",
      });
    }
    return apiInternalError(
      request,
      error instanceof Error ? error.message : "Failed to fetch maintenance plan",
    );
  }
}

/**
 * PATCH /api/maintenance-plans/[id]
 * Update a maintenance plan
 */
export async function PATCH(
  request: NextRequest,
  { params }: { params: Promise<{ id: string }> },
) {
  const { id } = await params;
  try {
    const guard = await requirePlanAccess(request, id);
    if ("error" in guard) {
      return guard.error;
    }

    const body = await request.json();

    // Check if plan exists
    const [existingPlan] = await db
      .select()
      .from(maintenancePlans)
      .where(eq(maintenancePlans.id, id))
      .limit(1);

    if (!existingPlan) {
      return apiNotFound(request, "Maintenance plan not found");
    }

    // Update plan
    const [updatedPlan] = await db
      .update(maintenancePlans)
      .set({
        ...body,
        updatedAt: new Date(),
      })
      .where(eq(maintenancePlans.id, id))
      .returning();

    return apiSuccess(request, updatedPlan, {
      extra: { message: "Maintenance plan updated successfully" },
    });
  } catch (error) {
    console.error("Error updating maintenance plan:", error);
    if (isDatabaseConfigurationError(error)) {
      return apiError(request, {
        status: 503,
        code: "SERVICE_UNAVAILABLE",
        message: "Failed to update maintenance plan",
      });
    }
    return apiInternalError(
      request,
      error instanceof Error ? error.message : "Failed to update maintenance plan",
    );
  }
}

/**
 * DELETE /api/maintenance-plans/[id]
 * Soft delete a maintenance plan
 */
export async function DELETE(
  request: NextRequest,
  { params }: { params: Promise<{ id: string }> },
) {
  const { id } = await params;
  try {
    const guard = await requirePlanAccess(request, id);
    if ("error" in guard) {
      return guard.error;
    }

    // Check if plan exists
    const [existingPlan] = await db
      .select()
      .from(maintenancePlans)
      .where(eq(maintenancePlans.id, id))
      .limit(1);

    if (!existingPlan) {
      return apiNotFound(request, "Maintenance plan not found");
    }

    // Soft delete
    await db
      .update(maintenancePlans)
      .set({
        deletedAt: new Date(),
        status: "cancelled",
      })
      .where(eq(maintenancePlans.id, id));

    return apiSuccess(request, { deleted: true }, {
      extra: { message: "Maintenance plan deleted successfully" },
    });
  } catch (error) {
    console.error("Error deleting maintenance plan:", error);
    if (isDatabaseConfigurationError(error)) {
      return apiError(request, {
        status: 503,
        code: "SERVICE_UNAVAILABLE",
        message: "Failed to delete maintenance plan",
      });
    }
    return apiInternalError(
      request,
      error instanceof Error ? error.message : "Failed to delete maintenance plan",
    );
  }
}
