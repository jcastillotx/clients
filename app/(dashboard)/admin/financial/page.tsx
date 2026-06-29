import { createClient, createAdminClientIfAvailable } from "@/lib/supabase/server";
import { redirect } from "next/navigation";
import { hasPermission } from "@/lib/rbac/permissions";
import { FinancialStats } from "@/components/admin/financial/financial-stats";
import { RevenueChart } from "@/components/admin/financial/revenue-chart";
import { AccountsReceivable } from "@/components/admin/financial/accounts-receivable";
import { TopClients } from "@/components/admin/financial/top-clients";
import { RecentTransactions } from "@/components/admin/financial/recent-transactions";
import { subDays, startOfMonth, endOfMonth, format } from "date-fns";

export const metadata = {
  title: "Financial Dashboard | Admin",
  description: "Financial reports and analytics",
};

export default async function FinancialDashboard() {
  const supabase = await createClient();

  const {
    data: { user },
  } = await supabase.auth.getUser();

  if (!user) {
    redirect("/login");
  }

  // Check permission
  const canViewFinancials = await hasPermission("reports.financial");
  if (!canViewFinancials) {
    redirect("/dashboard");
  }

  // Date ranges
  const today = new Date();
  const thirtyDaysAgo = subDays(today, 30);
  const currentMonthStart = startOfMonth(today);
  const currentMonthEnd = endOfMonth(today);

  // Use admin client so all clients' invoices are visible (bypasses RLS)
  const invoiceClient = createAdminClientIfAvailable() ?? supabase;

  // Fetch financial stats
  const [
    { data: allInvoices },
    { data: invoiceRows },
    { data: pendingInvoices },
    { data: overdueInvoices },
    { data: monthlyInvoices },
    { data: last30DaysRevenue },
  ] = await Promise.all([
    // Total invoices
    invoiceClient.from("invoices").select("amount"),
    // Paid invoices — need created_at to compute payment days
    invoiceClient.from("invoices").select("amount, created_at, paid_at").eq("status", "paid"),
    // Pending invoices
    invoiceClient.from("invoices").select("amount").eq("status", "sent"),
    // Overdue invoices (sent but past due date)
    invoiceClient.from("invoices").select("amount, due_date").eq("status", "sent").lt("due_date", today.toISOString()),
    // Current month invoices
    invoiceClient
      .from("invoices")
      .select("amount, created_at")
      .gte("created_at", currentMonthStart.toISOString())
      .lte("created_at", currentMonthEnd.toISOString()),
    // Last 30 days revenue (grouped by day)
    invoiceClient
      .from("invoices")
      .select("amount, paid_at, created_at")
      .eq("status", "paid")
      .gte("paid_at", thirtyDaysAgo.toISOString()),
  ]);

  // Calculate stats
  const totalRevenue = allInvoices?.reduce((sum, inv) => sum + (inv.amount || 0), 0) || 0;
  const paidRevenue = invoiceRows?.reduce((sum, inv) => sum + (inv.amount || 0), 0) || 0;
  const pendingRevenue = pendingInvoices?.reduce((sum, inv) => sum + (inv.amount || 0), 0) || 0;
  const overdueRevenue = overdueInvoices?.reduce((sum, inv) => sum + (inv.amount || 0), 0) || 0;
  const monthlyRevenue = monthlyInvoices?.reduce((sum, inv) => sum + (inv.amount || 0), 0) || 0;

  // Calculate average payment time: days between invoice creation and payment
  const paidWithDates = (invoiceRows || []).filter((inv) => inv.paid_at && inv.created_at);
  const avgPaymentDays =
    paidWithDates.length > 0
      ? Math.round(
          paidWithDates.reduce((sum, inv) => {
            const days =
              (new Date(inv.paid_at!).getTime() - new Date(inv.created_at).getTime()) /
              (1000 * 60 * 60 * 24);
            return sum + days;
          }, 0) / paidWithDates.length,
        )
      : 0;

  const stats = {
    totalRevenue,
    paidRevenue,
    pendingRevenue,
    overdueRevenue,
    monthlyRevenue,
    totalInvoices: allInvoices?.length || 0,
    paidInvoices: invoiceRows?.length || 0,
    pendingInvoices: pendingInvoices?.length || 0,
    overdueInvoices: overdueInvoices?.length || 0,
    avgPaymentDays,
  };

  // Revenue over time data
  const revenueData = last30DaysRevenue || [];

  return (
    <div className="flex flex-col gap-8 p-8">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold tracking-tight">Financial Dashboard</h1>
          <p className="text-muted-foreground">Revenue analytics and accounts receivable</p>
        </div>
      </div>

      {/* Stats Cards */}
      <FinancialStats stats={stats} />

      {/* Charts and Reports */}
      <div className="grid gap-6 md:grid-cols-2">
        <RevenueChart data={revenueData} />
        <AccountsReceivable />
      </div>

      {/* Tables */}
      <div className="grid gap-6 md:grid-cols-2">
        <TopClients />
        <RecentTransactions />
      </div>
    </div>
  );
}
