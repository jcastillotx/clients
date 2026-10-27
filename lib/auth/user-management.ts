import type { createAdminClient } from "@/lib/supabase/server";
import { collectRoleNames, type RoleJoinRow } from "@/lib/rbac/role-row-utils";

type AdminClient = ReturnType<typeof createAdminClient>;

export async function canManageUserTarget(
  client: AdminClient,
  targetId: string,
  actorIsAdmin: boolean,
): Promise<boolean> {
  if (actorIsAdmin) return true;
  const [profile, roles] = await Promise.all([
    client
      .from("users")
      .select("id, is_super_admin")
      .eq("id", targetId)
      .maybeSingle(),
    client
      .from("user_roles")
      .select("role:roles(name)")
      .eq("user_id", targetId),
  ]);
  if (profile.error) throw profile.error;
  if (roles.error) throw roles.error;
  if (!profile.data || profile.data.is_super_admin) return false;
  const names = collectRoleNames(roles.data as RoleJoinRow[]);
  return !names.has("admin") && !names.has("super_admin");
}

export async function canAssignRequestedRoles(
  client: AdminClient,
  roleIds: string[],
  actorIsAdmin: boolean,
): Promise<boolean> {
  if (actorIsAdmin || roleIds.length === 0) return true;
  const { data, error } = await client
    .from("roles")
    .select("id, name")
    .in("id", roleIds);
  if (error) throw error;
  return (
    data?.length === new Set(roleIds).size &&
    data.every(
      (role: { name: string }) =>
        !["admin", "super_admin", "account_manager"].includes(role.name),
    )
  );
}
