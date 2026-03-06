import { Suspense } from "react";
import { MarketingOverview } from "@/components/marketing/marketing-overview";
import { createClient } from "@/lib/supabase/server";

export const metadata = {
  title: "Marketing | Overview",
  description: "View your marketing services and tools",
};

export default async function MarketingPage() {
  const supabase = await createClient();
  const {
    data: { user },
  } = await supabase.auth.getUser();

  const { data: userData } = user
    ? await supabase.from("users").select("client_id").eq("id", user.id).single()
    : { data: null };

  return (
    <div className="flex flex-col gap-6">
      <div>
        <h1 className="text-3xl font-bold tracking-tight">Marketing</h1>
        <p className="text-muted-foreground">
          Overview of marketing services and tools for your account
        </p>
      </div>

      <Suspense fallback={<div>Loading marketing overview...</div>}>
        <MarketingOverview clientId={userData?.client_id || undefined} />
      </Suspense>
    </div>
  );
}
