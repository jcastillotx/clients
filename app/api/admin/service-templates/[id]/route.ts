import { NextRequest } from "next/server";
import { db, isDatabaseConfigurationError } from "@/lib/db";
import { serviceTemplates } from "@/lib/db/schema/proposals";
import { eq } from "drizzle-orm";
import { requireAdminUser } from "@/lib/auth/route-guards";
import {
  apiError,
  apiInternalError,
  apiNotFound,
  apiSuccess,
} from "@/lib/api/response";

/**
 * GET /api/admin/service-templates/[id]
 */
export async function GET(
  request: NextRequest,
  { params }: { params: Promise<{ id: string }> },
) {
  const { id } = await params;
  try {
    const guard = await requireAdminUser(request);
    if ("error" in guard) {
      return guard.error;
    }

    const [template] = await db
      .select()
      .from(serviceTemplates)
      .where(eq(serviceTemplates.id, id))
      .limit(1);

    if (!template) {
      return apiNotFound(request, "Template not found");
    }

    return apiSuccess(request, template);
  } catch (error) {
    console.error("Error fetching template:", error);
    if (isDatabaseConfigurationError(error)) {
      return apiError(request, {
        status: 503,
        code: "SERVICE_UNAVAILABLE",
        message: "Failed to fetch template",
      });
    }
    return apiInternalError(
      request,
      error instanceof Error ? error.message : "Failed to fetch template",
    );
  }
}

/**
 * PATCH /api/admin/service-templates/[id]
 */
export async function PATCH(
  request: NextRequest,
  { params }: { params: Promise<{ id: string }> },
) {
  const { id } = await params;
  try {
    const guard = await requireAdminUser(request);
    if ("error" in guard) {
      return guard.error;
    }

    const body = await request.json();

    const [existing] = await db
      .select()
      .from(serviceTemplates)
      .where(eq(serviceTemplates.id, id))
      .limit(1);
    if (!existing) {
      return apiNotFound(request, "Template not found");
    }

    const updateData: Record<string, unknown> = {
      ...body,
      updatedAt: new Date(),
    };
    if (body.lineItems) {
      const lineItems = body.lineItems as Array<{
        quantity?: number;
        unitPrice?: number;
      }>;
      updateData.totalAmount = lineItems
        .reduce(
          (sum: number, item: { quantity?: number; unitPrice?: number }) =>
            sum + (item.quantity || 1) * (item.unitPrice || 0),
          0,
        )
        .toString();
    }

    const [updated] = await db
      .update(serviceTemplates)
      .set(updateData)
      .where(eq(serviceTemplates.id, id))
      .returning();

    return apiSuccess(request, updated, { extra: { message: "Template updated" } });
  } catch (error) {
    console.error("Error updating template:", error);
    if (isDatabaseConfigurationError(error)) {
      return apiError(request, {
        status: 503,
        code: "SERVICE_UNAVAILABLE",
        message: "Failed to update template",
      });
    }
    return apiInternalError(
      request,
      error instanceof Error ? error.message : "Failed to update template",
    );
  }
}

/**
 * DELETE /api/admin/service-templates/[id]
 */
export async function DELETE(
  request: NextRequest,
  { params }: { params: Promise<{ id: string }> },
) {
  const { id } = await params;
  try {
    const guard = await requireAdminUser(request);
    if ("error" in guard) {
      return guard.error;
    }

    const [existing] = await db
      .select()
      .from(serviceTemplates)
      .where(eq(serviceTemplates.id, id))
      .limit(1);
    if (!existing) {
      return apiNotFound(request, "Template not found");
    }

    await db.delete(serviceTemplates).where(eq(serviceTemplates.id, id));
    return apiSuccess(request, { deleted: true }, { extra: { message: "Template deleted" } });
  } catch (error) {
    console.error("Error deleting template:", error);
    if (isDatabaseConfigurationError(error)) {
      return apiError(request, {
        status: 503,
        code: "SERVICE_UNAVAILABLE",
        message: "Failed to delete template",
      });
    }
    return apiInternalError(
      request,
      error instanceof Error ? error.message : "Failed to delete template",
    );
  }
}
