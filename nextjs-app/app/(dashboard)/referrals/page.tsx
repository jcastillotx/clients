import { Suspense } from "react";
import { ReferralsTable } from "@/components/referrals/referrals-table";
import { ReferralsStats } from "@/components/referrals/referrals-stats";
import { Button } from "@/components/ui/button";
import { Plus } from "lucide-react";
import Link from "next/link";

export const metadata = {
  title: "Referrals | Dashboard",
  description: "Track and manage client referrals",
};

export default function ReferralsPage() {
  return (
    <div className="container mx-auto py-6 space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold tracking-tight">Referrals</h1>
          <p className="text-muted-foreground mt-1">Track partner referrals and conversion pipeline</p>
        </div>
        <Button asChild>
          <Link href="/referrals/new">
            <Plus className="mr-2 h-4 w-4" />
            Add Referral
          </Link>
        </Button>
      </div>

      <Suspense fallback={<div>Loading stats...</div>}>
        <ReferralsStats />
      </Suspense>

      <Suspense fallback={<div>Loading referrals...</div>}>
        <ReferralsTable />
      </Suspense>
    </div>
  );
}
