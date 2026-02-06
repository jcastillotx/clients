import { createClient } from "@/lib/supabase/server";
import { DashboardStats } from "@/components/dashboard/dashboard-stats";
import { RecentActivity } from "@/components/dashboard/recent-activity";
import { UpcomingTasks } from "@/components/dashboard/upcoming-tasks";

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
  const supabase = createClient();

  const {
    data: { user },
  } = await supabase.auth.getUser();

  if (!user) {
    return null;
  }

  // Fetch dashboard stats in parallel
  const [
    { count: totalRequests },
    { count: openRequests },
    { count: totalInvoices },
    { data: recentRequests },
    { data: upcomingInvoices },
  ] = await Promise.all([
    supabase.from("requests").select("*", { count: "exact", head: true }),
    supabase.from("requests").select("*", { count: "exact", head: true }).in("status", ["pending", "in_progress"]),
    supabase.from("invoices").select("*", { count: "exact", head: true }),
    supabase
      .from("requests")
      .select(
        `
        id,
        title,
        status,
        priority,
        created_at,
        client:clients(company_name)
      `,
      )
      .order("created_at", { ascending: false })
      .limit(5),
    supabase
      .from("invoices")
      .select(
        `
        id,
        invoice_number,
        amount,
        due_date,
        status,
        client:clients(company_name)
      `,
      )
      .eq("status", "sent")
      .order("due_date", { ascending: true })
      .limit(5),
  ]);

  return (
    <div className="flex flex-col gap-8 p-8">
      <div>
        <h1 className="text-3xl font-bold tracking-tight">Dashboard</h1>
        <p className="text-muted-foreground">Welcome back, {user.user_metadata?.name || "User"}!</p>
      </div>

      {/* Stats Overview */}
      <DashboardStats
        totalRequests={totalRequests || 0}
        openRequests={openRequests || 0}
        totalInvoices={totalInvoices || 0}
      />

      {/* Recent Activity & Upcoming Tasks */}
      <div className="grid gap-6 md:grid-cols-2">
        <RecentActivity
          requests={
            (recentRequests || []).map((r: any) => ({
              ...r,
              client: Array.isArray(r.client) ? r.client[0] : r.client,
            })) as any
          }
        />
        <UpcomingTasks
          invoices={
            (upcomingInvoices || []).map((inv: any) => ({
              ...inv,
              client: Array.isArray(inv.client) ? inv.client[0] : inv.client,
            })) as any
          }
        />
      </div>
    </div>
  );
}
