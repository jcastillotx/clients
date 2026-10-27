import type { createClient } from "@/lib/supabase/server";

type SupabaseClient = Awaited<ReturnType<typeof createClient>>;

type AuthUser = {
  id: string;
  user_metadata?: Record<string, unknown>;
};

type RoleRow = {
  role?: { name?: string | null } | Array<{ name?: string | null }> | null;
};

type DbUser = {
  client_id?: string | null;
  is_super_admin?: boolean | null;
};

export type RouteAccess = {
  clientId: string | null;
  isAdmin: boolean;
  isStaff?: boolean;
};

export function normalizeRoleName(roleRow: RoleRow): string {
  if (Array.isArray(roleRow.role)) {
    return String(roleRow.role[0]?.name || "").toLowerCase();
  }

  return String(roleRow.role?.name || "").toLowerCase();
}

export function isAdminAccess(
  _user: AuthUser,
  dbUser: DbUser | null,
  roleRows: RoleRow[] | null | undefined,
): boolean {
  const roleNames = (roleRows || []).map(normalizeRoleName);

  return Boolean(
    dbUser?.is_super_admin ||
    roleNames.includes("admin") ||
    roleNames.includes("super_admin") ||
    roleNames.includes("account_manager"),
  );
}

export function isStaffAccess(
  _user: AuthUser,
  dbUser: DbUser | null,
  roleRows: RoleRow[] | null | undefined,
): boolean {
  const roleNames = (roleRows || []).map(normalizeRoleName);

  return Boolean(
    isAdminAccess(_user, dbUser, roleRows) ||
    roleNames.includes("staff"),
  );
}

export async function resolveRouteAccess(
  supabase: SupabaseClient,
  user: AuthUser,
): Promise<RouteAccess> {
  const [{ data: dbUser }, { data: roleRows }] = await Promise.all([
    supabase
      .from("users")
      .select("client_id, is_super_admin")
      .eq("id", user.id)
      .maybeSingle(),
    supabase
      .from("user_roles")
      .select("role:roles(name)")
      .eq("user_id", user.id),
  ]);

  const normalizedRoleRows = roleRows as RoleRow[] | null | undefined;

  return {
    clientId: dbUser?.client_id || null,
    isAdmin: isAdminAccess(user, dbUser, normalizedRoleRows),
    isStaff: isStaffAccess(user, dbUser, normalizedRoleRows),
  };
}

export function canAccessClient(
  access: RouteAccess,
  requestedClientId: string,
): boolean {
  return (
    access.isAdmin || access.isStaff || access.clientId === requestedClientId
  );
}
