import { createClient } from "@/lib/supabase/server";
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
    { data: recentActivity },
    { data: topClients },
    { data: slaBreaches },
  ] = await Promise.all([
    // Clients
    supabase.from("clients").select("*", { count: "exact", head: true }),
    supabase.from("clients").select("*", { count: "exact", head: true }).eq("is_active", true),

    // Users
    supabase.from("users").select("*", { count: "exact", head: true }),

    // Requests
    supabase.from("requests").select("*", { count: "exact", head: true }).is("deleted_at", null),
    supabase
      .from("requests")
      .select("*", { count: "exact", head: true })
      .eq("status", "pending")
      .is("deleted_at", null),

    // Invoices
    supabase.from("invoices").select("*", { count: "exact", head: true }).is("deleted_at", null),
    supabase
      .from("invoices")
      .select("*", { count: "exact", head: true })
      .in("status", ["sent", "overdue"])
      .is("deleted_at", null),

    // Contracts
    supabase.from("contracts").select("*", { count: "exact", head: true }).is("deleted_at", null),
    supabase
      .from("contracts")
      .select("*", { count: "exact", head: true })
      .eq("status", "active")
      .is("deleted_at", null),

    // Recent Activity
    supabase
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
    supabase
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
    supabase
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
  };

  return (
    <div className="container mx-auto py-6">
      <AdminDashboard
        stats={stats}
        recentActivity={recentActivity || []}
        topClients={sortedTopClients || []}
        slaBreaches={slaBreaches || []}
      />
    </div>
  );
}
