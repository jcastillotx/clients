import { NextRequest, NextResponse } from "next/server";
import { db, isDatabaseConfigurationError } from "@/lib/db";
import { maintenancePlanTemplates } from "@/lib/db/schema/maintenance-plans";
import { eq } from "drizzle-orm";

/**
 * GET /api/admin/maintenance-plan-templates/[id]
 */
export async function GET(_request: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;
  try {
    const [template] = await db
      .select()
      .from(maintenancePlanTemplates)
      .where(eq(maintenancePlanTemplates.id, id))
      .limit(1);

    if (!template) {
      return NextResponse.json({ success: false, error: "Template not found" }, { status: 404 });
    }

    return NextResponse.json({ success: true, data: template });
  } catch (error) {
    console.error("Error fetching template:", error);
    const status = isDatabaseConfigurationError(error) ? 503 : 500;
    return NextResponse.json(
      { success: false, error: "Failed to fetch template", message: error instanceof Error ? error.message : "Unknown error" },
      { status },
    );
  }
}

/**
 * PATCH /api/admin/maintenance-plan-templates/[id]
 */
export async function PATCH(request: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;
  try {
    const body = await request.json();

    const [existing] = await db
      .select()
      .from(maintenancePlanTemplates)
      .where(eq(maintenancePlanTemplates.id, id))
      .limit(1);

    if (!existing) {
      return NextResponse.json({ success: false, error: "Template not found" }, { status: 404 });
    }

    const [updated] = await db
      .update(maintenancePlanTemplates)
      .set({ ...body, updatedAt: new Date() })
      .where(eq(maintenancePlanTemplates.id, id))
      .returning();

    return NextResponse.json({ success: true, data: updated, message: "Template updated successfully" });
  } catch (error) {
    console.error("Error updating template:", error);
    const status = isDatabaseConfigurationError(error) ? 503 : 500;
    return NextResponse.json(
      { success: false, error: "Failed to update template", message: error instanceof Error ? error.message : "Unknown error" },
      { status },
    );
  }
}

/**
 * DELETE /api/admin/maintenance-plan-templates/[id]
 */
export async function DELETE(_request: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;
  try {
    const [existing] = await db
      .select()
      .from(maintenancePlanTemplates)
      .where(eq(maintenancePlanTemplates.id, id))
      .limit(1);

    if (!existing) {
      return NextResponse.json({ success: false, error: "Template not found" }, { status: 404 });
    }

    await db.delete(maintenancePlanTemplates).where(eq(maintenancePlanTemplates.id, id));

    return NextResponse.json({ success: true, message: "Template deleted successfully" });
  } catch (error) {
    console.error("Error deleting template:", error);
    const status = isDatabaseConfigurationError(error) ? 503 : 500;
    return NextResponse.json(
      { success: false, error: "Failed to delete template", message: error instanceof Error ? error.message : "Unknown error" },
      { status },
    );
  }
}
