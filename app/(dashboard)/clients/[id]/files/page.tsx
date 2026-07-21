import { notFound, redirect } from "next/navigation";
import { ClientWorkspaceNav } from "@/components/clients/client-workspace-nav";
import { DocumentLibrary } from "@/components/documents/document-library";
import { resolveStaffAccess } from "@/lib/api/resolve-staff-access";
import { hasPermission } from "@/lib/rbac/permissions";
import {
  createAdminClientIfAvailable,
  createClient,
} from "@/lib/supabase/server";

interface ClientFilesPageProps {
  params: Promise<{ id: string }>;
}

export default async function ClientFilesPage({
  params,
}: ClientFilesPageProps) {
  const { id } = await params;
  const access = await resolveStaffAccess();

  if (!access) {
    redirect("/login");
  }

  if (!access.isStaff) {
    redirect("/dashboard");
  }

  const supabase = await createClient();
  const dbClient = createAdminClientIfAvailable() ?? supabase;

  const [{ data: client }, { data: documents }, { data: folders }] =
    await Promise.all([
      dbClient
        .from("clients")
        .select("id, company_name")
        .eq("id", id)
        .is("deleted_at", null)
        .maybeSingle(),
      dbClient
        .from("documents")
        .select(
          `
          *,
          client:clients(id, company_name),
          uploader:users!uploaded_by(id, name, email)
        `,
        )
        .eq("client_id", id)
        .is("deleted_at", null)
        .eq("is_latest_version", true)
        .order("created_at", { ascending: false })
        .limit(100),
      dbClient.from("folders").select("*").eq("client_id", id).order("name"),
    ]);

  if (!client) {
    notFound();
  }

  const canUpload =
    access.isStaff ||
    (await hasPermission("documents.create", {
      supabase,
      userId: access.userId,
    }));

  return (
    <div className="flex flex-col gap-8 p-8">
      <ClientWorkspaceNav clientId={id} companyName={client.company_name} />
      <DocumentLibrary
        initialDocuments={documents || []}
        initialFolders={folders || []}
        clients={[client]}
        canUpload={canUpload}
        initialClientId={id}
        title="Client files"
        description={`Keep contracts, deliverables, approvals, and source files organized for ${client.company_name}.`}
        lockClient
      />
    </div>
  );
}
