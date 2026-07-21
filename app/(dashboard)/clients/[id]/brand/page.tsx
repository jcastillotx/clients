import { notFound, redirect } from "next/navigation";
import { ClientBrandGuideWorkspace } from "@/components/brand/client-brand-guide-workspace";
import type { ClientBrandDocument } from "@/components/brand/client-brand-guide-preview";
import { ClientWorkspaceNav } from "@/components/clients/client-workspace-nav";
import { resolveStaffAccess } from "@/lib/api/resolve-staff-access";
import { parseClientBrandGuideContent } from "@/lib/brand/client-brand-guide";
import {
  createAdminClientIfAvailable,
  createClient,
} from "@/lib/supabase/server";

interface ClientBrandPageProps {
  params: Promise<{ id: string }>;
}

interface GuideRow {
  id: string;
  status: "draft" | "published";
  meta: unknown;
}

export default async function ClientBrandPage({
  params,
}: ClientBrandPageProps) {
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

  const [{ data: client }, { data: guide }, { data: documents }] =
    await Promise.all([
      dbClient
        .from("clients")
        .select("id, company_name, logo_url")
        .eq("id", id)
        .is("deleted_at", null)
        .maybeSingle(),
      dbClient
        .from("brand_guides")
        .select("id, status, meta")
        .eq("client_id", id)
        .order("updated_at", { ascending: false })
        .limit(1)
        .maybeSingle(),
      dbClient
        .from("documents")
        .select("id, name, file_name, file_size, mime_type, tags, created_at")
        .eq("client_id", id)
        .contains("tags", ["brand"])
        .is("deleted_at", null)
        .eq("is_latest_version", true)
        .order("created_at", { ascending: false }),
    ]);

  if (!client) {
    notFound();
  }

  const typedGuide = guide as GuideRow | null;
  const content = parseClientBrandGuideContent(
    typedGuide?.meta,
    client.company_name,
    client.logo_url,
  );
  const guideStatus =
    typedGuide?.status === "published" ? "published" : "draft";

  return (
    <div className="flex flex-col gap-8 p-8">
      <ClientWorkspaceNav clientId={id} companyName={client.company_name} />
      <ClientBrandGuideWorkspace
        client={{ id: client.id, company_name: client.company_name }}
        initialGuideId={typedGuide?.id ?? null}
        initialContent={content}
        initialStatus={guideStatus}
        initialDocuments={(documents || []) as ClientBrandDocument[]}
      />
    </div>
  );
}
