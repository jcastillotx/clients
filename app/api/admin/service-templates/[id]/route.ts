import { NextRequest, NextResponse } from "next/server";
import { db, isDatabaseConfigurationError } from "@/lib/db";
import { serviceTemplates } from "@/lib/db/schema/proposals";
import { eq } from "drizzle-orm";

/**
 * GET /api/admin/service-templates/[id]
 */
export async function GET(_request: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;
  try {
    const [template] = await db.select().from(serviceTemplates).where(eq(serviceTemplates.id, id)).limit(1);

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
 * PATCH /api/admin/service-templates/[id]
 */
export async function PATCH(request: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;
  try {
    const body = await request.json();

    const [existing] = await db.select().from(serviceTemplates).where(eq(serviceTemplates.id, id)).limit(1);
    if (!existing) {
      return NextResponse.json({ success: false, error: "Template not found" }, { status: 404 });
    }

    // Recalculate total if line items changed
    const updateData: Record<string, any> = { ...body, updatedAt: new Date() };
    if (body.lineItems) {
      updateData.totalAmount = body.lineItems
        .reduce((sum: number, item: any) => sum + (item.quantity || 1) * (item.unitPrice || 0), 0)
        .toString();
    }

    const [updated] = await db
      .update(serviceTemplates)
      .set(updateData)
      .where(eq(serviceTemplates.id, id))
      .returning();

    return NextResponse.json({ success: true, data: updated, message: "Template updated" });
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
 * DELETE /api/admin/service-templates/[id]
 */
export async function DELETE(_request: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;
  try {
    const [existing] = await db.select().from(serviceTemplates).where(eq(serviceTemplates.id, id)).limit(1);
    if (!existing) {
      return NextResponse.json({ success: false, error: "Template not found" }, { status: 404 });
    }

    await db.delete(serviceTemplates).where(eq(serviceTemplates.id, id));
    return NextResponse.json({ success: true, message: "Template deleted" });
  } catch (error) {
    console.error("Error deleting template:", error);
    const status = isDatabaseConfigurationError(error) ? 503 : 500;
    return NextResponse.json(
      { success: false, error: "Failed to delete template", message: error instanceof Error ? error.message : "Unknown error" },
      { status },
    );
  }
}
