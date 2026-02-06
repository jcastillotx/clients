import { createClient } from "@/lib/supabase/server";
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

  // Fetch request with all related data
  const { data: request, error } = await supabase
    .from("requests")
    .select(
      `
      *,
      client:clients(id, company_name, domain),
      created_by_user:users!requests_created_by_fkey(id, name, avatar),
      assigned_user:users!requests_assigned_to_fkey(id, name, avatar),
      request_comments(
        id,
        content,
        created_at,
        user:users(id, name, avatar)
      )
    `,
    )
    .eq("id", id)
    .single();

  if (error || !request) {
    notFound();
  }

  // Fetch initial comments separately for better data structure
  const { data: comments } = await supabase
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

  return (
    <div className="flex flex-col gap-8 p-8">
      {/* Real-time subscription component (doesn't render UI) */}
      <RequestRealtime requestId={id} />

      {/* Request details */}
      <RequestDetail request={request} />

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
