import { NextRequest } from "next/server";
import { createClient } from "@/lib/supabase/server";
import { isAdminUser } from "@/lib/rbac/check";
import { apiSuccess, apiUnauthorized, apiForbidden, apiInternalError } from "@/lib/api/response";

/**
 * GET /api/users/staff
 *
 * Returns a list of users who can be assigned to projects (staff/admin).
 * Admin-only endpoint.
 *
 * Query params:
 *   ?search=  — optional case-insensitive filter on name or email
 */
export async function GET(request: NextRequest) {
  try {
    const supabase = await createClient();
    const { data: { user } } = await supabase.auth.getUser();

    if (!user) {
      return apiUnauthorized(request);
    }

    const [{ data: dbUser }, { data: roleRows }] = await Promise.all([
      supabase.from("users").select("is_super_admin").eq("id", user.id).maybeSingle(),
      supabase.from("user_roles").select("role:roles(name)").eq("user_id", user.id),
    ]);

    if (!isAdminUser(user, dbUser, roleRows)) {
      return apiForbidden(request);
    }

    const { searchParams } = new URL(request.url);
    const search = searchParams.get("search")?.trim() ?? "";

    let query = supabase
      .from("users")
      .select("id, name, email, avatar, is_super_admin")
      .not("name", "is", null)
      .order("name");

    if (search) {
      query = query.or(`name.ilike.%${search}%,email.ilike.%${search}%`);
    }

    const { data, error } = await query;

    if (error) {
      return apiInternalError(request, "Failed to fetch staff users");
    }

    return apiSuccess(request, data ?? []);
  } catch (error) {
    console.error("Error fetching staff users:", error);
    return apiInternalError(request, "Failed to fetch staff users");
  }
}
