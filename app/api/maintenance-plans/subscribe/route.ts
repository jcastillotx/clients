import { NextRequest, NextResponse } from "next/server";
import { db, isDatabaseConfigurationError } from "@/lib/db";
import { maintenancePlans, maintenancePlanTemplates } from "@/lib/db/schema/maintenance-plans";
import { eq } from "drizzle-orm";
import { createClient } from "@/lib/supabase/server";

/**
 * POST /api/maintenance-plans/subscribe
 * Client subscribes to a maintenance plan template
 */
export async function POST(request: NextRequest) {
  try {
    const supabase = await createClient();
    const { data: { user }, error: authError } = await supabase.auth.getUser();

    if (authError || !user) {
      return NextResponse.json({ success: false, error: "Authentication required" }, { status: 401 });
    }

    const body = await request.json();

    if (!body.templateId) {
      return NextResponse.json({ success: false, error: "Missing required field: templateId" }, { status: 400 });
    }
    if (!body.clientId) {
      return NextResponse.json({ success: false, error: "Missing required field: clientId" }, { status: 400 });
    }

    // Fetch the template
    const [template] = await db
      .select()
      .from(maintenancePlanTemplates)
      .where(eq(maintenancePlanTemplates.id, body.templateId))
      .limit(1);

    if (!template) {
      return NextResponse.json({ success: false, error: "Plan template not found" }, { status: 404 });
    }

    if (!template.isActive) {
      return NextResponse.json({ success: false, error: "This plan is no longer available" }, { status: 400 });
    }

    // Calculate next billing date
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

    // Create the maintenance plan from the template
    const [plan] = await db
      .insert(maintenancePlans)
      .values({
        clientId: body.clientId,
        createdBy: user.id,
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

    return NextResponse.json(
      { success: true, data: plan, message: "Successfully subscribed to maintenance plan" },
      { status: 201 },
    );
  } catch (error) {
    console.error("Error subscribing to maintenance plan:", error);
    const status = isDatabaseConfigurationError(error) ? 503 : 500;
    return NextResponse.json(
      { success: false, error: "Failed to subscribe to plan", message: error instanceof Error ? error.message : "Unknown error" },
      { status },
    );
  }
}
