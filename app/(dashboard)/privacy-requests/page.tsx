import { createClient } from "@/lib/supabase/server";
import { redirect } from "next/navigation";
import { PrivacyRequests } from "@/components/features/privacy-requests";

export const metadata = {
  title: "Privacy Requests | KRE8IV",
  description: "Handle GDPR and CCPA data requests",
};

export default async function PrivacyRequestsPage() {
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
        <h1 className="text-3xl font-bold tracking-tight">Privacy Requests</h1>
        <p className="mt-1 text-muted-foreground">Submit and track GDPR/CCPA data access and deletion requests</p>
      </div>

      {clientId ? (
        <PrivacyRequests clientId={clientId} />
      ) : (
        <div className="rounded-lg border border-border/70 bg-card p-6 text-sm text-muted-foreground">
          No client is assigned to this user yet. Assign a client to manage privacy requests.
        </div>
      )}
    </div>
  );
}
