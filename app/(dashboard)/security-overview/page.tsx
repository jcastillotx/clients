import { createClient } from "@/lib/supabase/server";
import { redirect } from "next/navigation";
import Link from "next/link";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { AccountHealthDashboard } from "@/components/features/account-health-dashboard";

export const metadata = {
  title: "Security Overview | KRE8IV",
  description: "Security posture, client risk, and compliance operations",
};

export default async function SecurityOverviewPage() {
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
        <h1 className="text-3xl font-bold tracking-tight">Security Overview</h1>
        <p className="mt-1 text-muted-foreground">Security health, privacy operations, and compliance shortcuts</p>
      </div>

      <div className="grid gap-4 md:grid-cols-2">
        <Card>
          <CardHeader>
            <CardTitle>Privacy Operations</CardTitle>
            <CardDescription>Manage data access, deletion, and export requests</CardDescription>
          </CardHeader>
          <CardContent>
            <Button asChild>
              <Link href="/privacy-requests">Open Privacy Requests</Link>
            </Button>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Integration Security</CardTitle>
            <CardDescription>Review webhook endpoints and external integrations</CardDescription>
          </CardHeader>
          <CardContent>
            <Button asChild variant="outline">
              <Link href="/webhooks">Open Webhooks</Link>
            </Button>
          </CardContent>
        </Card>
      </div>

      {clientId ? (
        <AccountHealthDashboard clientId={clientId} />
      ) : (
        <Card>
          <CardContent className="py-6 text-sm text-muted-foreground">
            No client is assigned to this user yet. Assign a client to load security health metrics.
          </CardContent>
        </Card>
      )}
    </div>
  );
}
