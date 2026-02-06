import { Suspense } from "react";
import { LeadList } from "@/components/marketing/lead-list";
import { Button } from "@/components/ui/button";
import { PlusCircle } from "lucide-react";
import Link from "next/link";

export const metadata = {
  title: "Leads | Marketing",
  description: "Manage your marketing leads",
};

export default function LeadsPage() {
  return (
    <div className="flex flex-col gap-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold tracking-tight">Lead Management</h1>
          <p className="text-muted-foreground">Track and nurture your marketing leads through the sales pipeline</p>
        </div>
        <Link href="/marketing/leads/new">
          <Button>
            <PlusCircle className="mr-2 h-4 w-4" />
            New Lead
          </Button>
        </Link>
      </div>

      <Suspense fallback={<div>Loading leads...</div>}>
        <LeadList />
      </Suspense>
    </div>
  );
}
