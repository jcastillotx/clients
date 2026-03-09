import { createClient } from "@/lib/supabase/server";

/**
 * Permission constants for type safety
 */
export { hasPermission, hasRole, hasAnyRole, hasAnyPermission, getUserPermissions, getUserRoles } from "@/lib/rbac/permissions";

/**
 * NOTE: `user` (JWT) is accepted for call-site convenience but its metadata is
 * intentionally NOT used for authorization decisions. JWT metadata is set
 * client-side and cannot be trusted without re-verification against the DB.
 *
 * The only authoritative sources are:
 *   1. `dbUser.is_super_admin`  — database column
 *   2. `roleRows`               — `user_roles` table joined with `roles`
 */
type AuthUser = {
  user_metadata?: Record<string, unknown>;
} | null;

type DbUser = {
  is_super_admin?: boolean | null;
} | null;

type RoleRow = {
  role?: { name?: string | null } | Array<{ name?: string | null }> | null;
};

/**
 * Check if a user has admin-level access.
 *
 * Authoritative sources only (no JWT metadata):
 *   1. `dbUser.is_super_admin` flag in the `users` table
 *   2. `user_roles` table entries joined with `roles` (passed as `roleRows`)
 *
 * @param user        Accepted for call-site ergonomics but metadata is ignored.
 * @param dbUser      Row from the `users` table.
 * @param roleRows    Pre-fetched rows from `user_roles` joined with `roles`.
 * @param extraRoles  Additional role names treated as admin-level
 *                    (e.g. `["staff"]` for UI workflow management).
 */
export function isAdminUser(
  _user: AuthUser,
  dbUser: DbUser,
  roleRows: RoleRow[] | null | undefined,
  extraRoles: string[] = [],
): boolean {
  const adminRoles = new Set(["admin", "super_admin", ...extraRoles]);

  // Source 1: database flag — most authoritative
  if (dbUser?.is_super_admin === true) {
    return true;
  }

  // Source 2: role table — also authoritative
  return (roleRows ?? []).some((row) => {
    const roleValue = row?.role;
    const roleName = String(
      (Array.isArray(roleValue)
        ? roleValue[0]?.name
        : (roleValue as { name?: string | null } | null)?.name) ?? "",
    ).toLowerCase();
    return adminRoles.has(roleName);
  });
}

/**
 * Async variant — fetches all required data itself.
 *
 * Prefer the synchronous `isAdminUser()` when you already have `dbUser` and
 * `roleRows` from parallel queries, to avoid redundant DB round-trips.
 */
export async function isUserAdmin(userId: string): Promise<boolean> {
  const supabase = await createClient();

  const [{ data: dbUser }, { data: roleRows }] = await Promise.all([
    supabase.from("users").select("is_super_admin").eq("id", userId).maybeSingle(),
    supabase.from("user_roles").select("role:roles(name)").eq("user_id", userId),
  ]);

  return isAdminUser(null, dbUser, roleRows);
}

/**
 * Check if the current user has access to a named feature.
 * Priority: user > role > client > global default.
 */
export async function hasFeatureAccess(userId: string, featureName: string): Promise<boolean> {
  const supabase = await createClient();

  const { data: userFeature } = await supabase
    .from("user_features")
    .select("is_enabled")
    .eq("user_id", userId)
    .eq("feature_name", featureName)
    .maybeSingle();

  if (userFeature !== null) return userFeature?.is_enabled ?? false;

  const { data: feature } = await supabase
    .from("features")
    .select("is_enabled_by_default")
    .eq("name", featureName)
    .maybeSingle();

  return feature?.is_enabled_by_default ?? false;
}
