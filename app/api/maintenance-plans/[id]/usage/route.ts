import { NextRequest } from "next/server";
import { db, isDatabaseConfigurationError } from "@/lib/db";
import {
  maintenancePlans,
  maintenancePlanUsage,
} from "@/lib/db/schema/maintenance-plans";
import { eq, sql } from "drizzle-orm";
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

type DbTransaction = Parameters<typeof db.transaction>[0] extends (
  tx: infer T,
) => Promise<unknown>
  ? T
  : never;

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
    .select()
    .from(maintenancePlans)
    .where(eq(maintenancePlans.id, planId))
    .limit(1);

  if (!plan) {
    return { error: apiNotFound(request, "Maintenance plan not found") };
  }

  if (!canAccessClient(access, plan.clientId)) {
    return { error: apiForbidden(request) };
  }

  return { user, access, plan };
}

/**
 * POST /api/maintenance-plans/[id]/usage
 * Log hours against a maintenance plan
 */
export async function POST(
  request: NextRequest,
  { params }: { params: Promise<{ id: string }> },
) {
  const { id: planId } = await params;
  try {
    const guard = await requirePlanAccess(request, planId);
    if ("error" in guard) {
      return guard.error;
    }

    const { user } = guard;
    const body = await request.json();

    // Validate required fields
    const requiredFields = ["hoursUsed", "description"];
    for (const field of requiredFields) {
      if (!body[field]) {
        return apiError(request, {
          status: 400,
          code: "BAD_REQUEST",
          message: `Missing required field: ${field}`,
        });
      }
    }

    const { plan } = guard;

    // Check if plan is active
    if (plan.status !== "active") {
      return apiError(request, {
        status: 400,
        code: "BAD_REQUEST",
        message: "Cannot log hours to an inactive maintenance plan",
      });
    }

    // Calculate hours and check for overage
    const hoursUsed = parseFloat(body.hoursUsed);
    const currentUsedHours = parseFloat(plan.usedHours);
    const includedHours = parseFloat(plan.includedHours);
    const rolloverHours = parseFloat(plan.rolloverHoursAvailable);
    const totalAvailable = includedHours + rolloverHours;

    const newUsedHours = currentUsedHours + hoursUsed;
    const isOverage = newUsedHours > totalAvailable;
    const overageHours = isOverage ? newUsedHours - totalAvailable : 0;
    const overageAmount = overageHours * parseFloat(plan.hourlyRateOverage);

    // Check if overage requires approval
    const requiresApproval = isOverage && plan.overageApprovalRequired;
    const approvalStatus = requiresApproval ? "pending" : "approved";

    // Check if we should send notification
    const utilizationPercent = (newUsedHours / totalAvailable) * 100;
    const shouldNotify =
      utilizationPercent >= parseFloat(plan.overageNotificationThreshold) &&
      (currentUsedHours / totalAvailable) * 100 <
        parseFloat(plan.overageNotificationThreshold);

    // Use transaction to update both plan and create usage log
    await db.transaction(async (tx: DbTransaction) => {
      // Create usage log
      await tx.insert(maintenancePlanUsage).values({
        planId,
        supportTicketId: body.supportTicketId,
        loggedBy: user.id,
        hoursUsed: body.hoursUsed,
        description: body.description,
        taskCategory: body.taskCategory,
        isOverage,
        overageAmount: isOverage ? overageAmount.toString() : null,
        isBillable: body.isBillable !== false,
        billedAmount: body.billedAmount,
        invoiceId: body.invoiceId,
        loggedAt: new Date(),
        workPerformedAt: body.workPerformedAt
          ? new Date(body.workPerformedAt)
          : null,
        requiresApproval,
        approvalStatus,
        metadata: body.metadata || {},
      });

      // Update plan's used hours (only if approved or no approval required)
      if (!requiresApproval) {
        await tx
          .update(maintenancePlans)
          .set({
            usedHours: newUsedHours.toString(),
            updatedAt: new Date(),
          })
          .where(eq(maintenancePlans.id, planId));
      }
    });

    // Send notification if threshold exceeded (would be done via queue in production)
    if (shouldNotify) {
      console.log(
        `NOTIFICATION: Plan ${planId} has exceeded ${plan.overageNotificationThreshold}% usage threshold`,
      );
      // TODO: Send email notification
    }

    const resultData = {
      hoursUsed,
      newUsedHours,
      totalAvailable,
      hoursRemaining: totalAvailable - newUsedHours,
      isOverage,
      overageHours,
      overageAmount,
      requiresApproval,
      utilizationPercent: Math.round(utilizationPercent * 100) / 100,
    };

    return apiSuccess(request, resultData, {
      status: 201,
      extra: {
        message: requiresApproval
          ? "Hours logged successfully. Awaiting approval for overage."
          : "Hours logged successfully",
        data: resultData,
      },
    });
  } catch (error) {
    console.error("Error logging maintenance plan usage:", error);
    if (isDatabaseConfigurationError(error)) {
      return apiError(request, {
        status: 503,
        code: "SERVICE_UNAVAILABLE",
        message: "Failed to log hours",
      });
    }
    return apiInternalError(
      request,
      error instanceof Error ? error.message : "Failed to log hours",
    );
  }
}

/**
 * GET /api/maintenance-plans/[id]/usage
 * Get usage history for a maintenance plan
 */
export async function GET(
  request: NextRequest,
  { params }: { params: Promise<{ id: string }> },
) {
  const { id: planId } = await params;
  try {
    const guard = await requirePlanAccess(request, planId);
    if ("error" in guard) {
      return guard.error;
    }

    const searchParams = request.nextUrl.searchParams;
    const limit = parseInt(searchParams.get("limit") || "50");
    const offset = parseInt(searchParams.get("offset") || "0");

    // Get usage logs
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
        supportTicket: {
          id: sql`support_tickets.id`,
          ticketNumber: sql`support_tickets.ticket_number`,
          subject: sql`support_tickets.subject`,
        },
      })
      .from(maintenancePlanUsage)
      .leftJoin(sql`users`, sql`users.id = ${maintenancePlanUsage.loggedBy}`)
      .leftJoin(
        sql`users as approver`,
        sql`approver.id = ${maintenancePlanUsage.approvedBy}`,
      )
      .leftJoin(
        sql`support_tickets`,
        sql`support_tickets.id = ${maintenancePlanUsage.supportTicketId}`,
      )
      .where(eq(maintenancePlanUsage.planId, planId))
      .orderBy(sql`${maintenancePlanUsage.loggedAt} DESC`)
      .limit(limit)
      .offset(offset);

    // Get total count
    const [{ count }] = await db
      .select({ count: sql<number>`count(*)` })
      .from(maintenancePlanUsage)
      .where(eq(maintenancePlanUsage.planId, planId));

    // Get usage summary
    const [summary] = await db
      .select({
        totalHoursLogged: sql<number>`COALESCE(SUM(CAST(${maintenancePlanUsage.hoursUsed} AS DECIMAL)), 0)`,
        totalOverageHours: sql<number>`COALESCE(SUM(CASE WHEN ${maintenancePlanUsage.isOverage} THEN CAST(${maintenancePlanUsage.hoursUsed} AS DECIMAL) ELSE 0 END), 0)`,
        totalOverageAmount: sql<number>`COALESCE(SUM(CASE WHEN ${maintenancePlanUsage.overageAmount} IS NOT NULL THEN CAST(${maintenancePlanUsage.overageAmount} AS DECIMAL) ELSE 0 END), 0)`,
        pendingApprovalCount: sql<number>`COUNT(CASE WHEN ${maintenancePlanUsage.approvalStatus} = 'pending' THEN 1 END)`,
      })
      .from(maintenancePlanUsage)
      .where(eq(maintenancePlanUsage.planId, planId));

    const payload = {
      usageLogs,
      summary,
      pagination: {
        limit,
        offset,
        total: count,
        hasMore: offset + limit < count,
      },
    };

    return apiSuccess(request, payload);
  } catch (error) {
    console.error("Error fetching maintenance plan usage:", error);
    if (isDatabaseConfigurationError(error)) {
      return apiError(request, {
        status: 503,
        code: "SERVICE_UNAVAILABLE",
        message: "Failed to fetch usage history",
      });
    }
    return apiInternalError(
      request,
      error instanceof Error ? error.message : "Failed to fetch usage history",
    );
  }
}
