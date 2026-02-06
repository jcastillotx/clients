import { createClient } from "@/lib/supabase/server";
import { SupportTicketDetail } from "@/components/support/ticket-detail";
import { SupportTicketComments } from "@/components/support/ticket-comments";
import { notFound, redirect } from "next/navigation";

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
  const supabase = createClient();

  // Check authentication
  const {
    data: { user },
  } = await supabase.auth.getUser();

  if (!user) {
    redirect("/login");
  }

  // Fetch ticket with relations
  const { data: ticket, error: ticketError } = await supabase
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
  const { data: comments } = await supabase
    .from("support_ticket_comments")
    .select(
      `
      *,
      user:users(id, name, email, avatar)
    `,
    )
    .eq("support_ticket_id", id)
    .order("created_at", { ascending: true });

  // Fetch staff users for assignment updates
  const { data: staffUsers } = await supabase.from("users").select("id, name, email").eq("role", "staff").order("name");

  return (
    <div className="container mx-auto py-8 max-w-6xl">
      <SupportTicketDetail ticket={ticket} staffUsers={staffUsers || []} />

      <div className="mt-8">
        <SupportTicketComments ticketId={id} initialComments={comments || []} currentUserId={user.id} />
      </div>
    </div>
  );
}
