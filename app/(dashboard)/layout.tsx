import { createAdminClientIfAvailable, createClient } from "@/lib/supabase/server";
import { redirect } from "next/navigation";
import { DashboardNav } from "@/components/dashboard/nav";
import { TopBar } from "@/components/layout/top-bar";
import { ensureAuthUserProfile } from "@/lib/supabase/user-profile-sync";

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

  const adminClient = createAdminClientIfAvailable();
  if (adminClient) {
    await ensureAuthUserProfile(adminClient, user);
  }

  const { data: dbUser } = await supabase.from("users").select("is_super_admin").eq("id", user.id).maybeSingle();
  const { data: roleRows } = await supabase
    .from("user_roles")
    .select("role:roles(name)")
    .eq("user_id", user.id);

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

  const userRole = isAdmin ? "admin" : isStaff ? "staff" : "client";
  const { data: userData } = await supabase.from("users").select("name, email, client_id").eq("id", user.id).single();

  return (
    <div className="flex min-h-screen bg-gradient-to-br from-background via-background to-secondary/30">
      {/* Sidebar Navigation */}
      <DashboardNav user={user} isStaff={isStaff} isAdmin={isAdmin} isAccountManager={isAccountManager} />

      {/* Main Content */}
      <main className="relative flex-1 overflow-y-auto">
        {/* Top Bar - Different for clients vs staff/admin */}
        <TopBar 
          userRole={userRole} 
          userName={userData?.name || user.email || "User"} 
          userEmail={userData?.email || user.email || ""} 
          clientId={userData?.client_id || undefined}
        />

        {/* Page Content with consistent spacing */}
        <div className="dashboard-page-content relative p-6 md:p-8">{children}</div>
      </main>
    </div>
  );
}
