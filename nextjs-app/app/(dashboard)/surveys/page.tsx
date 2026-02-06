import { Suspense } from "react";
import { SurveysTable } from "@/components/surveys/surveys-table";
import { SurveysStats } from "@/components/surveys/surveys-stats";
import { Button } from "@/components/ui/button";
import { Plus } from "lucide-react";
import Link from "next/link";

export const metadata = {
  title: "Surveys | Dashboard",
  description: "Manage client satisfaction surveys and feedback",
};

export default function SurveysPage() {
  return (
    <div className="container mx-auto py-6 space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold tracking-tight">Surveys</h1>
          <p className="text-muted-foreground mt-1">Client satisfaction surveys and feedback collection</p>
        </div>
        <Button asChild>
          <Link href="/surveys/new">
            <Plus className="mr-2 h-4 w-4" />
            Create Survey
          </Link>
        </Button>
      </div>

      <Suspense fallback={<div>Loading stats...</div>}>
        <SurveysStats />
      </Suspense>

      <Suspense fallback={<div>Loading surveys...</div>}>
        <SurveysTable />
      </Suspense>
    </div>
  );
}
