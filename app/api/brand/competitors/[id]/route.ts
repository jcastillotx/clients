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

const updateCompetitorSchema = z.object({
  competitor_name: z.string().min(1).optional(),
  website_url: z.string().url().optional().nullable(),
  positioning: z.string().optional().nullable(),
  target_audience: z.string().optional().nullable(),
  key_differentiators: z.array(z.string()).optional().nullable(),
  is_active: z.boolean().optional(),
  meta: z
    .object({
      socialLinks: z
        .object({
          facebook: z.string().optional(),
          twitter: z.string().optional(),
          linkedin: z.string().optional(),
          instagram: z.string().optional(),
        })
        .optional(),
      strengths: z.array(z.string()).optional(),
      weaknesses: z.array(z.string()).optional(),
      marketShare: z.number().optional(),
    })
    .optional()
    .nullable(),
});

export async function GET(request: NextRequest, context: RouteContext) {
  try {
    const { id } = await context.params;
    const access = await resolveStaffAccess();
    if (!access) return apiUnauthorized(request);
    if (!access.isStaff) return apiForbidden(request);

    const supabase = await createClient();
    let query = supabase.from("brand_competitors").select("*").eq("id", id);

    if (!access.isAdmin && access.clientId) {
      query = query.eq("client_id", access.clientId);
    }

    const { data, error } = await query.maybeSingle();
    if (error) return apiInternalError(request, error.message);
    if (!data) return apiNotFound(request, "Competitor not found");

    return apiSuccess(request, data, { extra: { competitor: data } });
  } catch (err) {
    console.error("Error fetching brand competitor:", err);
    return apiInternalError(request, "Failed to fetch brand competitor");
  }
}

export async function PATCH(request: NextRequest, context: RouteContext) {
  try {
    const { id } = await context.params;
    const access = await resolveStaffAccess();
    if (!access) return apiUnauthorized(request);
    if (!access.isStaff) return apiForbidden(request);

    const body = await request.json();
    const validated = updateCompetitorSchema.parse(body);

    const patch: Record<string, unknown> = {};
    if (validated.competitor_name !== undefined) patch.competitor_name = validated.competitor_name;
    if (validated.website_url !== undefined) patch.website_url = validated.website_url;
    if (validated.positioning !== undefined) patch.positioning = validated.positioning;
    if (validated.target_audience !== undefined) patch.target_audience = validated.target_audience;
    if (validated.key_differentiators !== undefined)
      patch.key_differentiators = validated.key_differentiators;
    if (validated.is_active !== undefined) patch.is_active = validated.is_active;
    if (validated.meta !== undefined) patch.meta = validated.meta;

    const supabase = await createClient();
    let query = supabase
      .from("brand_competitors")
      .update({ ...patch, updated_at: new Date().toISOString() })
      .eq("id", id);

    if (!access.isAdmin && access.clientId) {
      query = query.eq("client_id", access.clientId);
    }

    const { data, error } = await query.select().maybeSingle();
    if (error) return apiInternalError(request, error.message);
    if (!data) return apiNotFound(request, "Competitor not found");

    return apiSuccess(request, data, { extra: { competitor: data } });
  } catch (err) {
    if (err instanceof z.ZodError) return apiValidationError(request, err);
    console.error("Error updating brand competitor:", err);
    return apiInternalError(request, "Failed to update brand competitor");
  }
}

export async function DELETE(request: NextRequest, context: RouteContext) {
  try {
    const { id } = await context.params;
    const access = await resolveStaffAccess();
    if (!access) return apiUnauthorized(request);
    if (!access.isAdmin) return apiForbidden(request);

    const supabase = await createClient();
    const { error } = await supabase.from("brand_competitors").delete().eq("id", id);
    if (error) return apiInternalError(request, error.message);

    return apiSuccess(request, { id });
  } catch (err) {
    console.error("Error deleting brand competitor:", err);
    return apiInternalError(request, "Failed to delete brand competitor");
  }
}
