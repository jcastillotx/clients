import { createClient } from "@/lib/supabase/server";
import { createAdminClientIfAvailable } from "@/lib/supabase/server";
import { notFound } from "next/navigation";
import { RequestDetail } from "@/components/requests/request-detail";
import { RequestComments } from "@/components/requests/request-comments";
import { RequestRealtime } from "@/components/requests/request-realtime";

interface RequestDetailPageProps {
  params: Promise<{
    id: string;
  }>;
}

/**
 * Request detail page (Server Component)
 *
 * Fetches request with all related data on the server.
 * Real-time updates handled by client components.
 */
export default async function RequestDetailPage({ params }: RequestDetailPageProps) {
  const { id } = await params;
  const supabase = await createClient();

  const {
    data: { user },
  } = await supabase.auth.getUser();

  const { data: dbUser } = user
    ? await supabase.from("users").select("id, is_super_admin").eq("id", user.id).maybeSingle()
    : { data: null };

  const metadataRole = String(user?.user_metadata?.role || user?.user_metadata?.app_role || "").toLowerCase();
  let canManageWorkflow = Boolean(
    dbUser?.is_super_admin ||
      user?.user_metadata?.is_super_admin === true ||
      metadataRole === "admin" ||
      metadataRole === "super_admin" ||
      metadataRole === "staff",
  );

  if (!canManageWorkflow && user) {
    const { data: roleRows } = await supabase
      .from("user_roles")
      .select("role:roles(name)")
      .eq("user_id", user.id);
    canManageWorkflow = (roleRows || []).some((row: any) => {
      const roleName = String(row?.role?.name || row?.role?.[0]?.name || "").toLowerCase();
      return roleName === "admin" || roleName === "super_admin" || roleName === "staff";
    });
  }

  const adminClient = canManageWorkflow ? createAdminClientIfAvailable() : null;
  const dbClient = adminClient ?? supabase;

  // Fetch request with all related data
  const { data: request, error } = await dbClient
    .from("requests")
    .select(
      `
      *,
      client:clients(id, company_name, domain),
      created_by_user:users!requests_created_by_fkey(id, name, avatar),
      assigned_user:users!requests_assigned_to_fkey(id, name, avatar)
    `,
    )
    .eq("id", id)
    .single();

  if (error || !request) {
    notFound();
  }

  // Fetch initial comments separately for better data structure
  const { data: comments } = await dbClient
    .from("request_comments")
    .select(
      `
      id,
      content,
      created_at,
      updated_at,
      user:users(id, name, avatar)
    `,
    )
    .eq("request_id", id)
    .order("created_at", { ascending: true });

  const { data: assignableUsers } = canManageWorkflow
    ? await dbClient.from("users").select("id, name, email").is("deleted_at", null).order("name")
    : { data: [] };

  return (
    <div className="flex flex-col gap-8 p-8">
      {/* Real-time subscription component (doesn't render UI) */}
      <RequestRealtime requestId={id} />

      {/* Request details */}
      <RequestDetail request={request} assignableUsers={assignableUsers || []} canManageWorkflow={canManageWorkflow} />

      {/* Comments section with real-time updates */}
      <RequestComments
        requestId={id}
        initialComments={
          (comments || []).map((c: any) => ({
            ...c,
            user: Array.isArray(c.user) ? c.user[0] : c.user,
          })) as any
        }
      />
    </div>
  );
}
