import { NextRequest } from "next/server";
import { db, isDatabaseConfigurationError } from "@/lib/db";
import { maintenancePlanTemplates } from "@/lib/db/schema/maintenance-plans";
import { eq } from "drizzle-orm";
import { requireAdminUser } from "@/lib/auth/route-guards";
import {
  apiError,
  apiInternalError,
  apiNotFound,
  apiSuccess,
} from "@/lib/api/response";

/**
 * GET /api/admin/maintenance-plan-templates/[id]
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
      .from(maintenancePlanTemplates)
      .where(eq(maintenancePlanTemplates.id, id))
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
 * PATCH /api/admin/maintenance-plan-templates/[id]
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
      .from(maintenancePlanTemplates)
      .where(eq(maintenancePlanTemplates.id, id))
      .limit(1);

    if (!existing) {
      return apiNotFound(request, "Template not found");
    }

    const [updated] = await db
      .update(maintenancePlanTemplates)
      .set({ ...body, updatedAt: new Date() })
      .where(eq(maintenancePlanTemplates.id, id))
      .returning();

    return apiSuccess(request, updated, {
      extra: { message: "Template updated successfully" },
    });
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
 * DELETE /api/admin/maintenance-plan-templates/[id]
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
      .from(maintenancePlanTemplates)
      .where(eq(maintenancePlanTemplates.id, id))
      .limit(1);

    if (!existing) {
      return apiNotFound(request, "Template not found");
    }

    await db
      .delete(maintenancePlanTemplates)
      .where(eq(maintenancePlanTemplates.id, id));

    return apiSuccess(request, { deleted: true }, {
      extra: { message: "Template deleted successfully" },
    });
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
