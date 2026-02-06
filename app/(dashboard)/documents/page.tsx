import { createClient } from "@/lib/supabase/server";
import { hasPermission } from "@/lib/rbac/permissions";
import { redirect } from "next/navigation";
import { DocumentLibrary } from "@/components/documents/document-library";

export default async function DocumentsPage({
  searchParams,
}: {
  searchParams: Promise<{ clientId?: string; requestId?: string }>;
}) {
  const resolvedSearchParams = await searchParams;
  const supabase = createClient();

  // Get authenticated user
  const {
    data: { user },
  } = await supabase.auth.getUser();

  if (!user) {
    redirect("/login");
  }

  const canView = await hasPermission("documents.view");
  if (!canView) {
    redirect("/dashboard");
  }

  // Fetch initial documents
  let query = supabase
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
  }

  if (resolvedSearchParams.requestId) {
    query = query.eq("request_id", resolvedSearchParams.requestId);
  }

  const { data: documents } = await query;

  // Fetch clients for filter dropdown
  const { data: clients } = await supabase.from("clients").select("id, company_name").order("company_name");

  const canUpload = await hasPermission("documents.create");

  return (
    <div className="container mx-auto py-6">
      <DocumentLibrary
        initialDocuments={documents || []}
        clients={clients || []}
        canUpload={canUpload}
        initialClientId={resolvedSearchParams.clientId}
        initialRequestId={resolvedSearchParams.requestId}
      />
    </div>
  );
}
