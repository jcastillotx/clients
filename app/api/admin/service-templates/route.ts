import { NextRequest, NextResponse } from "next/server";
import { db, isDatabaseConfigurationError } from "@/lib/db";
import { serviceTemplates } from "@/lib/db/schema/proposals";
import { desc } from "drizzle-orm";
import { createClient } from "@/lib/supabase/server";

/**
 * GET /api/admin/service-templates
 * List all service templates
 */
export async function GET() {
  try {
    const templates = await db
      .select()
      .from(serviceTemplates)
      .orderBy(desc(serviceTemplates.createdAt));

    return NextResponse.json({ success: true, data: templates, count: templates.length });
  } catch (error) {
    console.error("Error fetching service templates:", error);
    const status = isDatabaseConfigurationError(error) ? 503 : 500;
    return NextResponse.json(
      { success: false, error: "Failed to fetch templates", message: error instanceof Error ? error.message : "Unknown error" },
      { status },
    );
  }
}

/**
 * POST /api/admin/service-templates
 * Create a new service template (admin only)
 */
export async function POST(request: NextRequest) {
  try {
    const supabase = await createClient();
    const { data: { user }, error: authError } = await supabase.auth.getUser();

    if (authError || !user) {
      return NextResponse.json({ success: false, error: "Authentication required" }, { status: 401 });
    }

    const body = await request.json();

    if (!body.name?.trim()) {
      return NextResponse.json({ success: false, error: "Service name is required" }, { status: 400 });
    }
    if (!body.lineItems || body.lineItems.length === 0) {
      return NextResponse.json({ success: false, error: "At least one line item is required" }, { status: 400 });
    }

    const totalAmount = body.lineItems.reduce(
      (sum: number, item: any) => sum + (item.quantity || 1) * (item.unitPrice || 0),
      0,
    );

    const [template] = await db
      .insert(serviceTemplates)
      .values({
        name: body.name,
        description: body.description || null,
        category: body.category || null,
        isActive: body.isActive !== false,
        currency: body.currency || "USD",
        lineItems: body.lineItems.map((item: any) => ({
          id: crypto.randomUUID(),
          description: item.description,
          quantity: item.quantity || 1,
          unitPrice: item.unitPrice || 0,
          amount: (item.quantity || 1) * (item.unitPrice || 0),
          category: item.category,
        })),
        totalAmount: totalAmount.toString(),
        terms: body.terms || null,
        metadata: body.metadata || {},
        createdBy: user.id,
      })
      .returning();

    return NextResponse.json({ success: true, data: template, message: "Service template created" }, { status: 201 });
  } catch (error) {
    console.error("Error creating service template:", error);
    const status = isDatabaseConfigurationError(error) ? 503 : 500;
    return NextResponse.json(
      { success: false, error: "Failed to create template", message: error instanceof Error ? error.message : "Unknown error" },
      { status },
    );
  }
}
