import { redirect } from "next/navigation";
import { subDays, startOfMonth } from "date-fns";
import { createAdminClientIfAvailable, createClient } from "@/lib/supabase/server";
import { isUserAdmin } from "@/lib/rbac/check";
import { AdminDashboard } from "@/components/admin/dashboard/admin-dashboard";

type CountResult = { count: number | null };

type InvoiceRow = {
  id: string;
  invoice_number: string;
  amount: string | number | null;
  status: string;
  due_date: string | null;
  paid_at: string | null;
  created_at: string | null;
  client?: { company_name?: string | null } | null;
};

type SupportTicketRow = {
  id: string;
  ticket_number: string;
  subject: string;
  status: string;
  priority: string;
  created_at: string | null;
  sla_response_breached?: boolean | null;
  sla_resolution_breached?: boolean | null;
  client?: { company_name?: string | null } | null;
  assigned_user?: { name?: string | null } | null;
};

type ActivityRow = {
  id: string;
  description: string;
  subject_type: string;
  created_at: string;
  causer?: { name?: string | null; email?: string | null } | null;
};

type AutomationFailureRow = {
  id: string;
  trigger: string;
  status?: string | null;
  message?: string | null;
  error?: string | null;
  started_at?: string | null;
  finished_at?: string | null;
  created_at?: string | null;
};

function normalizeCount(result: CountResult): number {
  return result.count ?? 0;
}

function money(value: string | number | null | undefined): number {
  if (value === null || value === undefined) {
    return 0;
  }
  const parsed = Number(value);
  return Number.isFinite(parsed) ? parsed : 0;
}

function clientName(row: { client?: unknown }): string {
  const client = Array.isArray(row.client) ? row.client[0] : row.client;
  return (client as { company_name?: string | null } | null)?.company_name || "Unassigned";
}

export default async function AdminPage() {
  const supabase = await createClient();
  const {
    data: { user },
  } = await supabase.auth.getUser();

  if (!user) {
    redirect("/login");
  }

  const isAdmin = await isUserAdmin(user.id);
  if (!isAdmin) {
    redirect("/dashboard");
  }

  const adminClient = createAdminClientIfAvailable();
  if (!adminClient) {
    throw new Error("Missing Supabase service role credentials for admin dashboard queries");
  }

  const now = new Date();
  const last30 = subDays(now, 30).toISOString();
  const monthStart = startOfMonth(now).toISOString();
  const nowIso = now.toISOString();

  const [
    totalClients,
    activeClients,
    totalUsers,
    activeUsers,
    totalRequests,
    openRequests,
    requestsLast30,
    totalProjects,
    activeProjects,
    totalDocuments,
    documentsLast30,
    totalInvoices,
    unpaidInvoices,
    invoicesLast30,
    paidInvoicesThisMonth,
    overdueInvoices,
    totalContracts,
    activeContracts,
    totalTickets,
    openTickets,
    urgentTickets,
    breachedTickets,
    ticketsLast30,
    upcomingMeetings,
    recentTicketsResult,
    recentInvoicesResult,
    recentActivityResult,
    automationFailuresResult,
    reportFailuresResult,
  ] = await Promise.all([
    adminClient.from("clients").select("id", { count: "exact", head: true }).is("deleted_at", null),
    adminClient.from("clients").select("id", { count: "exact", head: true }).eq("status", "active").is("deleted_at", null),
    adminClient.from("users").select("id", { count: "exact", head: true }).is("deleted_at", null),
    adminClient.from("users").select("id", { count: "exact", head: true }).eq("is_active", true).is("deleted_at", null),
    adminClient.from("requests").select("id", { count: "exact", head: true }).is("deleted_at", null),
    adminClient
      .from("requests")
      .select("id", { count: "exact", head: true })
      .in("status", ["pending", "in_progress", "awaiting_approval"])
      .is("deleted_at", null),
    adminClient
      .from("requests")
      .select("id", { count: "exact", head: true })
      .gte("created_at", last30)
      .is("deleted_at", null),
    adminClient.from("projects").select("id", { count: "exact", head: true }).is("deleted_at", null),
    adminClient
      .from("projects")
      .select("id", { count: "exact", head: true })
      .in("status", ["planning", "active"])
      .is("deleted_at", null),
    adminClient.from("documents").select("id", { count: "exact", head: true }).is("deleted_at", null),
    adminClient
      .from("documents")
      .select("id", { count: "exact", head: true })
      .gte("created_at", last30)
      .is("deleted_at", null),
    adminClient.from("invoices").select("id", { count: "exact", head: true }).is("deleted_at", null),
    adminClient
      .from("invoices")
      .select("id", { count: "exact", head: true })
      .in("status", ["sent", "overdue"])
      .is("deleted_at", null),
    adminClient
      .from("invoices")
      .select("amount,status,created_at,paid_at")
      .gte("created_at", last30)
      .is("deleted_at", null),
    adminClient
      .from("invoices")
      .select("amount,status,created_at,paid_at")
      .eq("status", "paid")
      .gte("paid_at", monthStart)
      .is("deleted_at", null),
    adminClient
      .from("invoices")
      .select("id", { count: "exact", head: true })
      .neq("status", "paid")
      .lt("due_date", nowIso)
      .is("deleted_at", null),
    adminClient.from("contracts").select("id", { count: "exact", head: true }).is("deleted_at", null),
    adminClient.from("contracts").select("id", { count: "exact", head: true }).eq("status", "active").is("deleted_at", null),
    adminClient.from("support_tickets").select("id", { count: "exact", head: true }).is("deleted_at", null),
    adminClient
      .from("support_tickets")
      .select("id", { count: "exact", head: true })
      .in("status", ["open", "in_progress", "waiting_on_client", "waiting_on_vendor"])
      .is("deleted_at", null),
    adminClient
      .from("support_tickets")
      .select("id", { count: "exact", head: true })
      .in("priority", ["high", "urgent"])
      .in("status", ["open", "in_progress", "waiting_on_client", "waiting_on_vendor"])
      .is("deleted_at", null),
    adminClient
      .from("support_tickets")
      .select("id", { count: "exact", head: true })
      .or("sla_response_breached.eq.true,sla_resolution_breached.eq.true")
      .is("deleted_at", null),
    adminClient
      .from("support_tickets")
      .select("id", { count: "exact", head: true })
      .gte("created_at", last30)
      .is("deleted_at", null),
    adminClient
      .from("meetings")
      .select("id", { count: "exact", head: true })
      .eq("status", "scheduled")
      .gte("scheduled_at", nowIso)
      .is("deleted_at", null),
    adminClient
      .from("support_tickets")
      .select(
        `
        id,
        ticket_number,
        subject,
        status,
        priority,
        created_at,
        sla_response_breached,
        sla_resolution_breached,
        client:clients(company_name),
        assigned_user:users!support_tickets_assigned_to_fkey(name)
      `,
      )
      .is("deleted_at", null)
      .order("created_at", { ascending: false })
      .limit(6),
    adminClient
      .from("invoices")
      .select(
        `
        id,
        invoice_number,
        amount,
        status,
        due_date,
        paid_at,
        created_at,
        client:clients(company_name)
      `,
      )
      .is("deleted_at", null)
      .order("created_at", { ascending: false })
      .limit(6),
    adminClient
      .from("activity_logs")
      .select(
        `
        id,
        description,
        subject_type,
        created_at,
        causer:users!causer_id(name, email)
      `,
      )
      .order("created_at", { ascending: false })
      .limit(10),
    adminClient
      .from("automation_logs")
      .select("id, trigger, status, message, started_at, finished_at, created_at")
      .eq("status", "failed")
      .order("created_at", { ascending: false })
      .limit(5),
    adminClient
      .from("report_deliveries")
      .select("id, category, status, error, created_at")
      .eq("status", "failed")
      .order("created_at", { ascending: false })
      .limit(5),
  ]);

  const recentInvoices = (recentInvoicesResult.data || []) as InvoiceRow[];
  const monthPaidRevenue = ((paidInvoicesThisMonth.data || []) as InvoiceRow[]).reduce(
    (sum, invoice) => sum + money(invoice.amount),
    0,
  );
  const last30Revenue = ((invoicesLast30.data || []) as InvoiceRow[]).reduce(
    (sum, invoice) => sum + money(invoice.amount),
    0,
  );
  const outstandingRevenue = recentInvoices
    .filter((invoice) => ["sent", "overdue"].includes(invoice.status))
    .reduce((sum, invoice) => sum + money(invoice.amount), 0);

  const recentTickets = ((recentTicketsResult.data || []) as SupportTicketRow[]).map((ticket) => ({
    id: ticket.id,
    ticketNumber: ticket.ticket_number,
    subject: ticket.subject,
    status: ticket.status,
    priority: ticket.priority,
    clientName: clientName(ticket),
    assignedTo: Array.isArray(ticket.assigned_user)
      ? ticket.assigned_user[0]?.name || null
      : ticket.assigned_user?.name || null,
    createdAt: ticket.created_at,
    hasSlaBreach: Boolean(ticket.sla_response_breached || ticket.sla_resolution_breached),
  }));

  const operationalIssues = [
    ...((automationFailuresResult.data || []) as AutomationFailureRow[]).map((failure) => ({
      id: `automation-${failure.id}`,
      title: `Automation failed: ${failure.trigger}`,
      detail: failure.message || failure.error || "Automation run failed without a stored error message.",
      severity: "high" as const,
      createdAt: failure.created_at || failure.finished_at || failure.started_at || null,
    })),
    ...((reportFailuresResult.data || []) as Array<{ id: string; category: string | null; error: string | null; created_at: string | null }>).map(
      (failure) => ({
        id: `report-${failure.id}`,
        title: `Report delivery failed${failure.category ? `: ${failure.category}` : ""}`,
        detail: failure.error || "Report delivery failed without a stored error message.",
        severity: "medium" as const,
        createdAt: failure.created_at,
      }),
    ),
    ...recentTickets
      .filter((ticket) => ticket.hasSlaBreach || ticket.priority === "urgent")
      .map((ticket) => ({
        id: `ticket-${ticket.id}`,
        title: ticket.hasSlaBreach ? `SLA breach: ${ticket.subject}` : `Urgent ticket: ${ticket.subject}`,
        detail: `${ticket.ticketNumber} • ${ticket.clientName} • ${ticket.status.replace(/_/g, " ")}`,
        severity: ticket.hasSlaBreach ? ("critical" as const) : ("high" as const),
        createdAt: ticket.createdAt,
      })),
  ]
    .sort((a, b) => new Date(b.createdAt || 0).getTime() - new Date(a.createdAt || 0).getTime())
    .slice(0, 8);

  const dashboardData = {
    generatedAt: now.toISOString(),
    platform: {
      clients: { total: normalizeCount(totalClients), active: normalizeCount(activeClients) },
      users: { total: normalizeCount(totalUsers), active: normalizeCount(activeUsers) },
      requests: {
        total: normalizeCount(totalRequests),
        open: normalizeCount(openRequests),
        last30: normalizeCount(requestsLast30),
      },
      projects: { total: normalizeCount(totalProjects), active: normalizeCount(activeProjects) },
      documents: { total: normalizeCount(totalDocuments), last30: normalizeCount(documentsLast30) },
      tickets: {
        total: normalizeCount(totalTickets),
        open: normalizeCount(openTickets),
        urgent: normalizeCount(urgentTickets),
        breached: normalizeCount(breachedTickets),
        last30: normalizeCount(ticketsLast30),
      },
      meetings: { upcoming: normalizeCount(upcomingMeetings) },
      contracts: { total: normalizeCount(totalContracts), active: normalizeCount(activeContracts) },
      invoices: { total: normalizeCount(totalInvoices), unpaid: normalizeCount(unpaidInvoices), overdue: normalizeCount(overdueInvoices) },
    },
    financials: {
      paidThisMonth: monthPaidRevenue,
      createdLast30: last30Revenue,
      outstandingRecent: outstandingRevenue,
      unpaidCount: normalizeCount(unpaidInvoices),
      overdueCount: normalizeCount(overdueInvoices),
      recentInvoices: recentInvoices.map((invoice) => ({
        id: invoice.id,
        invoiceNumber: invoice.invoice_number,
        clientName: clientName(invoice),
        amount: money(invoice.amount),
        status: invoice.status,
        dueDate: invoice.due_date,
        createdAt: invoice.created_at,
      })),
    },
    issues: operationalIssues,
    analytics: {
      requestVolume30Days: normalizeCount(requestsLast30),
      ticketVolume30Days: normalizeCount(ticketsLast30),
      documentUploads30Days: normalizeCount(documentsLast30),
      openRequestRate:
        normalizeCount(totalRequests) > 0 ? Math.round((normalizeCount(openRequests) / normalizeCount(totalRequests)) * 100) : 0,
      openTicketRate:
        normalizeCount(totalTickets) > 0 ? Math.round((normalizeCount(openTickets) / normalizeCount(totalTickets)) * 100) : 0,
    },
    recentTickets,
    recentActivity: ((recentActivityResult.data || []) as ActivityRow[]).map((activity) => ({
      id: activity.id,
      description: activity.description,
      subjectType: activity.subject_type,
      createdAt: activity.created_at,
      actor: Array.isArray(activity.causer)
        ? activity.causer[0]?.name || activity.causer[0]?.email || null
        : activity.causer?.name || activity.causer?.email || null,
    })),
  };

  return (
    <div className="container mx-auto py-6">
      <AdminDashboard data={dashboardData} />
    </div>
  );
}
