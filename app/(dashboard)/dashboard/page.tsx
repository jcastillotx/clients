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
  const supabase = await createClient();

  // Authentication is handled by the dashboard layout - no redundant redirect needed.
  // We still need the user for display, so we call getUser() once (layout already validated auth).
  const {
    data: { user },
  } = await supabase.auth.getUser();

  if (!user) return null; // Type narrowing only; layout already guards auth

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

      {/* Recent Activity & Upcoming Tasks */}
      <div className="grid gap-6 xl:grid-cols-2">
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
