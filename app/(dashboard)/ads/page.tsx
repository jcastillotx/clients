import { createClient } from "@/lib/supabase/server";
import { AdCampaignsList } from "@/components/ads/ad-campaigns-list";
import { Button } from "@/components/ui/button";
import Link from "next/link";
import { Plus } from "lucide-react";

export const metadata = {
  title: "Ad Campaigns | KRE8IV",
  description: "Manage your advertising campaigns",
};

interface SearchParams {
  clientId?: string;
  status?: string;
}

export default async function AdCampaignsPage({
  searchParams,
}: {
  searchParams: Promise<SearchParams>;
}) {
  const resolvedSearchParams = await searchParams;
  const supabase = await createClient();

  const {
    data: { user },
  } = await supabase.auth.getUser();

  if (!user) {
    return null;
  }

  // Fetch user's client ID and admin status
  const { data: userData } = await supabase.from("users").select("client_id, is_super_admin").eq("id", user.id).single();

  const clientId = resolvedSearchParams.clientId || userData?.client_id || null;
  const isAdmin = Boolean(userData?.is_super_admin);

  // Admins without a client_id see all data platform-wide
  let adAccountsQuery = supabase.from("ad_accounts").select("*").is("deleted_at", null);
  if (clientId) {
    adAccountsQuery = adAccountsQuery.eq("client_id", clientId);
  }

  const { data: adAccounts, error: accountsError } = await adAccountsQuery;

  const { data: campaigns, error: campaignsError } = await supabase
    .from("ad_campaigns")
    .select(`*, account:ad_accounts(id, platform, account_name, currency)`)
    .in("ad_account_id", adAccounts?.map((acc) => acc.id) || [])
    .is("deleted_at", null)
    .order("created_at", { ascending: false });

  if (accountsError || campaignsError) {
    console.error("Error fetching ad campaigns:", accountsError || campaignsError);
  }

  return (
    <div className="flex flex-col gap-8 p-8">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold tracking-tight">Ad Campaigns</h1>
          <p className="text-muted-foreground">
            {isAdmin && !clientId ? "Showing all clients — select a client to filter" : "Monitor and manage your advertising campaigns"}
          </p>
        </div>
        <Button asChild>
          <Link href="/ads/new">
            <Plus className="mr-2 h-4 w-4" />
            New Campaign
          </Link>
        </Button>
      </div>

      <AdCampaignsList clientId={clientId || ""} initialCampaigns={campaigns || []} adAccounts={adAccounts || []} />
    </div>
  );
}
