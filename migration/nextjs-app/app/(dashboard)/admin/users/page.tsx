import { createClient } from "@/lib/supabase/server";
import { hasPermission } from "@/lib/rbac/permissions";
import { redirect } from "next/navigation";
import { UserManagement } from "@/components/admin/users/user-management";

export default async function AdminUsersPage() {
  const supabase = createClient();

  // Get authenticated user
  const {
    data: { user },
  } = await supabase.auth.getUser();

  if (!user) {
    redirect("/login");
  }

  // Check permission
  const canManageUsers = await hasPermission("users.manage");
  if (!canManageUsers) {
    redirect("/admin");
  }

  // Fetch users with their roles and client info
  const { data: users } = await supabase
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
    .order("created_at", { ascending: false });

  // Fetch available roles
  const { data: roles } = await supabase.from("roles").select("*").order("name");

  // Fetch available clients
  const { data: clients } = await supabase.from("clients").select("id, company_name").order("company_name");

  return (
    <div className="container mx-auto py-6">
      <UserManagement initialUsers={users || []} roles={roles || []} clients={clients || []} />
    </div>
  );
}
