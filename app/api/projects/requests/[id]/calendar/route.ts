import { NextRequest } from "next/server";
import {
  apiForbidden,
  apiInternalError,
  apiNotFound,
  apiSuccess,
  apiUnauthorized,
} from "@/lib/api/response";

import { createClient } from "@/lib/supabase/server";

async function resolveAccess(supabase: Awaited<ReturnType<typeof createClient>>, user: { id: string; user_metadata?: Record<string, unknown> }) {
  const [{ data: dbUser }, { data: roleRows }] = await Promise.all([
    supabase.from("users").select("id, client_id, is_super_admin").eq("id", user.id).maybeSingle(),
    supabase.from("user_roles").select("role:roles(name)").eq("user_id", user.id),
  ]);

  const metadataRole = String(user.user_metadata?.role || user.user_metadata?.app_role || "").toLowerCase();
  const roleNames = (roleRows || []).map((row: unknown) => {
    const roleRow = row as { role?: { name?: string } | Array<{ name?: string }> };
    if (Array.isArray(roleRow.role)) {
      return String(roleRow.role[0]?.name || "").toLowerCase();
    }
    return String(roleRow.role?.name || "").toLowerCase();
  });

  const isAdmin = Boolean(
    dbUser?.is_super_admin ||
      user.user_metadata?.is_super_admin === true ||
      metadataRole === "admin" ||
      metadataRole === "super_admin" ||
      roleNames.includes("admin") ||
      roleNames.includes("super_admin"),
  );

  return {
    clientId: dbUser?.client_id || null,
    isAdmin,
  };
}

/**
 * GET /api/projects/requests/[id]/calendar
 *
 * AJAX feed for meetings/calendar entries linked to the project request.
 */
export async function GET(request: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;
  try {
    const supabase = await createClient();
    const {
      data: { user },
      error: authError,
    } = await supabase.auth.getUser();

    if (authError || !user) {
      return apiUnauthorized(request);
    }

    const access = await resolveAccess(supabase, user);

    const { data: requestRow, error: requestError } = await supabase
      .from("requests")
      .select("id, client_id")
      .eq("id", id)
      .contains("custom_fields", { type: "project" })
      .single();

    if (requestError || !requestRow) {
      return apiNotFound(request, "Project request not found");
    }

    if (!access.isAdmin && access.clientId !== requestRow.client_id) {
      return apiForbidden(request);
    }

    const { data, error } = await supabase
      .from("meetings")
      .select("id, title, meeting_type, scheduled_at, duration_minutes, location, meeting_url, status, client:clients(company_name)")
      .eq("request_id", id)
      .order("scheduled_at", { ascending: true });

    if (error) {
      return apiInternalError(request, error.message);
    }

    return apiSuccess(request, data || []);
  } catch (error) {
    console.error("Error fetching project request calendar:", error);
    return apiInternalError(request, "Failed to fetch calendar data");
  }
}
