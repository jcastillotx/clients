import { Suspense } from "react";
import { PartnersTable } from "@/components/partners/partners-table";
import { PartnersStats } from "@/components/partners/partners-stats";
import { Button } from "@/components/ui/button";
import { Plus } from "lucide-react";
import Link from "next/link";

export const metadata = {
  title: "Partners | Dashboard",
  description: "Manage partner organizations and track referrals",
};

export default function PartnersPage() {
  return (
    <div className="container mx-auto py-6 space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold tracking-tight">Partners</h1>
          <p className="text-muted-foreground mt-1">Manage partner organizations and track their referrals</p>
        </div>
        <Button asChild>
          <Link href="/partners/new">
            <Plus className="mr-2 h-4 w-4" />
            Add Partner
          </Link>
        </Button>
      </div>

      <Suspense fallback={<div>Loading stats...</div>}>
        <PartnersStats />
      </Suspense>

      <Suspense fallback={<div>Loading partners...</div>}>
        <PartnersTable />
      </Suspense>
    </div>
  );
}
