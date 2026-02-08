import { createClient } from "@/lib/supabase/server";
import { redirect } from "next/navigation";
import { AccountHealthDashboard } from "@/components/features/account-health-dashboard";

export const metadata = {
  title: "Account Health | KRE8IV",
  description: "Track client health metrics, risk, and recommendations",
};

export default async function AccountHealthPage() {
  const supabase = await createClient();

  const {
    data: { user },
  } = await supabase.auth.getUser();

  if (!user) {
    redirect("/login");
  }

  const { data: dbUser } = await supabase.from("users").select("client_id").eq("id", user.id).maybeSingle();
  const clientId = dbUser?.client_id;

  return (
    <div className="container mx-auto space-y-6 py-8">
      <div>
        <h1 className="text-3xl font-bold tracking-tight">Account Health</h1>
        <p className="mt-1 text-muted-foreground">Monitor account score, risk, and retention recommendations</p>
      </div>

      {clientId ? (
        <AccountHealthDashboard clientId={clientId} />
      ) : (
        <div className="rounded-lg border border-border/70 bg-card p-6 text-sm text-muted-foreground">
          No client is assigned to this user yet. Assign a client to view account health.
        </div>
      )}
    </div>
  );
}
