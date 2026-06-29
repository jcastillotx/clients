import { NextRequest } from "next/server";
import { createClient } from "@/lib/supabase/server";
import { collectRoleNames } from "@/lib/rbac/role-row-utils";
import {
  apiForbidden,
  apiInternalError,
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

export async function GET(req: NextRequest) {
  const supabase = await createClient();

  const {
    data: { user },
  } = await supabase.auth.getUser();

  if (!user) return apiUnauthorized(req);

  try {
    const authorized = await assertStaff(supabase, user.id);
    if (!authorized) return apiForbidden(req);

    const { data: userData } = await supabase
      .from("users")
      .select("client_id, is_super_admin")
      .eq("id", user.id)
      .single();

    const { data: roleRows } = await supabase
      .from("user_roles")
      .select("role:roles(name)")
      .eq("user_id", user.id);

    const roleNames = collectRoleNames(roleRows);
    const isAdmin = Boolean(userData?.is_super_admin) || roleNames.has("admin") || roleNames.has("super_admin");

    let query = supabase
      .from("report_schedules")
      .select(`
        *,
        template:report_templates(id, name, report_type)
      `)
      .order("created_at", { ascending: false });

    if (!isAdmin && userData?.client_id) {
      query = query.eq("client_id", userData.client_id);
    }

    const { data, error } = await query;

    if (error) throw error;

    const rows = data ?? [];
    return apiSuccess(req, rows, { extra: { schedules: rows } });
  } catch (error) {
    console.error("Error fetching report schedules:", error);
    return apiInternalError(req, "Failed to fetch report schedules");
  }
}
