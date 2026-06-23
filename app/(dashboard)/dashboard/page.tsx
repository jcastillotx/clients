import { createAdminClientIfAvailable, createClient } from "@/lib/supabase/server";
import { DashboardStats } from "@/components/dashboard/dashboard-stats";
import { RecentActivity } from "@/components/dashboard/recent-activity";
import { SupportOverview } from "@/components/dashboard/support-overview";
import { OperationsCalendar } from "@/components/dashboard/operations-calendar";
import { LatestMessages } from "@/components/dashboard/latest-messages";
import { LatestInvoices } from "@/components/dashboard/latest-invoices";
import { db } from "@/lib/db";
import { conversations, conversationParticipants, messages, messageReads } from "@/lib/db/schema/messages";
import { users } from "@/lib/db/schema/users";
import { desc, eq, sql } from "drizzle-orm";

type RowWithClient<T> = T & {
  client?: { company_name?: string } | Array<{ company_name?: string }> | null;
};

const priorityWeight: Record<string, number> = {
  urgent: 4,
  high: 3,
  medium: 2,
  low: 1,
};

const OPEN_REQUEST_STATUSES = ["pending", "in_progress"] as const;
const OPEN_TICKET_STATUSES = ["open", "in_progress", "waiting_on_client", "waiting_on_vendor"] as const;

function normalizeClient<T extends { client?: unknown }>(row: T) {
  return {
    ...row,
    client: Array.isArray(row.client) ? row.client[0] || null : row.client,
  };
}

function sortByPriorityThenDate<T extends { priority?: string | null; created_at?: string | null }>(items: T[]) {
  return [...items].sort((a, b) => {
    const priorityDelta = (priorityWeight[b.priority || "low"] || 0) - (priorityWeight[a.priority || "low"] || 0);
    if (priorityDelta !== 0) {
      return priorityDelta;
    }

    return new Date(b.created_at || 0).getTime() - new Date(a.created_at || 0).getTime();
  });
}

export const metadata = {
  title: "Dashboard | KRE8IV",
  description: "Your dashboard overview",
};

/**
 * Main dashboard page (Server Component)
 *
 * Fetches overview statistics, recent activity, and upcoming tasks.
 */
export default async function DashboardPage() {
  const supabase = await createClient();

  // Authentication is handled by the dashboard layout - no redundant redirect needed.
  // We still need the user for display, so we call getUser() once (layout already validated auth).
  const {
    data: { user },
  } = await supabase.auth.getUser();

  if (!user) return null; // Type narrowing only; layout already guards auth

  const [{ data: dbUser }, { data: roleRows }] = await Promise.all([
    supabase.from("users").select("id, client_id, is_super_admin").eq("id", user.id).maybeSingle(),
    supabase.from("user_roles").select("role:roles(name)").eq("user_id", user.id),
  ]);

  const metadataRole = String(user.user_metadata?.role || user.user_metadata?.app_role || "").toLowerCase();
  const roleNames = new Set<string>();
  if (metadataRole) roleNames.add(metadataRole);
  for (const row of roleRows || []) {
    const roleName = String((row as any)?.role?.name || (row as any)?.role?.[0]?.name || "").toLowerCase();
    if (roleName) roleNames.add(roleName);
  }

  const isSuperAdmin = Boolean(dbUser?.is_super_admin || user.user_metadata?.is_super_admin === true);
  const isAdmin = isSuperAdmin || roleNames.has("admin") || roleNames.has("super_admin");
  const isAccountManager = roleNames.has("account_manager");
  const isStaff = isAdmin || isAccountManager || roleNames.has("staff");

  const adminClient = isStaff ? createAdminClientIfAvailable() : null;
  const dbClient = adminClient ?? supabase;
  const scopedClientId = dbUser?.client_id || null;

  // Fetch dashboard stats in parallel
  const [
    { count: totalRequests },
    { count: openRequests },
    { count: totalInvoices },
    { data: recentRequests },
    { data: recentTickets },
    { data: meetings },
    { data: projectDueItems },
    { data: campaigns },
    { data: invoiceRows },
  ] = await Promise.all([
    dbClient.from("requests").select("*", { count: "exact", head: true }).is("deleted_at", null),
    dbClient
      .from("requests")
      .select("*", { count: "exact", head: true })
      .in("status", ["pending", "in_progress"])
      .is("deleted_at", null),
    dbClient.from("invoices").select("*", { count: "exact", head: true }),
    dbClient
      .from("requests")
      .select(
        `
        id,
        title,
        status,
        priority,
        created_at,
        due_date,
        client:clients(company_name)
      `,
      )
      .is("deleted_at", null)
      .in("status", [...OPEN_REQUEST_STATUSES])
      .order("created_at", { ascending: false })
      .limit(12),
    dbClient
      .from("support_tickets")
      .select(
        `
        id,
        ticket_number,
        subject,
        status,
        priority,
        created_at,
        first_response_at,
        sla_response_due_at,
        sla_resolution_due_at,
        client:clients(company_name)
      `,
      )
      .is("deleted_at", null)
      .in("status", [...OPEN_TICKET_STATUSES])
      .order("created_at", { ascending: false })
      .limit(12),
    isStaff
      ? dbClient
          .from("meetings")
          .select(
            `
            id,
            title,
            scheduled_at,
            status,
            client:clients(company_name)
          `,
          )
          .gte("scheduled_at", new Date(new Date().getFullYear(), new Date().getMonth() - 1, 1).toISOString())
          .limit(50)
      : { data: [] },
    isStaff
      ? dbClient
          .from("projects")
          .select(
            `
            id,
            name,
            end_date,
            status,
            client:clients(company_name)
          `,
          )
          .not("end_date", "is", null)
          .limit(50)
      : { data: [] },
    isStaff
      ? dbClient
          .from("campaigns")
          .select(
            `
            id,
            name,
            start_date,
            end_date,
            status,
            client:clients(company_name)
          `,
          )
          .or("start_date.not.is.null,end_date.not.is.null")
          .limit(50)
      : { data: [] },
    isAdmin || isAccountManager
      ? dbClient
          .from("invoices")
          .select(
            `
            id,
            invoice_number,
            amount,
            status,
            due_date,
            created_at,
            client:clients(company_name)
          `,
          )
          .order("created_at", { ascending: false })
          .limit(5)
      : { data: [] },
  ]);

  const normalizedRequests = ((recentRequests || []) as RowWithClient<any>[]).map((request) => normalizeClient(request));
  const normalizedTickets = ((recentTickets || []) as RowWithClient<any>[]).map((ticket) => normalizeClient(ticket));
  const priorityRequests = sortByPriorityThenDate(normalizedRequests).slice(0, 5);
  const priorityTickets = sortByPriorityThenDate(normalizedTickets).slice(0, 5);

  const calendarItems = isStaff
    ? [
        ...((meetings || []) as RowWithClient<any>[])
          .map((meeting) => normalizeClient(meeting))
          .map((meeting) => ({
            id: `meeting-${meeting.id}`,
            title: meeting.title,
            date: meeting.scheduled_at,
            href: `/meetings/${meeting.id}`,
            type: "meeting" as const,
            subtitle: meeting.client?.company_name || null,
          })),
        ...((projectDueItems || []) as RowWithClient<any>[])
          .map((project) => normalizeClient(project))
          .map((project) => ({
            id: `project-${project.id}`,
            title: project.name,
            date: project.end_date,
            href: `/projects/${project.id}`,
            type: "project" as const,
            subtitle: project.client?.company_name || null,
          })),
        ...priorityRequests
          .filter((request) => request.due_date)
          .map((request) => ({
            id: `request-${request.id}`,
            title: request.title,
            date: request.due_date,
            href: `/requests/${request.id}`,
            type: "request" as const,
            subtitle: request.client?.company_name || null,
          })),
        ...priorityTickets
          .filter((ticket) => ticket.sla_resolution_due_at || ticket.sla_response_due_at)
          .map((ticket) => ({
            id: `ticket-${ticket.id}`,
            title: ticket.subject,
            date: ticket.sla_resolution_due_at || ticket.sla_response_due_at,
            href: `/support/${ticket.id}`,
            type: "ticket" as const,
            subtitle: ticket.client?.company_name || null,
          })),
        ...((campaigns || []) as RowWithClient<any>[])
          .map((campaign) => normalizeClient(campaign))
          .map((campaign) => ({
            id: `campaign-${campaign.id}`,
            title: campaign.name,
            date: campaign.start_date || campaign.end_date,
            href: `/marketing/campaigns`,
            type: "campaign" as const,
            subtitle: campaign.client?.company_name || null,
          }))
          .filter((item) => item.date),
      ]
    : [];

  const latestConversations = isStaff || scopedClientId
    ? await db
        .select({
          id: conversations.id,
          title: conversations.title,
          lastMessageAt: conversations.lastMessageAt,
          lastMessage: sql<string | null>`(
            SELECT body
            FROM ${messages}
            WHERE ${messages.conversationId} = ${conversations.id}
            ORDER BY created_at DESC
            LIMIT 1
          )`,
          lastMessageType: sql<string | null>`(
            SELECT type
            FROM ${messages}
            WHERE ${messages.conversationId} = ${conversations.id}
            ORDER BY created_at DESC
            LIMIT 1
          )`,
          unreadCount: sql<number>`(
            SELECT COUNT(*)::int
            FROM ${messages} m
            LEFT JOIN ${messageReads} mr ON m.id = mr.message_id AND mr.user_id = ${user.id}
            WHERE m.conversation_id = ${conversations.id}
            AND m.sender_id != ${user.id}
            AND mr.id IS NULL
          )`,
          participants: sql<Array<{ id: string; name: string; email: string }>>`(
            SELECT json_agg(json_build_object(
              'id', u.id,
              'name', u.name,
              'email', u.email
            ))
            FROM ${conversationParticipants} cp
            JOIN ${users} u ON cp.user_id = u.id
            WHERE cp.conversation_id = ${conversations.id}
          )`,
        })
        .from(conversations)
        .innerJoin(conversationParticipants, eq(conversationParticipants.conversationId, conversations.id))
        .where(eq(conversationParticipants.userId, user.id))
        .orderBy(desc(conversations.lastMessageAt))
        .limit(5)
    : [];

  return (
    <div className="flex flex-col gap-8 p-5 md:p-8">
      <div className="rounded-2xl border border-border/70 bg-card/70 p-6 shadow-sm backdrop-blur">
        <p className="mb-2 text-xs font-semibold uppercase tracking-[0.18em] text-primary">Overview</p>
        <h1 className="text-3xl font-bold tracking-tight md:text-4xl">Dashboard</h1>
        <p className="mt-2 text-muted-foreground">Welcome back, {user.user_metadata?.name || "User"}!</p>
      </div>

      {/* Stats Overview */}
      <DashboardStats
        totalRequests={totalRequests || 0}
        openRequests={openRequests || 0}
        totalInvoices={totalInvoices || 0}
      />

      {isStaff ? <OperationsCalendar items={calendarItems} /> : null}

      <div className="grid gap-6 xl:grid-cols-2">
        <RecentActivity
          requests={priorityRequests as any}
          title="Priority Requests"
          emptyMessage="No active requests in the queue"
        />
        <SupportOverview
          tickets={priorityTickets as any}
          title="Priority Support Tickets"
          emptyMessage="No support tickets in the queue"
        />
      </div>

      <div className={`grid gap-6 ${isAdmin || isAccountManager ? "xl:grid-cols-2" : "xl:grid-cols-1"}`}>
        <LatestMessages conversations={latestConversations as any} />
        {isAdmin || isAccountManager ? <LatestInvoices invoices={((invoiceRows || []) as RowWithClient<any>[]).map((invoice) => normalizeClient(invoice)) as any} /> : null}
      </div>
    </div>
  );
}
