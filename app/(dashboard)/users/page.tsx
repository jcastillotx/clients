import { createAdminClientIfAvailable, createClient } from "@/lib/supabase/server";
import { hasAnyRole, hasPermission, Permissions, Roles } from "@/lib/rbac/permissions";
import { redirect } from "next/navigation";
import { UserManagement } from "@/components/admin/users/user-management";
import { syncMissingAuthUsers } from "@/lib/supabase/user-profile-sync";

export const metadata = {
  title: "Users | KRE8IV",
  description: "Manage platform users and organization assignments",
};

type UsersQueryStrategy = {
  includeRelations: boolean;
  includeDeletedFilter: boolean;
  orderByCreatedAt: boolean;
  label: string;
};

type UserManagementUser = {
  id: string;
  name: string;
  email: string;
  phone: string | null;
  avatar: string | null;
  client_id: string | null;
  is_active: boolean;
  status: string;
  last_login_at: string | null;
  created_at: string;
  client?: {
    id: string;
    company_name: string;
  } | null;
  user_roles?: Array<{
    role: {
      id: string;
      name: string;
      description: string | null;
    };
  }>;
};

export default async function UsersPage() {
  const supabase = await createClient();

  const {
    data: { user },
  } = await supabase.auth.getUser();

  if (!user) {
    redirect("/login");
  }

  const metadataRole = String(user.user_metadata?.role ?? user.user_metadata?.app_role ?? "").toLowerCase();
  const isAdminMetadataRole =
    user.user_metadata?.is_super_admin === true || metadataRole === Roles.SUPER_ADMIN || metadataRole === Roles.ADMIN;
  const hasManagementMetadataRole = isAdminMetadataRole || metadataRole === Roles.ACCOUNT_MANAGER;

  const accessOptions = { supabase, userId: user.id };
  const [canManageUsers, canCreateUsers, canAssignRoles, isManagementRole] = await Promise.all([
    hasPermission(Permissions.USERS_MANAGE, accessOptions),
    hasPermission(Permissions.USERS_CREATE, accessOptions),
    hasPermission(Permissions.USERS_ASSIGN_ROLES, accessOptions),
    hasAnyRole([Roles.SUPER_ADMIN, Roles.ADMIN, Roles.ACCOUNT_MANAGER], accessOptions),
  ]);

  const hasManagementAccess = isManagementRole || hasManagementMetadataRole;
  const canAccessUserManagement = canManageUsers || canCreateUsers || hasManagementAccess;
  if (!canAccessUserManagement) {
    redirect("/dashboard");
  }

  // Prefer service-role reads for user admin screens when available. In environments
  // with partial migrations, this also avoids RLS edge cases while still requiring access checks above.
  const adminClient = createAdminClientIfAvailable();
  const dbClient = adminClient ?? supabase;

  if (adminClient) {
    await syncMissingAuthUsers(adminClient);
  } else {
    console.warn("Service-role Supabase key missing; using session client for /users");
  }

  const usersSelectWithRelations = `
    *,
    client:clients(id, company_name),
    user_roles(
      role:roles(id, name, description)
    )
  `;

  const runUsersQuery = async (strategy: UsersQueryStrategy) => {
    let query = dbClient.from("users").select(strategy.includeRelations ? usersSelectWithRelations : "*");
    if (strategy.includeDeletedFilter) {
      query = query.is("deleted_at", null);
    }
    query = strategy.orderByCreatedAt
      ? query.order("created_at", { ascending: false })
      : query.order("id", { ascending: false });
    return query;
  };

  const userQueryStrategies: UsersQueryStrategy[] = [
    {
      includeRelations: true,
      includeDeletedFilter: true,
      orderByCreatedAt: true,
      label: "relations + deleted filter + created_at order",
    },
    {
      includeRelations: true,
      includeDeletedFilter: false,
      orderByCreatedAt: true,
      label: "relations + created_at order",
    },
    {
      includeRelations: true,
      includeDeletedFilter: false,
      orderByCreatedAt: false,
      label: "relations + id order",
    },
    {
      includeRelations: false,
      includeDeletedFilter: false,
      orderByCreatedAt: false,
      label: "plain users fallback",
    },
  ];

  let users: UserManagementUser[] = [];
  let usersError: unknown = null;
  for (const strategy of userQueryStrategies) {
    const result = await runUsersQuery(strategy);
    if (!result.error) {
      users = ((result.data || []) as unknown) as UserManagementUser[];
      usersError = null;
      break;
    }
    usersError = result.error;
    console.warn(`Users query strategy failed (${strategy.label}); trying fallback`, result.error);
  }

  if (usersError) {
    console.error("Unable to load users after all fallback strategies", usersError);
  }

  const [rolesResult, clientsResult] = await Promise.all([
    dbClient.from("roles").select("*").order("name"),
    dbClient.from("clients").select("id, company_name").order("company_name"),
  ]);

  if (rolesResult.error) {
    console.warn("Failed to load roles for user management; continuing with empty roles", rolesResult.error);
  }
  if (clientsResult.error) {
    console.warn("Failed to load clients for user management; continuing with empty clients", clientsResult.error);
  }

  const roles = rolesResult.data || [];
  const clients = clientsResult.data || [];

  return (
    <div className="container mx-auto py-6">
      <UserManagement
        initialUsers={users}
        roles={roles}
        clients={clients}
        canAssignRoles={canAssignRoles || canManageUsers || isAdminMetadataRole}
      />
    </div>
  );
}
