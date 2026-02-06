import { createClient } from "@/lib/supabase/server";
import { redirect } from "next/navigation";
import { hasPermission } from "@/lib/rbac/permissions";
import { RoleList } from "@/components/admin/roles/role-list";

export const metadata = {
  title: "Roles & Permissions | Admin",
  description: "Manage user roles and permissions",
};

export default async function RolesPage() {
  const supabase = await createClient();

  const {
    data: { user },
  } = await supabase.auth.getUser();

  if (!user) {
    redirect("/login");
  }

  // Check permission
  const canManageRoles = await hasPermission("roles.read");
  if (!canManageRoles) {
    redirect("/dashboard");
  }

  // Fetch roles
  const { data: roles } = await supabase
    .from("roles")
    .select(
      `
      *,
      role_permissions(
        permission:permissions(*)
      ),
      user_roles(count)
    `,
    )
    .order("name");

  // Fetch permissions
  const { data: permissions } = await supabase.from("permissions").select("*").order("resource").order("action");

  return (
    <div className="flex flex-col gap-8 p-8">
      <div>
        <h1 className="text-3xl font-bold tracking-tight">Roles & Permissions</h1>
        <p className="text-muted-foreground">Manage user roles and assign permissions</p>
      </div>

      <RoleList initialRoles={roles || []} permissions={permissions || []} />
    </div>
  );
}
