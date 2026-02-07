import { createClient } from "@/lib/supabase/server";
import { redirect } from "next/navigation";
import { DashboardNav } from "@/components/dashboard/nav";

// All dashboard pages require authentication (cookies), so they cannot be statically generated
export const dynamic = "force-dynamic";

/**
 * Dashboard layout (Server Component)
 *
 * Wraps all dashboard pages with navigation and authentication check.
 */
export default async function DashboardLayout({ children }: { children: React.ReactNode }) {
  const supabase = await createClient();

  // Check authentication
  const {
    data: { user },
  } = await supabase.auth.getUser();

  if (!user) {
    redirect("/login");
  }

  return (
    <div className="flex min-h-screen bg-gradient-to-br from-background via-background to-secondary/30">
      {/* Sidebar Navigation */}
      <DashboardNav user={user} />

      {/* Main Content */}
      <main className="relative flex-1 overflow-y-auto">
        <div className="pointer-events-none absolute inset-x-0 top-0 h-36 bg-gradient-to-b from-primary/8 to-transparent" />
        <div className="relative">{children}</div>
      </main>
    </div>
  );
}
