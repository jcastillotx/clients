import { createAdminClientIfAvailable, createClient } from "@/lib/supabase/server";
import { hasPermission } from "@/lib/rbac/permissions";
import { redirect } from "next/navigation";
import { DocumentLibrary } from "@/components/documents/document-library";
import { resolveRouteAccess } from "@/lib/auth/route-access";

export default async function DocumentsPage({
  searchParams,
}: {
  searchParams: Promise<{ clientId?: string; requestId?: string }>;
}) {
  const resolvedSearchParams = await searchParams;
  const supabase = await createClient();

  // Get authenticated user
  const {
    data: { user },
  } = await supabase.auth.getUser();

  if (!user) {
    redirect("/login");
  }

  const access = await resolveRouteAccess(supabase, user);
  const adminClient = access.isAdmin ? createAdminClientIfAvailable() : null;
  const dbClient = adminClient ?? supabase;

  // Check permission (fallback to true if RBAC not set up)
  const canView = await hasPermission("documents.read").catch(() => true);
  if (!canView) {
    redirect("/dashboard");
  }

  // Fetch initial documents
  let query = dbClient
    .from("documents")
    .select(
      `
      *,
      client:clients(id, company_name),
      uploader:users!uploaded_by(id, name, email)
    `,
    )
    .is("deleted_at", null)
    .eq("is_latest_version", true)
    .order("created_at", { ascending: false })
    .limit(50);

  if (resolvedSearchParams.clientId) {
    query = query.eq("client_id", resolvedSearchParams.clientId);
  } else if (!access.isAdmin && access.clientId) {
    query = query.eq("client_id", access.clientId);
  }

  if (resolvedSearchParams.requestId) {
    query = query.eq("request_id", resolvedSearchParams.requestId);
  }

  const { data: documents } = await query;

  // Fetch folders
  let foldersQuery = dbClient.from("folders").select("*").order("name");
  if (resolvedSearchParams.clientId) {
    foldersQuery = foldersQuery.eq("client_id", resolvedSearchParams.clientId);
  } else if (!access.isAdmin && access.clientId) {
    foldersQuery = foldersQuery.eq("client_id", access.clientId);
  }
  const { data: folders } = await foldersQuery;

  // Fetch clients for filter dropdown
  let clientsQuery = dbClient.from("clients").select("id, company_name").order("company_name");
  if (!access.isAdmin && access.clientId) {
    clientsQuery = clientsQuery.eq("id", access.clientId);
  }
  const { data: clients } = await clientsQuery;

  const canUpload = await hasPermission("documents.create");

  return (
    <div className="container mx-auto py-6">
      <DocumentLibrary
        initialDocuments={documents || []}
        initialFolders={folders || []}
        clients={clients || []}
        canUpload={canUpload}
        initialClientId={resolvedSearchParams.clientId}
        initialRequestId={resolvedSearchParams.requestId}
      />
    </div>
  );
}
