import { createClient } from "@/lib/supabase/server";
import { collectRoleNames } from "@/lib/rbac/role-row-utils";

export type StaffAccess = {
  userId: string;
  clientId: string | null;
  isAdmin: boolean;
  isStaff: boolean;
};

export async function resolveStaffAccess(): Promise<StaffAccess | null> {
  const supabase = await createClient();
  const {
    data: { user },
    error,
  } = await supabase.auth.getUser();

  if (error || !user) {
    return null;
  }

  const [{ data: dbUser }, { data: roleRows }] = await Promise.all([
    supabase.from("users").select("client_id, is_super_admin").eq("id", user.id).maybeSingle(),
    supabase.from("user_roles").select("role:roles(name)").eq("user_id", user.id),
  ]);

  const roleNames = collectRoleNames(roleRows);
  const isAdmin =
    Boolean(dbUser?.is_super_admin) || roleNames.has("admin") || roleNames.has("super_admin");
  const isStaff =
    isAdmin || roleNames.has("account_manager") || roleNames.has("staff");

  return {
    userId: user.id,
    clientId: dbUser?.client_id ?? null,
    isAdmin,
    isStaff,
  };
}
