import { createClient } from "@/lib/supabase/server";
import { createAdminClientIfAvailable } from "@/lib/supabase/server";
import { hasPermission } from "@/lib/rbac/permissions";
import { redirect } from "next/navigation";
import { AdminDashboard } from "@/components/admin/dashboard/admin-dashboard";

export default async function AdminPage() {
  const supabase = await createClient();

  // Authentication is handled by the dashboard layout and middleware admin guard.
  // Check admin access
  const isAdmin = await hasPermission("admin.access");
  if (!isAdmin) {
    redirect("/dashboard");
  }

  // Use admin client to bypass RLS and see all data across clients
  const adminClient = createAdminClientIfAvailable() ?? supabase;

  // Fetch dashboard statistics in parallel
  const [
    { count: totalClients },
    { count: activeClients },
    { count: totalUsers },
    { count: totalRequests },
    { count: pendingRequests },
    { count: totalInvoices },
    { count: unpaidInvoices },
    { count: totalContracts },
    { count: activeContracts },
    { count: totalTickets },
    { count: openTickets },
    { data: recentActivity },
    { data: topClients },
    { data: slaBreaches },
    { data: recentTickets },
  ] = await Promise.all([
    // Clients
    adminClient.from("clients").select("*", { count: "exact", head: true }),
    adminClient.from("clients").select("*", { count: "exact", head: true }).eq("is_active", true),

    // Users
    adminClient.from("users").select("*", { count: "exact", head: true }),

    // Requests
    adminClient.from("requests").select("*", { count: "exact", head: true }).is("deleted_at", null),
    adminClient
      .from("requests")
      .select("*", { count: "exact", head: true })
      .eq("status", "pending")
      .is("deleted_at", null),

    // Invoices
    adminClient.from("invoices").select("*", { count: "exact", head: true }).is("deleted_at", null),
    adminClient
      .from("invoices")
      .select("*", { count: "exact", head: true })
      .in("status", ["sent", "overdue"])
      .is("deleted_at", null),

    // Contracts
    adminClient.from("contracts").select("*", { count: "exact", head: true }).is("deleted_at", null),
    adminClient
      .from("contracts")
      .select("*", { count: "exact", head: true })
      .eq("status", "active")
      .is("deleted_at", null),

    // Support Tickets
    adminClient.from("support_tickets").select("*", { count: "exact", head: true }).is("deleted_at", null),
    adminClient
      .from("support_tickets")
      .select("*", { count: "exact", head: true })
      .in("status", ["open", "in_progress"])
      .is("deleted_at", null),

    // Recent Activity
    adminClient
      .from("activity_logs")
      .select(
        `
        *,
        causer:users!causer_id(id, name, email)
      `,
      )
      .order("created_at", { ascending: false })
      .limit(10),

    // Top Clients by Revenue
    adminClient
      .from("clients")
      .select(
        `
        id,
        company_name,
        invoices(amount)
      `,
      )
      .limit(5),

    // SLA Breaches
    adminClient
      .from("requests")
      .select(
        `
        *,
        client:clients(id, company_name)
      `,
      )
      .eq("sla_breached", true)
      .is("deleted_at", null)
      .order("sla_breach_at", { ascending: false })
      .limit(5),

    // Recent Support Tickets (across all clients)
    adminClient
      .from("support_tickets")
      .select(
        `
        *,
        client:clients(company_name),
        creator:users!support_tickets_created_by_fkey(name, avatar),
        assigned_user:users!support_tickets_assigned_to_fkey(name, avatar)
      `,
      )
      .is("deleted_at", null)
      .order("created_at", { ascending: false })
      .limit(5),
  ]);

  // Calculate top clients by total invoice amount
  const clientsWithRevenue = topClients?.map((client) => ({
    ...client,
    totalRevenue: client.invoices?.reduce((sum: number, inv: any) => sum + (inv.amount || 0), 0) || 0,
  }));

  const sortedTopClients = clientsWithRevenue?.sort((a, b) => b.totalRevenue - a.totalRevenue).slice(0, 5);

  const stats = {
    clients: {
      total: totalClients || 0,
      active: activeClients || 0,
    },
    users: {
      total: totalUsers || 0,
    },
    requests: {
      total: totalRequests || 0,
      pending: pendingRequests || 0,
    },
    invoices: {
      total: totalInvoices || 0,
      unpaid: unpaidInvoices || 0,
    },
    contracts: {
      total: totalContracts || 0,
      active: activeContracts || 0,
    },
    tickets: {
      total: totalTickets || 0,
      open: openTickets || 0,
    },
  };

  return (
    <div className="container mx-auto py-6">
      <AdminDashboard
        stats={stats}
        recentActivity={recentActivity || []}
        topClients={sortedTopClients || []}
        slaBreaches={slaBreaches || []}
        recentTickets={recentTickets || []}
      />
    </div>
  );
}
