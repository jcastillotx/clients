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

type RoleOption = {
  id: string;
  name: string;
  description: string | null;
};

type ClientOption = {
  id: string;
  company_name: string;
};

type UserRoleJoinRow = {
  user_id: string;
  role: RoleOption | RoleOption[] | null;
};

type UserRoleRow = {
  user_id: string;
  role_id: string;
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

  const runUsersQuery = async (strategy: UsersQueryStrategy) => {
    let query = dbClient.from("users").select("*");
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
      includeDeletedFilter: true,
      orderByCreatedAt: true,
      label: "deleted filter + created_at order",
    },
    {
      includeDeletedFilter: false,
      orderByCreatedAt: true,
      label: "created_at order",
    },
    {
      includeDeletedFilter: false,
      orderByCreatedAt: false,
      label: "id order fallback",
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

  const roles: RoleOption[] = (rolesResult.data || []) as RoleOption[];
  const clients: ClientOption[] = (clientsResult.data || []) as ClientOption[];

  const clientsById = new Map(clients.map((client) => [client.id, client]));
  const rolesById = new Map(roles.map((role) => [role.id, role]));
  const userIds = users.map((u) => u.id);

  const userRolesByUserId = new Map<string, Array<{ role: RoleOption }>>();

  const addRoleToUser = (userId: string, role: RoleOption) => {
    if (!role?.id) return;
    const existing = userRolesByUserId.get(userId) ?? [];
    if (!existing.some((entry) => entry.role.id === role.id)) {
      existing.push({ role });
      userRolesByUserId.set(userId, existing);
    }
  };

  if (userIds.length > 0) {
    const joinedUserRolesResult = await dbClient
      .from("user_roles")
      .select("user_id, role:roles!user_roles_role_id_fkey(id, name, description)")
      .in("user_id", userIds);

    if (!joinedUserRolesResult.error) {
      const joinedRows = (joinedUserRolesResult.data || []) as UserRoleJoinRow[];
      for (const row of joinedRows) {
        const roleValue = Array.isArray(row.role) ? row.role[0] : row.role;
        if (roleValue && typeof roleValue.id === "string" && typeof roleValue.name === "string") {
          addRoleToUser(row.user_id, {
            id: roleValue.id,
            name: roleValue.name,
            description: roleValue.description ?? null,
          });
        }
      }
    } else {
      console.warn("Failed to join roles in users page; falling back to manual role hydration", joinedUserRolesResult.error);

      const plainUserRolesResult = await dbClient.from("user_roles").select("user_id, role_id").in("user_id", userIds);
      if (plainUserRolesResult.error) {
        console.warn("Failed to load user_roles in users page fallback", plainUserRolesResult.error);
      } else {
        const plainRows = (plainUserRolesResult.data || []) as UserRoleRow[];
        const missingRoleIds = Array.from(
          new Set(plainRows.map((row) => row.role_id).filter((roleId) => typeof roleId === "string" && !rolesById.has(roleId))),
        );

        if (missingRoleIds.length > 0) {
          const missingRolesResult = await dbClient
            .from("roles")
            .select("id, name, description")
            .in("id", missingRoleIds);
          if (missingRolesResult.error) {
            console.warn("Failed to hydrate missing roles in users page fallback", missingRolesResult.error);
          } else {
            for (const role of (missingRolesResult.data || []) as RoleOption[]) {
              rolesById.set(role.id, role);
            }
          }
        }

        for (const row of plainRows) {
          const role = rolesById.get(row.role_id);
          if (role) {
            addRoleToUser(row.user_id, role);
          }
        }
      }
    }
  }

  const enrichedUsers: UserManagementUser[] = users.map((userRow) => {
    const client =
      typeof userRow.client_id === "string" && userRow.client_id.length > 0 ? (clientsById.get(userRow.client_id) ?? null) : null;
    const userRoles = userRolesByUserId.get(userRow.id) ?? [];
    return {
      ...userRow,
      client,
      user_roles: userRoles,
    };
  });

  return (
    <div className="container mx-auto py-6">
      <UserManagement
        initialUsers={enrichedUsers}
        roles={roles}
        clients={clients}
        canAssignRoles={canAssignRoles || canManageUsers || isAdminMetadataRole}
        canSendPasswordResets={canManageUsers || isAdminMetadataRole}
      />
    </div>
  );
}
