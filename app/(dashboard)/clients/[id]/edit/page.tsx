import { createAdminClient, createClient } from "@/lib/supabase/server";
import { ClientForm } from "@/components/clients/client-form";
import { notFound, redirect } from "next/navigation";
import { hasAnyRole, hasPermission, Permissions, Roles } from "@/lib/rbac/permissions";

interface EditClientPageProps {
  params: Promise<{
    id: string;
  }>;
}

export const metadata = {
  title: "Edit Client | KRE8IV",
  description: "Edit client details",
};

/**
 * Edit client page (Server Component)
 *
 * Fetches existing client data and provides edit form.
 */
export default async function EditClientPage({ params }: EditClientPageProps) {
  const { id } = await params;
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
  const [canUpdateClient, hasManagementRoleDb] = await Promise.all([
    hasPermission(Permissions.CLIENTS_UPDATE, accessOptions),
    hasAnyRole([Roles.SUPER_ADMIN, Roles.ADMIN, Roles.ACCOUNT_MANAGER], accessOptions),
  ]);
  const hasManagementRole = hasManagementRoleDb || hasManagementMetadataRole;

  if (!(canUpdateClient || hasManagementRole)) {
    redirect(`/clients/${id}`);
  }

  const dbClient = hasManagementRole ? createAdminClient() : supabase;

  // Fetch client data
  const { data: client, error } = await dbClient.from("clients").select("*").eq("id", id).single();

  if (error || !client) {
    notFound();
  }

  // Fetch users for primary contact selection
  const { data: users } = await dbClient.from("users").select("id, name, email").order("name");

  return (
    <div className="flex flex-col gap-8 p-8 max-w-4xl mx-auto">
      <div>
        <h1 className="text-3xl font-bold tracking-tight">Edit Client</h1>
        <p className="text-muted-foreground">Update {client.company_name} details</p>
      </div>

      <ClientForm users={users || []} initialData={client} />
    </div>
  );
}
