import { NextRequest, NextResponse } from "next/server";
import { db, isDatabaseConfigurationError } from "@/lib/db";
import { maintenancePlans } from "@/lib/db/schema/maintenance-plans";
import { eq, and, or, desc, sql, gte, isNull } from "drizzle-orm";

/**
 * GET /api/maintenance-plans
 * List maintenance plans with filters
 */
export async function GET(request: NextRequest) {
  try {
    const searchParams = request.nextUrl.searchParams;
    const clientId = searchParams.get("clientId");
    const status = searchParams.get("status");
    const planType = searchParams.get("planType");
    const activeOnly = searchParams.get("activeOnly") === "true";

    const filters = [isNull(maintenancePlans.deletedAt)];
    if (clientId) {
      filters.push(eq(maintenancePlans.clientId, clientId));
    }
    if (status) {
      filters.push(eq(maintenancePlans.status, status as any));
    }
    if (planType) {
      filters.push(eq(maintenancePlans.planType, planType));
    }
    if (activeOnly) {
      filters.push(
        and(
          eq(maintenancePlans.status, "active"),
          or(sql`${maintenancePlans.endDate} IS NULL`, gte(maintenancePlans.endDate, new Date().toISOString().split("T")[0])),
        ) as any,
      );
    }

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
      .where(and(...filters) as any)
      .orderBy(desc(maintenancePlans.createdAt));

    return NextResponse.json({
      success: true,
      data: plans,
      count: plans.length,
    });
  } catch (error) {
    console.error("Error fetching maintenance plans:", error);
    const status = isDatabaseConfigurationError(error) ? 503 : 500;

    return NextResponse.json(
      {
        success: false,
        error: "Failed to fetch maintenance plans",
        message: error instanceof Error ? error.message : "Unknown error",
      },
      { status },
    );
  }
}

/**
 * POST /api/maintenance-plans
 * Create a new maintenance plan
 */
export async function POST(request: NextRequest) {
  try {
    const body = await request.json();

    // Validate required fields
    const requiredFields = [
      "clientId",
      "name",
      "startDate",
      "monthlyRate",
      "includedHours",
      "hourlyRateOverage",
      "createdBy",
    ];

    for (const field of requiredFields) {
      if (!body[field]) {
        return NextResponse.json(
          {
            success: false,
            error: `Missing required field: ${field}`,
          },
          { status: 400 },
        );
      }
    }

    // Calculate next billing date based on billing cycle
    const startDate = new Date(body.startDate);
    const nextBillingDate = calculateNextBillingDate(startDate, body.billingCycle || "monthly");

    // Create maintenance plan
    const [plan] = await db
      .insert(maintenancePlans)
      .values({
        clientId: body.clientId,
        createdBy: body.createdBy,
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

    return NextResponse.json(
      {
        success: true,
        data: plan,
        message: "Maintenance plan created successfully",
      },
      { status: 201 },
    );
  } catch (error) {
    console.error("Error creating maintenance plan:", error);
    const status = isDatabaseConfigurationError(error) ? 503 : 500;

    return NextResponse.json(
      {
        success: false,
        error: "Failed to create maintenance plan",
        message: error instanceof Error ? error.message : "Unknown error",
      },
      { status },
    );
  }
}

/**
 * Calculate next billing date based on billing cycle
 */
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
