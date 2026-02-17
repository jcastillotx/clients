import { createAdminClientIfAvailable, createClient } from "@/lib/supabase/server";
import { ClientForm } from "@/components/clients/client-form";
import { redirect } from "next/navigation";
import { hasAnyRole, hasPermission, Permissions, Roles } from "@/lib/rbac/permissions";

export const metadata = {
  title: "New Client | KRE8IV",
  description: "Create a new client",
};

/**
 * New client page (Server Component)
 *
 * Provides form for creating a new client.
 */
export default async function NewClientPage() {
  const supabase = await createClient();

  const {
    data: { user },
  } = await supabase.auth.getUser();

  if (!user) {
    redirect("/login");
  }

  const metadataRole = String(user.user_metadata?.role ?? user.user_metadata?.app_role ?? "").toLowerCase();
  const hasManagementMetadataRole =
    user.user_metadata?.is_super_admin === true ||
    metadataRole === Roles.SUPER_ADMIN ||
    metadataRole === Roles.ADMIN ||
    metadataRole === Roles.ACCOUNT_MANAGER;

  const accessOptions = { supabase, userId: user.id };
  const [canCreateClient, hasManagementRoleDb] = await Promise.all([
    hasPermission(Permissions.CLIENTS_CREATE, accessOptions),
    hasAnyRole([Roles.SUPER_ADMIN, Roles.ADMIN, Roles.ACCOUNT_MANAGER], accessOptions),
  ]);
  const hasManagementRole = hasManagementRoleDb || hasManagementMetadataRole;

  if (!(canCreateClient || hasManagementRole)) {
    redirect("/clients");
  }

  const adminClient = hasManagementRole ? createAdminClientIfAvailable() : null;
  const dbClient = adminClient ?? supabase;

  if (hasManagementRole && !adminClient) {
    console.warn("Service-role Supabase key missing; falling back to session client for /clients/new");
  }

  // Fetch users for primary contact selection
  const { data: users, error: usersError } = await dbClient.from("users").select("id, name, email").order("name");

  if (usersError) {
    console.error("Error fetching users for client creation:", usersError);
  }

  return (
    <div className="flex flex-col gap-8 p-8 max-w-4xl mx-auto">
      <div>
        <h1 className="text-3xl font-bold tracking-tight">New Client</h1>
        <p className="text-muted-foreground">Create a new client account</p>
      </div>

      <ClientForm users={users ?? []} />
    </div>
  );
}
