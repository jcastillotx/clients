import { createClient } from "@/lib/supabase/server";

/**
 * Check if the current user has a specific permission
 * @param permissionName - The permission to check
 * @param options - Optional supabase client and userId to avoid redundant auth calls
 */
export async function hasPermission(
  permissionName: string,
  options?: { supabase?: Awaited<ReturnType<typeof createClient>>; userId?: string }
): Promise<boolean> {
  const supabase = options?.supabase ?? (await createClient());

  let userId = options?.userId;
  if (!userId) {
    const {
      data: { user },
    } = await supabase.auth.getUser();
    if (!user) return false;
    userId = user.id;
  }

  const { data, error } = await supabase.rpc("user_has_permission", {
    p_user_id: userId,
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
  return hasRoleInternal(roleName);
}

async function hasRoleInternal(
  roleName: string,
  options?: { supabase?: Awaited<ReturnType<typeof createClient>>; userId?: string },
): Promise<boolean> {
  const supabase = options?.supabase ?? (await createClient());

  let userId = options?.userId;
  if (!userId) {
    const {
      data: { user },
    } = await supabase.auth.getUser();
    if (!user) return false;
    userId = user.id;
  }

  const { data, error } = await supabase.rpc("user_has_role", {
    p_user_id: userId,
    p_role_name: roleName,
  });

  if (error) {
    console.error("Error checking role:", error);
    return false;
  }

  return data === true;
}

/**
 * Check if the current user has any of the specified roles
 * @param roleNames - Array of role names to check
 * @param options - Optional supabase client and userId to avoid redundant auth calls
 */
export async function hasAnyRole(
  roleNames: string[],
  options?: { supabase?: Awaited<ReturnType<typeof createClient>>; userId?: string },
): Promise<boolean> {
  for (const roleName of roleNames) {
    const hasIt = await hasRoleInternal(roleName, options);
    if (hasIt) return true;
  }
  return false;
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
 * @param permissionNames - Array of permissions to check
 * @param options - Optional supabase client and userId to avoid redundant auth calls
 */
export async function hasAnyPermission(
  permissionNames: string[],
  options?: { supabase?: Awaited<ReturnType<typeof createClient>>; userId?: string }
): Promise<boolean> {
  // Use hasPermission for each check to leverage the optimized path
  for (const permissionName of permissionNames) {
    const hasIt = await hasPermission(permissionName, options);
    if (hasIt) return true;
  }
  return false;
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
  USERS_MANAGE: "users.manage",

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

  // Contracts
  CONTRACTS_CREATE: "contracts.create",
  CONTRACTS_READ: "contracts.read",
  CONTRACTS_UPDATE: "contracts.update",
  CONTRACTS_DELETE: "contracts.delete",

  // Settings
  SETTINGS_READ: "settings.read",
  SETTINGS_UPDATE: "settings.update",
  SETTINGS_MANAGE: "settings.manage",

  // Marketing
  MARKETING_CAMPAIGNS_CREATE: "marketing_campaigns.create",
  MARKETING_CAMPAIGNS_READ: "marketing_campaigns.read",
  MARKETING_CAMPAIGNS_UPDATE: "marketing_campaigns.update",
  MARKETING_CAMPAIGNS_DELETE: "marketing_campaigns.delete",
  MARKETING_LEADS_CREATE: "marketing_leads.create",
  MARKETING_LEADS_READ: "marketing_leads.read",
  MARKETING_LEADS_UPDATE: "marketing_leads.update",
  MARKETING_LEADS_DELETE: "marketing_leads.delete",
  MARKETING_CONTENT_CREATE: "marketing_content.create",
  MARKETING_CONTENT_READ: "marketing_content.read",
  MARKETING_CONTENT_UPDATE: "marketing_content.update",
  MARKETING_CONTENT_DELETE: "marketing_content.delete",
  MARKETING_VIEW_CLIENT: "marketing.view_client",
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
