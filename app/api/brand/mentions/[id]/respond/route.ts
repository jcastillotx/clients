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

const respondSchema = z.object({
  response_notes: z.string().optional().nullable(),
});

export async function POST(request: NextRequest, context: RouteContext) {
  try {
    const { id } = await context.params;
    const access = await resolveStaffAccess();
    if (!access) return apiUnauthorized(request);
    if (!access.isStaff) return apiForbidden(request);

    const body = await request.json();
    const validated = respondSchema.parse(body);

    const supabase = await createClient();

    // Verify the mention exists and belongs to the user's client (non-admins)
    let checkQuery = supabase.from("brand_mentions").select("id, responded_at").eq("id", id);
    if (!access.isAdmin && access.clientId) {
      checkQuery = checkQuery.eq("client_id", access.clientId);
    }
    const { data: existing, error: checkError } = await checkQuery.maybeSingle();

    if (checkError) return apiInternalError(request, checkError.message);
    if (!existing) return apiNotFound(request, "Mention not found");

    const { data, error } = await supabase
      .from("brand_mentions")
      .update({
        responded_at: new Date().toISOString(),
        responded_by: access.userId,
        response_notes: validated.response_notes ?? null,
        updated_at: new Date().toISOString(),
      })
      .eq("id", id)
      .select()
      .maybeSingle();

    if (error) return apiInternalError(request, error.message);
    if (!data) return apiNotFound(request, "Mention not found");

    return apiSuccess(request, data, { extra: { mention: data } });
  } catch (err) {
    if (err instanceof z.ZodError) return apiValidationError(request, err);
    console.error("Error responding to brand mention:", err);
    return apiInternalError(request, "Failed to respond to brand mention");
  }
}
