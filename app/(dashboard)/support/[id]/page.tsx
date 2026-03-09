import { createAdminClientIfAvailable, createClient } from "@/lib/supabase/server";
import { SupportTicketDetail } from "@/components/support/ticket-detail";
import { SupportTicketComments } from "@/components/support/ticket-comments";
import { notFound, redirect } from "next/navigation";
import { hasAnyRole } from "@/lib/rbac/permissions";

type CommentUser = {
  id: string;
  name: string;
  email: string;
  avatar?: string | null;
};

type TicketComment = {
  id: string;
  comment: string;
  is_internal: boolean;
  created_at: string;
  user: CommentUser | CommentUser[] | null;
};

interface PageProps {
  params: Promise<{
    id: string;
  }>;
}

/**
 * Support Ticket Detail page (Server Component)
 *
 * Displays full ticket details with timeline, SLA tracking, and comments.
 */
export default async function SupportTicketDetailPage({ params }: PageProps) {
  const { id } = await params;
  const supabase = await createClient();

  // Check authentication
  const {
    data: { user },
  } = await supabase.auth.getUser();

  if (!user) {
    redirect("/login");
  }

  const isStaff = await hasAnyRole(["super_admin", "admin", "account_manager", "staff"], { supabase, userId: user.id });
  const adminClient = isStaff ? createAdminClientIfAvailable() : null;
  const dbClient = adminClient ?? supabase;

  // Fetch ticket with relations
  const { data: ticket, error: ticketError } = await dbClient
    .from("support_tickets")
    .select(
      `
      *,
      client:clients(id, company_name, email),
      creator:users!support_tickets_created_by_fkey(id, name, email, avatar),
      assigned_user:users!support_tickets_assigned_to_fkey(id, name, email, avatar)
    `,
    )
    .eq("id", id)
    .single();

  if (ticketError || !ticket) {
    notFound();
  }

  // Fetch comments with user info
  const commentsClient = createAdminClientIfAvailable() ?? dbClient;

  const { data: comments } = await commentsClient
    .from("support_ticket_comments")
    .select(
      `
      *,
      user:users(id, name, email, avatar)
    `,
    )
    .eq("support_ticket_id", id)
    .order("created_at", { ascending: true });

  const sanitizedComments = ((comments || []) as TicketComment[]).map((comment) => {
    const rawUser = Array.isArray(comment.user) ? comment.user[0] : comment.user;

    return {
      ...comment,
      user: rawUser
        ? {
            id: rawUser.id,
            name: rawUser.name || "Unknown User",
            email: rawUser.email || "",
            avatar: rawUser.avatar ?? undefined,
          }
        : {
            id: "unknown-user",
            name: "Unknown User",
            email: "",
            avatar: undefined,
          },
    };
  });

  // Fetch staff users for assignment updates
  const { data: staffUsers } = await dbClient
    .from("users")
    .select("id, name, email")
    .in("role", ["staff", "admin"])
    .order("name");

  return (
    <div className="container mx-auto py-8 max-w-6xl">
      <SupportTicketDetail ticket={ticket} staffUsers={staffUsers || []} />

      <div className="mt-8">
        <SupportTicketComments
          ticketId={id}
          initialComments={sanitizedComments}
          currentUserId={user.id}
          isStaff={isStaff}
        />
      </div>
    </div>
  );
}
