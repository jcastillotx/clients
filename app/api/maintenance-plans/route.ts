import { NextRequest } from "next/server";
import { db, isDatabaseConfigurationError } from "@/lib/db";
import { maintenancePlans, maintenancePlanStatusEnum, type MaintenancePlanStatus } from "@/lib/db/schema/maintenance-plans";
import { eq, and, desc, sql, isNull, type SQL } from "drizzle-orm";
import { requireAuthenticatedUser } from "@/lib/auth/route-guards";
import {
  apiError,
  apiInternalError,
  apiSuccess,
} from "@/lib/api/response";

function isMaintenancePlanStatus(value: string): value is MaintenancePlanStatus {
  return (maintenancePlanStatusEnum as readonly string[]).includes(value);
}

/**
 * GET /api/maintenance-plans
 */
export async function GET(request: NextRequest) {
  try {
    const searchParams = request.nextUrl.searchParams;
    const clientId = searchParams.get("clientId");
    const status = searchParams.get("status");
    const planType = searchParams.get("planType");
    const activeOnly = searchParams.get("activeOnly") === "true";

    const filters: SQL<unknown>[] = [isNull(maintenancePlans.deletedAt)];
    if (clientId) {
      filters.push(eq(maintenancePlans.clientId, clientId));
    }
    if (status && isMaintenancePlanStatus(status)) {
      filters.push(eq(maintenancePlans.status, status));
    }
    if (planType) {
      filters.push(eq(maintenancePlans.planType, planType));
    }
    if (activeOnly) {
      const today = new Date().toISOString().split("T")[0];
      filters.push(
        sql`(${maintenancePlans.status} = 'active' AND (${maintenancePlans.endDate} IS NULL OR ${maintenancePlans.endDate} >= ${today}))`,
      );
    }

    const whereClause = filters.length > 1 ? and(...filters) : filters[0];

    const plans = await db
      .select({
        plan: maintenancePlans,
        client: {
          id: sql`clients.id`,
          companyName: sql`clients.company_name`,
          email: sql`clients.email`,
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
      .where(whereClause)
      .orderBy(desc(maintenancePlans.createdAt));

    return apiSuccess(request, plans, { extra: { count: plans.length } });
  } catch (error) {
    console.error("Error fetching maintenance plans:", error);
    if (isDatabaseConfigurationError(error)) {
      return apiError(request, {
        status: 503,
        code: "SERVICE_UNAVAILABLE",
        message: "Failed to fetch maintenance plans",
      });
    }
    return apiInternalError(
      request,
      error instanceof Error ? error.message : "Failed to fetch maintenance plans",
    );
  }
}

/**
 * POST /api/maintenance-plans
 */
export async function POST(request: NextRequest) {
  try {
    const guard = await requireAuthenticatedUser(request);
    if ("error" in guard) {
      return guard.error;
    }

    const body = await request.json();

    const requiredFields = ["clientId", "name", "startDate", "monthlyRate", "includedHours", "hourlyRateOverage"];

    for (const field of requiredFields) {
      if (!body[field]) {
        return apiError(request, {
          status: 400,
          code: "BAD_REQUEST",
          message: `Missing required field: ${field}`,
        });
      }
    }

    const startDate = new Date(body.startDate);
    const nextBillingDate = calculateNextBillingDate(startDate, body.billingCycle || "monthly");

    const [plan] = await db
      .insert(maintenancePlans)
      .values({
        clientId: body.clientId,
        createdBy: guard.user.id,
        name: body.name,
        description: body.description,
        planType: body.planType || "standard",
        status: body.status || "active",
        startDate: body.startDate,
        endDate: body.endDate,
        nextBillingDate: nextBillingDate.toISOString().split("T")[0],
        billingCycle: body.billingCycle || "monthly",
        monthlyRate: body.monthlyRate,
        currency: body.currency || "USD",
        autoRenew: body.autoRenew !== false,
        includedHours: body.includedHours,
        usedHours: "0",
        hourlyRateOverage: body.hourlyRateOverage,
        rolloverEnabled: body.rolloverEnabled || false,
        maxRolloverHours: body.maxRolloverHours,
        rolloverHoursAvailable: "0",
        servicesIncluded: body.servicesIncluded || [],
        coveredServices: body.coveredServices || [],
        overageBillingEnabled: body.overageBillingEnabled !== false,
        overageApprovalRequired: body.overageApprovalRequired || false,
        overageNotificationThreshold: body.overageNotificationThreshold || "90",
        autoRenewNotificationDays: body.autoRenewNotificationDays || 30,
        renewalTermMonths: body.renewalTermMonths || 12,
        metadata: body.metadata || {},
      })
      .returning();

    return apiSuccess(request, plan, {
      status: 201,
      extra: { message: "Maintenance plan created successfully" },
    });
  } catch (error) {
    console.error("Error creating maintenance plan:", error);
    if (isDatabaseConfigurationError(error)) {
      return apiError(request, {
        status: 503,
        code: "SERVICE_UNAVAILABLE",
        message: "Failed to create maintenance plan",
      });
    }
    return apiInternalError(
      request,
      error instanceof Error ? error.message : "Failed to create maintenance plan",
    );
  }
}

function calculateNextBillingDate(startDate: Date, billingCycle: string): Date {
  const nextDate = new Date(startDate);

  switch (billingCycle) {
    case "monthly":
      nextDate.setMonth(nextDate.getMonth() + 1);
      break;
    case "quarterly":
      nextDate.setMonth(nextDate.getMonth() + 3);
      break;
    case "semi_annual":
      nextDate.setMonth(nextDate.getMonth() + 6);
      break;
    case "annual":
      nextDate.setFullYear(nextDate.getFullYear() + 1);
      break;
    default:
      nextDate.setMonth(nextDate.getMonth() + 1);
  }

  return nextDate;
}
