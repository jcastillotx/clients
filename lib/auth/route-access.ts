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
};

export function normalizeRoleName(roleRow: RoleRow): string {
  if (Array.isArray(roleRow.role)) {
    return String(roleRow.role[0]?.name || "").toLowerCase();
  }

  return String(roleRow.role?.name || "").toLowerCase();
}

export function isAdminAccess(
  user: AuthUser,
  dbUser: DbUser | null,
  roleRows: RoleRow[] | null | undefined,
): boolean {
  const metadataRole = String(
    user.user_metadata?.role || user.user_metadata?.app_role || "",
  ).toLowerCase();
  const roleNames = (roleRows || []).map(normalizeRoleName);

  return Boolean(
    dbUser?.is_super_admin ||
    user.user_metadata?.is_super_admin === true ||
    metadataRole === "admin" ||
    metadataRole === "super_admin" ||
    metadataRole === "account_manager" ||
    roleNames.includes("admin") ||
    roleNames.includes("super_admin") ||
    roleNames.includes("account_manager"),
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

  return {
    clientId: dbUser?.client_id || null,
    isAdmin: isAdminAccess(
      user,
      dbUser,
      roleRows as RoleRow[] | null | undefined,
    ),
  };
}

export function canAccessClient(
  access: RouteAccess,
  requestedClientId: string,
): boolean {
  return access.isAdmin || access.clientId === requestedClientId;
}
