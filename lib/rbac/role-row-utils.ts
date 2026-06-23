export type RoleJoinRow = {
  role?: { name?: string | null } | Array<{ name?: string | null }> | null;
};

export function roleNameFromJoinRow(row: RoleJoinRow): string {
  const role = row.role;
  const roleName = Array.isArray(role) ? role[0]?.name : role?.name;

  return String(roleName || "").toLowerCase();
}

export function collectRoleNames(
  rows: RoleJoinRow[] | null | undefined,
): Set<string> {
  const names = new Set<string>();

  for (const row of rows || []) {
    const name = roleNameFromJoinRow(row);
    if (name) {
      names.add(name);
    }
  }

  return names;
}
