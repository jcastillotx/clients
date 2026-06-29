import { createClient } from "@/lib/supabase/server";
import { redirect } from "next/navigation";
import { WebhookManager } from "@/components/features/webhook-manager";

export const metadata = {
  title: "Webhooks | KRE8IV",
  description: "Manage outbound webhook endpoints and subscriptions",
};

export default async function WebhooksPage() {
  const supabase = await createClient();

  const {
    data: { user },
  } = await supabase.auth.getUser();

  if (!user) {
    redirect("/login");
  }

  const { data: dbUser } = await supabase.from("users").select("client_id, is_super_admin").eq("id", user.id).maybeSingle();
  const clientId = dbUser?.client_id ?? null;
  const isAdmin = Boolean(dbUser?.is_super_admin);

  return (
    <div className="container mx-auto space-y-6 py-8">
      <div>
        <h1 className="text-3xl font-bold tracking-tight">Webhooks</h1>
        {isAdmin && !clientId ? (
          <p className="mt-1 text-muted-foreground">Platform-wide webhooks — all clients</p>
        ) : (
          <p className="mt-1 text-muted-foreground">Configure external integrations for real-time event delivery</p>
        )}
      </div>

      {clientId || isAdmin ? (
        <WebhookManager clientId={clientId} />
      ) : (
        <div className="rounded-lg border border-border/70 bg-card p-6 text-sm text-muted-foreground">
          No client is assigned to this user yet. Assign a client to manage webhooks.
        </div>
      )}
    </div>
  );
}
