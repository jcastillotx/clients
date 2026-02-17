import { createAdminClient, createClient } from "@/lib/supabase/server";
import { hasAnyRole, hasPermission, Permissions, Roles } from "@/lib/rbac/permissions";
import { redirect } from "next/navigation";
import { UserManagement } from "@/components/admin/users/user-management";

export const metadata = {
  title: "Users | KRE8IV",
  description: "Manage platform users and organization assignments",
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

  const dbClient = hasManagementAccess ? createAdminClient() : supabase;

  const [{ data: users }, { data: roles }, { data: clients }] = await Promise.all([
    dbClient
      .from("users")
      .select(
        `
        *,
        client:clients(id, company_name),
        user_roles(
          role:roles(id, name, description)
        )
      `,
      )
      .is("deleted_at", null)
      .order("created_at", { ascending: false }),
    dbClient.from("roles").select("*").order("name"),
    dbClient.from("clients").select("id, company_name").order("company_name"),
  ]);

  return (
    <div className="container mx-auto py-6">
      <UserManagement
        initialUsers={users || []}
        roles={roles || []}
        clients={clients || []}
        canAssignRoles={canAssignRoles || canManageUsers || isAdminMetadataRole}
      />
    </div>
  );
}
