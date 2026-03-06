import { Suspense } from "react";
import { LeadList } from "@/components/marketing/lead-list";
import { Button } from "@/components/ui/button";
import { PlusCircle } from "lucide-react";
import Link from "next/link";
import { createClient } from "@/lib/supabase/server";
import { redirect } from "next/navigation";

export const metadata = {
  title: "Leads | Marketing",
  description: "Manage your marketing leads",
};

export default async function LeadsPage() {
  const supabase = await createClient();
  const {
    data: { user },
  } = await supabase.auth.getUser();

  if (!user) redirect("/login");

  // Check staff access
  const { data: dbUser } = await supabase.from("users").select("is_super_admin").eq("id", user.id).maybeSingle();
  const { data: roleRows } = await supabase
    .from("user_roles")
    .select("role:roles(name)")
    .eq("user_id", user.id);

  const roleNames = new Set<string>();
  for (const row of roleRows || []) {
    const roleName = String((row as any)?.role?.name || (row as any)?.role?.[0]?.name || "").toLowerCase();
    if (roleName) roleNames.add(roleName);
  }

  const isAdmin = Boolean(dbUser?.is_super_admin) || roleNames.has("admin") || roleNames.has("super_admin");
  const isAccountManager = roleNames.has("account_manager");
  const isStaff = isAdmin || isAccountManager || roleNames.has("staff");

  if (!isStaff) {
    redirect("/marketing");
  }

  return (
    <div className="flex flex-col gap-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold tracking-tight">Lead Management</h1>
          <p className="text-muted-foreground">Track and nurture your marketing leads through the sales pipeline</p>
        </div>
        <Link href="/marketing/leads/new">
          <Button>
            <PlusCircle className="mr-2 h-4 w-4" />
            New Lead
          </Button>
        </Link>
      </div>

      <Suspense fallback={<div>Loading leads...</div>}>
        <LeadList />
      </Suspense>
    </div>
  );
}
