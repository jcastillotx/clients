import { NextRequest } from "next/server";
import { z } from "zod";
import { createClient } from "@/lib/supabase/server";
import { resolveStaffAccess } from "@/lib/api/resolve-staff-access";
import { staffGuideCategorySchema } from "@/lib/validations/partners-kb";
import {
  apiError,
  apiForbidden,
  apiInternalError,
  apiNotFound,
  apiSuccess,
  apiUnauthorized,
  apiValidationError,
} from "@/lib/api/response";
import { slugify } from "@/lib/api/slug";

type RouteContext = { params: Promise<{ id: string }> };

export async function PATCH(request: NextRequest, context: RouteContext) {
  try {
    const { id } = await context.params;
    const access = await resolveStaffAccess();
    if (!access) {
      return apiUnauthorized(request);
    }
    if (!access.isAdmin) {
      return apiForbidden(request);
    }

    const body = await request.json();
    const validated = staffGuideCategorySchema.partial().parse(body);
    const supabase = await createClient();

    const patch: Record<string, unknown> = {};
    if (validated.name !== undefined) patch.name = validated.name;
    if (validated.slug !== undefined) patch.slug = validated.slug;
    if (validated.name !== undefined && validated.slug === undefined) {
      patch.slug = slugify(validated.name);
    }
    if (validated.description !== undefined) patch.description = validated.description;
    if (validated.icon !== undefined) patch.icon = validated.icon;
    if (validated.position !== undefined) patch.position = validated.position;

    if (Object.keys(patch).length === 0) {
      return apiError(request, {
        status: 400,
        code: "BAD_REQUEST",
        message: "No fields to update",
      });
    }

    const { data, error } = await supabase
      .from("staff_guide_categories")
      .update(patch)
      .eq("id", id)
      .select("*")
      .maybeSingle();

    if (error) {
      return apiInternalError(request, error.message);
    }
    if (!data) {
      return apiNotFound(request, "Category not found");
    }

    return apiSuccess(request, data);
  } catch (error) {
    if (error instanceof z.ZodError) {
      return apiValidationError(request, error);
    }
    console.error("Error updating staff guide category:", error);
    return apiInternalError(request, "Failed to update category");
  }
}

export async function DELETE(request: NextRequest, context: RouteContext) {
  try {
    const { id } = await context.params;
    const access = await resolveStaffAccess();
    if (!access) {
      return apiUnauthorized(request);
    }
    if (!access.isAdmin) {
      return apiForbidden(request);
    }

    const supabase = await createClient();
    const { error } = await supabase.from("staff_guide_categories").delete().eq("id", id);

    if (error) {
      return apiInternalError(request, error.message);
    }

    return apiSuccess(request, { id });
  } catch (error) {
    console.error("Error deleting staff guide category:", error);
    return apiInternalError(request, "Failed to delete category");
  }
}
