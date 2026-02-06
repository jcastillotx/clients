import { createClient } from "@/lib/supabase/server";
import { CampaignDashboard } from "@/components/ads/campaign-dashboard";
import { notFound } from "next/navigation";
import { ArrowLeft } from "lucide-react";
import Link from "next/link";
import { Button } from "@/components/ui/button";

export const metadata = {
  title: "Campaign Details | KRE8IV",
  description: "View campaign performance and metrics",
};

interface PageProps {
  params: Promise<{
    id: string;
  }>;
}

/**
 * Individual Ad Campaign page (Server Component)
 *
 * Displays detailed metrics and performance for a specific campaign.
 */
export default async function CampaignDetailPage({ params }: PageProps) {
  const { id } = await params;
  const supabase = await createClient();

  // Check authentication
  const {
    data: { user },
  } = await supabase.auth.getUser();

  if (!user) {
    return null; // Middleware will redirect
  }

  // Fetch campaign with related data
  const { data: campaign, error: campaignError } = await supabase
    .from("ad_campaigns")
    .select(
      `
      *,
      account:ad_accounts(id, platform, account_name, currency, timezone),
      ad_sets:ad_sets(
        *,
        ads:ads(
          *,
          creative:ad_creatives(*),
          metrics:ad_metrics(*)
        )
      )
    `,
    )
    .eq("id", id)
    .is("deleted_at", null)
    .single();

  if (campaignError || !campaign) {
    console.error("Error fetching campaign:", campaignError);
    notFound();
  }

  // Aggregate metrics across all ads in the campaign
  const allMetrics =
    campaign.ad_sets?.flatMap((adSet: any) => adSet.ads?.flatMap((ad: any) => ad.metrics || []) || []) || [];

  const aggregatedMetrics = allMetrics.reduce(
    (acc: any, metric: any) => ({
      impressions: acc.impressions + (parseFloat(metric.impressions) || 0),
      clicks: acc.clicks + (parseFloat(metric.clicks) || 0),
      spend: acc.spend + (parseFloat(metric.spend) || 0),
      conversions: acc.conversions + (parseFloat(metric.conversions) || 0),
    }),
    { impressions: 0, clicks: 0, spend: 0, conversions: 0 },
  );

  // Calculate derived metrics
  const ctr = aggregatedMetrics.impressions > 0 ? (aggregatedMetrics.clicks / aggregatedMetrics.impressions) * 100 : 0;
  const cpc = aggregatedMetrics.clicks > 0 ? aggregatedMetrics.spend / aggregatedMetrics.clicks : 0;
  const cpm = aggregatedMetrics.impressions > 0 ? (aggregatedMetrics.spend / aggregatedMetrics.impressions) * 1000 : 0;

  return (
    <div className="flex flex-col gap-8 p-8">
      <div className="flex items-center gap-4">
        <Button variant="ghost" size="icon" asChild>
          <Link href="/ads">
            <ArrowLeft className="h-4 w-4" />
          </Link>
        </Button>
        <div>
          <h1 className="text-3xl font-bold tracking-tight">{campaign.name}</h1>
          <p className="text-muted-foreground">
            {campaign.account?.platform} • {campaign.objective}
          </p>
        </div>
      </div>

      <CampaignDashboard
        campaign={campaign}
        metrics={{
          ...aggregatedMetrics,
          ctr,
          cpc,
          cpm,
        }}
        adSets={campaign.ad_sets || []}
      />
    </div>
  );
}
