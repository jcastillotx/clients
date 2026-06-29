import { NextRequest } from "next/server";
import { createClient } from "@/lib/supabase/server";
import { collectRoleNames } from "@/lib/rbac/role-row-utils";
import {
  apiForbidden,
  apiInternalError,
  apiNotFound,
  apiSuccess,
  apiUnauthorized,
} from "@/lib/api/response";

async function assertStaff(supabase: Awaited<ReturnType<typeof createClient>>, userId: string) {
  const { data: roleRows } = await supabase
    .from("user_roles")
    .select("role:roles(name)")
    .eq("user_id", userId);

  const roleNames = collectRoleNames(roleRows);
  const isStaff =
    roleNames.has("admin") ||
    roleNames.has("super_admin") ||
    roleNames.has("account_manager") ||
    roleNames.has("staff");

  if (isStaff) return true;

  const { data: userData } = await supabase
    .from("users")
    .select("is_super_admin")
    .eq("id", userId)
    .single();

  return Boolean(userData?.is_super_admin);
}

export async function PATCH(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const supabase = await createClient();
  const { id } = await params;

  const {
    data: { user },
  } = await supabase.auth.getUser();

  if (!user) return apiUnauthorized(req);

  try {
    const authorized = await assertStaff(supabase, user.id);
    if (!authorized) return apiForbidden(req);

    const body = await req.json();

    const updatePayload: Record<string, unknown> = {
      updated_at: new Date().toISOString(),
    };

    if (typeof body.isActive === "boolean") updatePayload.is_active = body.isActive;
    if (body.name) updatePayload.name = body.name;
    if (body.frequency) updatePayload.frequency = body.frequency;
    if (body.recipients) updatePayload.recipients = body.recipients;

    const { data, error } = await supabase
      .from("report_schedules")
      .update(updatePayload)
      .eq("id", id)
      .select()
      .single();

    if (error || !data) return apiNotFound(req, "Report schedule not found");

    return apiSuccess(req, data, { extra: { schedule: data } });
  } catch (error) {
    console.error("Error updating report schedule:", error);
    return apiInternalError(req, "Failed to update report schedule");
  }
}

export async function DELETE(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const supabase = await createClient();
  const { id } = await params;

  const {
    data: { user },
  } = await supabase.auth.getUser();

  if (!user) return apiUnauthorized(req);

  try {
    const authorized = await assertStaff(supabase, user.id);
    if (!authorized) return apiForbidden(req);

    const { data: existing } = await supabase
      .from("report_schedules")
      .select("id")
      .eq("id", id)
      .single();

    if (!existing) return apiNotFound(req, "Report schedule not found");

    const { error } = await supabase.from("report_schedules").delete().eq("id", id);

    if (error) throw error;

    return apiSuccess(req, null, { extra: { deleted: true } });
  } catch (error) {
    console.error("Error deleting report schedule:", error);
    return apiInternalError(req, "Failed to delete report schedule");
  }
}
