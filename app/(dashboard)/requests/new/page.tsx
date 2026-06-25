import { createAdminClientIfAvailable, createClient } from "@/lib/supabase/server";
import { RequestForm } from "@/components/requests/request-form";
import { collectRoleNames } from "@/lib/rbac/role-row-utils";
import { getAssignableUsersForClient } from "@/lib/users/assignable-users";
import { redirect } from "next/navigation";

export const metadata = {
  title: "New Request | KRE8IV",
  description: "Create a new service request",
};

interface SearchParams {
  client_id?: string;
}

/**
 * New request page (Server Component)
 *
 * Fetches clients for the dropdown and pre-selects if client_id provided.
 */
export default async function NewRequestPage({ searchParams }: { searchParams: Promise<SearchParams> }) {
  const resolvedSearchParams = await searchParams;
  const supabase = await createClient();

  const {
    data: { user },
  } = await supabase.auth.getUser();

  if (!user) {
    redirect("/login");
  }

  const { data: dbUser } = await supabase.from("users").select("id, client_id, is_super_admin").eq("id", user.id).maybeSingle();
  const metadataRole = String(user.user_metadata?.role || user.user_metadata?.app_role || "").toLowerCase();
  let canAssignUsers = Boolean(
    dbUser?.is_super_admin ||
      user.user_metadata?.is_super_admin === true ||
      metadataRole === "admin" ||
      metadataRole === "super_admin",
  );

  if (!canAssignUsers) {
    const { data: roleRows } = await supabase
      .from("user_roles")
      .select("role:roles(name)")
      .eq("user_id", user.id);
    const roleNames = collectRoleNames(roleRows);
    canAssignUsers = roleNames.has("admin") || roleNames.has("super_admin");
  }

  const defaultClientId = canAssignUsers
    ? resolvedSearchParams.client_id
    : dbUser?.client_id || undefined;

  // Fetch clients for dropdown
  const clientsQuery = supabase.from("clients").select("id, company_name").eq("status", "active").order("company_name");
  const { data: clients } = canAssignUsers
    ? await clientsQuery
    : await clientsQuery.eq("id", dbUser?.client_id || "00000000-0000-0000-0000-000000000000");

  const assignableDbClient =
    canAssignUsers ? (createAdminClientIfAvailable() ?? supabase) : supabase;
  const assignableUsers = canAssignUsers
    ? [
        ...new Map(
          (
            await Promise.all(
              (clients || []).map((client) =>
                getAssignableUsersForClient(assignableDbClient, client.id),
              ),
            )
          )
            .flat()
            .map((assignableUser) => [assignableUser.id, assignableUser] as const),
        ).values(),
      ]
    : [];

  return (
    <div className="flex flex-col gap-8 p-8 max-w-4xl mx-auto">
      <div>
        <h1 className="text-3xl font-bold tracking-tight">New Request</h1>
        <p className="text-muted-foreground">Create a new service request</p>
      </div>

      <RequestForm
        clients={clients || []}
        assignableUsers={assignableUsers}
        preselectedClientId={defaultClientId}
        canAssignUsers={canAssignUsers}
        canSelectClient={canAssignUsers}
        canManageStatus={canAssignUsers}
      />
    </div>
  );
}
