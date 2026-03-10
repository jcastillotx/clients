import { createClient } from "@/lib/supabase/server";
import { createAdminClientIfAvailable } from "@/lib/supabase/server";
import { notFound } from "next/navigation";
import { RequestDetail } from "@/components/requests/request-detail";
import { RequestComments } from "@/components/requests/request-comments";
import { RequestRealtime } from "@/components/requests/request-realtime";
import { isAdminUser } from "@/lib/rbac/check";

interface RequestDetailPageProps {
  params: Promise<{
    id: string;
  }>;
}

function normalizeRelation<T>(value: T | T[] | null | undefined): T | null {
  if (Array.isArray(value)) {
    return value[0] ?? null;
  }

  return value ?? null;
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

  // Parallel fetch: user profile + role assignments
  const [{ data: dbUser }, { data: roleRows }] = await Promise.all([
    user
      ? supabase.from("users").select("id, is_super_admin").eq("id", user.id).maybeSingle()
      : Promise.resolve({ data: null }),
    user
      ? supabase.from("user_roles").select("role:roles(name)").eq("user_id", user.id)
      : Promise.resolve({ data: null }),
  ]);

  // Staff can manage workflow (update status, assign) but NOT delete
  const canManageWorkflow = isAdminUser(user, dbUser, roleRows, ["staff"]);
  // Only true admins / super-admins can delete
  const canDelete = isAdminUser(user, dbUser, roleRows);

  // Admin client bypasses RLS so admins can:
  //   - read requests across all clients (not just their own client_id)
  //   - read all comments on those requests
  //   - list all users for the assignee picker
  // Regular users fall back to the session-scoped client which enforces RLS.
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

  const normalizedRequest = {
    ...request,
    client: normalizeRelation((request as any).client),
    created_by_user: normalizeRelation((request as any).created_by_user),
    assigned_user: normalizeRelation((request as any).assigned_user),
  };

  // Parallel fetch: comments + assignable users
  const [{ data: comments }, { data: assignableUsers }] = await Promise.all([
    // Admin client used so admins can see comments across all client requests
    dbClient
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
      .order("created_at", { ascending: true }),
    // Admin client used so admins can see all users across clients for assignment
    canManageWorkflow
      ? dbClient.from("users").select("id, name, email").is("deleted_at", null).order("name")
      : Promise.resolve({ data: [] }),
  ]);

  return (
    <div className="flex flex-col gap-8 p-8">
      {/* Real-time subscription component (doesn't render UI) */}
      <RequestRealtime requestId={id} />

      {/* Request details */}
      <RequestDetail request={normalizedRequest as any} assignableUsers={assignableUsers || []} canManageWorkflow={canManageWorkflow} canDelete={canDelete} />

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
