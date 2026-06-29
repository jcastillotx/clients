import { NextRequest } from "next/server";
import { z } from "zod";
import { createClient } from "@/lib/supabase/server";
import { resolveStaffAccess } from "@/lib/api/resolve-staff-access";
import {
  apiForbidden,
  apiInternalError,
  apiNotFound,
  apiSuccess,
  apiUnauthorized,
  apiValidationError,
} from "@/lib/api/response";

type RouteContext = { params: Promise<{ id: string }> };

const updateGuideSchema = z.object({
  slug: z.string().min(1).optional(),
  status: z.enum(["draft", "published"]).optional(),
  cover_image: z.string().optional().nullable(),
  is_public: z.boolean().optional(),
  password_protected: z.boolean().optional(),
  password: z.string().optional().nullable(),
  published_at: z.string().datetime().optional().nullable(),
  meta: z.record(z.unknown()).optional().nullable(),
});

export async function GET(request: NextRequest, context: RouteContext) {
  try {
    const { id } = await context.params;
    const access = await resolveStaffAccess();
    if (!access) return apiUnauthorized(request);
    if (!access.isStaff) return apiForbidden(request);

    const supabase = await createClient();
    let query = supabase
      .from("brand_guides")
      .select(`
        *,
        creator:users!brand_guides_created_by_fkey(id, name),
        sections:brand_guide_sections(*),
        colors:brand_colors(*),
        fonts:brand_fonts(*),
        templates:brand_templates(*)
      `)
      .eq("id", id);

    if (!access.isAdmin && access.clientId) {
      query = query.eq("client_id", access.clientId);
    }

    const { data, error } = await query.maybeSingle();
    if (error) return apiInternalError(request, error.message);
    if (!data) return apiNotFound(request, "Brand guide not found");

    return apiSuccess(request, data, { extra: { guide: data } });
  } catch (err) {
    console.error("Error fetching brand guide:", err);
    return apiInternalError(request, "Failed to fetch brand guide");
  }
}

export async function PATCH(request: NextRequest, context: RouteContext) {
  try {
    const { id } = await context.params;
    const access = await resolveStaffAccess();
    if (!access) return apiUnauthorized(request);
    if (!access.isStaff) return apiForbidden(request);

    const body = await request.json();
    const validated = updateGuideSchema.parse(body);

    const patch: Record<string, unknown> = {};
    if (validated.slug !== undefined) patch.slug = validated.slug;
    if (validated.status !== undefined) {
      patch.status = validated.status;
      // Auto-set published_at when publishing
      if (validated.status === "published" && validated.published_at === undefined) {
        patch.published_at = new Date().toISOString();
      }
    }
    if (validated.cover_image !== undefined) patch.cover_image = validated.cover_image;
    if (validated.is_public !== undefined) patch.is_public = validated.is_public;
    if (validated.password_protected !== undefined)
      patch.password_protected = validated.password_protected;
    if (validated.password !== undefined) patch.password = validated.password;
    if (validated.published_at !== undefined) patch.published_at = validated.published_at;
    if (validated.meta !== undefined) patch.meta = validated.meta;

    const supabase = await createClient();
    let query = supabase
      .from("brand_guides")
      .update({ ...patch, updated_at: new Date().toISOString() })
      .eq("id", id);

    if (!access.isAdmin && access.clientId) {
      query = query.eq("client_id", access.clientId);
    }

    const { data, error } = await query.select().maybeSingle();
    if (error) return apiInternalError(request, error.message);
    if (!data) return apiNotFound(request, "Brand guide not found");

    return apiSuccess(request, data, { extra: { guide: data } });
  } catch (err) {
    if (err instanceof z.ZodError) return apiValidationError(request, err);
    console.error("Error updating brand guide:", err);
    return apiInternalError(request, "Failed to update brand guide");
  }
}

export async function DELETE(request: NextRequest, context: RouteContext) {
  try {
    const { id } = await context.params;
    const access = await resolveStaffAccess();
    if (!access) return apiUnauthorized(request);
    if (!access.isAdmin) return apiForbidden(request);

    const supabase = await createClient();
    const { error } = await supabase.from("brand_guides").delete().eq("id", id);
    if (error) return apiInternalError(request, error.message);

    return apiSuccess(request, { id });
  } catch (err) {
    console.error("Error deleting brand guide:", err);
    return apiInternalError(request, "Failed to delete brand guide");
  }
}
