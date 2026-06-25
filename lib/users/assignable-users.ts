import { collectRoleNames, type RoleJoinRow } from "@/lib/rbac/role-row-utils";

const PLATFORM_ASSIGNABLE_ROLES = new Set([
  "admin",
  "super_admin",
  "staff",
  "account_manager",
]);

type QueryBuilder<T> = PromiseLike<{ data: T | null; error: unknown }>;

type SupabaseLike = {
  from: (table: string) => {
    select: (columns: string) => unknown;
  };
};

type UserQueryRow = {
  id: string;
  name: string | null;
  email: string | null;
  client_id: string | null;
  is_super_admin: boolean | null;
};

type UserRoleQueryRow = RoleJoinRow & {
  user_id?: string | null;
};

export type AssignableUser = {
  id: string;
  name: string | null;
  email: string | null;
  client_id: string | null;
  is_platform_staff: boolean;
};

function asQuery<T>(value: unknown): QueryBuilder<T> {
  return value as QueryBuilder<T>;
}

function readPlatformUserIds(roleRows: UserRoleQueryRow[] | null | undefined) {
  const ids = new Set<string>();

  for (const row of roleRows || []) {
    if (!row.user_id) {
      continue;
    }
    const roleNames = collectRoleNames([row]);
    if ([...roleNames].some((roleName) => PLATFORM_ASSIGNABLE_ROLES.has(roleName))) {
      ids.add(row.user_id);
    }
  }

  return ids;
}

function isPlatformAssignableUser(row: UserQueryRow, platformUserIds: Set<string>) {
  return row.is_super_admin === true || platformUserIds.has(row.id);
}

export async function getAssignableUsersForClient(
  dbClient: SupabaseLike,
  clientId: string,
): Promise<AssignableUser[]> {
  const usersQuery = dbClient
    .from("users")
    .select("id, name, email, client_id, is_super_admin");
  const rolesQuery = dbClient
    .from("user_roles")
    .select("user_id, role:roles(name)");

  const [{ data: userRows, error: usersError }, { data: roleRows, error: rolesError }] =
    await Promise.all([
      asQuery<UserQueryRow[]>(
        (usersQuery as {
          is: (column: string, value: null) => {
            eq: (column: string, value: boolean) => { order: (column: string) => unknown };
          };
        })
          .is("deleted_at", null)
          .eq("is_active", true)
          .order("name"),
      ),
      asQuery<UserRoleQueryRow[]>(rolesQuery),
    ]);

  if (usersError) {
    throw usersError;
  }
  if (rolesError) {
    throw rolesError;
  }

  const platformUserIds = readPlatformUserIds(roleRows);
  const assignableById = new Map<string, AssignableUser>();

  for (const row of userRows || []) {
    const isPlatformStaff = isPlatformAssignableUser(row, platformUserIds);
    if (row.client_id !== clientId && !isPlatformStaff) {
      continue;
    }

    assignableById.set(row.id, {
      id: row.id,
      name: row.name,
      email: row.email,
      client_id: row.client_id,
      is_platform_staff: isPlatformStaff,
    });
  }

  return [...assignableById.values()];
}

export async function isUserAssignableToClient(
  dbClient: SupabaseLike,
  userId: string,
  clientId: string,
): Promise<boolean> {
  const { data: userRow, error: userError } = await asQuery<UserQueryRow>(
    (dbClient
      .from("users")
      .select("id, name, email, client_id, is_super_admin") as {
      eq: (column: string, value: string | boolean) => {
        eq: (column: string, value: string | boolean) => {
          is: (column: string, value: null) => {
            maybeSingle: () => unknown;
          };
        };
      };
    })
      .eq("id", userId)
      .eq("is_active", true)
      .is("deleted_at", null)
      .maybeSingle(),
  );

  if (userError) {
    throw userError;
  }
  if (!userRow) {
    return false;
  }
  if (userRow.client_id === clientId || userRow.is_super_admin === true) {
    return true;
  }

  const { data: roleRows, error: rolesError } = await asQuery<UserRoleQueryRow[]>(
    (dbClient
      .from("user_roles")
      .select("role:roles(name)") as {
      eq: (column: string, value: string) => unknown;
    }).eq("user_id", userId),
  );

  if (rolesError) {
    throw rolesError;
  }

  const roleNames = collectRoleNames(roleRows);
  return [...roleNames].some((roleName) => PLATFORM_ASSIGNABLE_ROLES.has(roleName));
}
