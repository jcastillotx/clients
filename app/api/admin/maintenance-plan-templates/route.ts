import { NextRequest, NextResponse } from "next/server";
import { db, isDatabaseConfigurationError } from "@/lib/db";
import { maintenancePlanTemplates } from "@/lib/db/schema/maintenance-plans";
import { desc, eq } from "drizzle-orm";
import { createClient } from "@/lib/supabase/server";

/**
 * GET /api/admin/maintenance-plan-templates
 * List all maintenance plan templates (admin)
 */
export async function GET() {
  try {
    const templates = await db
      .select()
      .from(maintenancePlanTemplates)
      .orderBy(desc(maintenancePlanTemplates.createdAt));

    return NextResponse.json({
      success: true,
      data: templates,
      count: templates.length,
    });
  } catch (error) {
    console.error("Error fetching maintenance plan templates:", error);
    const status = isDatabaseConfigurationError(error) ? 503 : 500;
    return NextResponse.json(
      { success: false, error: "Failed to fetch templates", message: error instanceof Error ? error.message : "Unknown error" },
      { status },
    );
  }
}

/**
 * POST /api/admin/maintenance-plan-templates
 * Create a new maintenance plan template (admin only)
 */
export async function POST(request: NextRequest) {
  try {
    const supabase = await createClient();
    const { data: { user }, error: authError } = await supabase.auth.getUser();

    if (authError || !user) {
      return NextResponse.json({ success: false, error: "Authentication required" }, { status: 401 });
    }

    const body = await request.json();

    const requiredFields = ["name", "monthlyRate", "includedHours", "hourlyRateOverage"];
    for (const field of requiredFields) {
      if (!body[field] && body[field] !== 0) {
        return NextResponse.json({ success: false, error: `Missing required field: ${field}` }, { status: 400 });
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
        createdBy: user.id,
      })
      .returning();

    return NextResponse.json({ success: true, data: template, message: "Template created successfully" }, { status: 201 });
  } catch (error) {
    console.error("Error creating maintenance plan template:", error);
    const status = isDatabaseConfigurationError(error) ? 503 : 500;
    return NextResponse.json(
      { success: false, error: "Failed to create template", message: error instanceof Error ? error.message : "Unknown error" },
      { status },
    );
  }
}
