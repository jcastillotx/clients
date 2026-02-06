import { createClient } from "@/lib/supabase/server";

/**
 * Check if the current user has a specific permission
 */
export async function hasPermission(permissionName: string): Promise<boolean> {
  const supabase = await createClient();

  const {
    data: { user },
  } = await supabase.auth.getUser();
  if (!user) return false;

  const { data, error } = await supabase.rpc("user_has_permission", {
    p_user_id: user.id,
    p_permission_name: permissionName,
  });

  if (error) {
    console.error("Error checking permission:", error);
    return false;
  }

  return data === true;
}

/**
 * Check if the current user has a specific role
 */
export async function hasRole(roleName: string): Promise<boolean> {
  const supabase = await createClient();

  const {
    data: { user },
  } = await supabase.auth.getUser();
  if (!user) return false;

  const { data, error } = await supabase.rpc("user_has_role", {
    p_user_id: user.id,
    p_role_name: roleName,
  });

  if (error) {
    console.error("Error checking role:", error);
    return false;
  }

  return data === true;
}

/**
 * Get all permissions for the current user
 */
export async function getUserPermissions(): Promise<
  Array<{ permission_name: string; resource: string; action: string }>
> {
  const supabase = await createClient();

  const {
    data: { user },
  } = await supabase.auth.getUser();
  if (!user) return [];

  const { data, error } = await supabase.rpc("get_user_permissions", {
    p_user_id: user.id,
  });

  if (error) {
    console.error("Error getting user permissions:", error);
    return [];
  }

  return data || [];
}

/**
 * Get all roles for the current user
 */
export async function getUserRoles(): Promise<Array<{ role_id: string; role_name: string; role_description: string }>> {
  const supabase = await createClient();

  const {
    data: { user },
  } = await supabase.auth.getUser();
  if (!user) return [];

  const { data, error } = await supabase.rpc("get_user_roles", {
    p_user_id: user.id,
  });

  if (error) {
    console.error("Error getting user roles:", error);
    return [];
  }

  return data || [];
}

/**
 * Check if user has any of the specified permissions
 */
export async function hasAnyPermission(permissionNames: string[]): Promise<boolean> {
  const permissions = await getUserPermissions();
  const userPermissionNames = permissions.map((p) => p.permission_name);
  return permissionNames.some((name) => userPermissionNames.includes(name));
}

/**
 * Check if user has all of the specified permissions
 */
export async function hasAllPermissions(permissionNames: string[]): Promise<boolean> {
  const permissions = await getUserPermissions();
  const userPermissionNames = permissions.map((p) => p.permission_name);
  return permissionNames.every((name) => userPermissionNames.includes(name));
}

/**
 * Client-side permission check (use in Client Components)
 */
export async function checkPermissionClient(permissionName: string): Promise<boolean> {
  try {
    const response = await fetch("/api/auth/check-permission", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ permission: permissionName }),
    });

    if (!response.ok) return false;
    const data = await response.json();
    return data.hasPermission === true;
  } catch (error) {
    console.error("Error checking permission:", error);
    return false;
  }
}

/**
 * Permission constants for type safety
 */
export const Permissions = {
  // Clients
  CLIENTS_CREATE: "clients.create",
  CLIENTS_READ: "clients.read",
  CLIENTS_UPDATE: "clients.update",
  CLIENTS_DELETE: "clients.delete",

  // Invoices
  INVOICES_CREATE: "invoices.create",
  INVOICES_READ: "invoices.read",
  INVOICES_UPDATE: "invoices.update",
  INVOICES_DELETE: "invoices.delete",
  INVOICES_SEND: "invoices.send",
  INVOICES_PAY: "invoices.pay",

  // Requests
  REQUESTS_CREATE: "requests.create",
  REQUESTS_READ: "requests.read",
  REQUESTS_UPDATE: "requests.update",
  REQUESTS_DELETE: "requests.delete",
  REQUESTS_ASSIGN: "requests.assign",

  // Users
  USERS_CREATE: "users.create",
  USERS_READ: "users.read",
  USERS_UPDATE: "users.update",
  USERS_DELETE: "users.delete",
  USERS_ASSIGN_ROLES: "users.assign_roles",

  // Roles
  ROLES_CREATE: "roles.create",
  ROLES_READ: "roles.read",
  ROLES_UPDATE: "roles.update",
  ROLES_DELETE: "roles.delete",

  // Documents
  DOCUMENTS_CREATE: "documents.create",
  DOCUMENTS_READ: "documents.read",
  DOCUMENTS_UPDATE: "documents.update",
  DOCUMENTS_DELETE: "documents.delete",

  // Reports
  REPORTS_FINANCIAL: "reports.financial",
  REPORTS_ANALYTICS: "reports.analytics",
  REPORTS_EXPORT: "reports.export",

  // Settings
  SETTINGS_READ: "settings.read",
  SETTINGS_UPDATE: "settings.update",
} as const;

/**
 * Role constants
 */
export const Roles = {
  SUPER_ADMIN: "super_admin",
  ADMIN: "admin",
  ACCOUNT_MANAGER: "account_manager",
  STAFF: "staff",
  CLIENT: "client",
} as const;
