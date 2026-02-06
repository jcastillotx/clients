import { Suspense } from "react";
import { CampaignList } from "@/components/marketing/campaign-list";
import { Button } from "@/components/ui/button";
import { PlusCircle } from "lucide-react";
import Link from "next/link";

export const metadata = {
  title: "Campaigns | Marketing",
  description: "Manage your marketing campaigns",
};

export default function CampaignsPage() {
  return (
    <div className="flex flex-col gap-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold tracking-tight">Marketing Campaigns</h1>
          <p className="text-muted-foreground">Create and manage marketing campaigns across all channels</p>
        </div>
        <Link href="/marketing/campaigns/new">
          <Button>
            <PlusCircle className="mr-2 h-4 w-4" />
            New Campaign
          </Button>
        </Link>
      </div>

      <Suspense fallback={<div>Loading campaigns...</div>}>
        <CampaignList />
      </Suspense>
    </div>
  );
}
