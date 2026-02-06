import { Suspense } from "react";
import { CompetitorAnalysisDashboard } from "@/components/brand/competitor-analysis-dashboard";
import { CompetitorList } from "@/components/brand/competitor-list";
import { AddCompetitorDialog } from "@/components/brand/add-competitor-dialog";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { Skeleton } from "@/components/ui/skeleton";
import { Button } from "@/components/ui/button";
import { Plus } from "lucide-react";

export const metadata = {
  title: "Competitor Analysis",
  description: "Track and analyze competitors",
};

function CompetitorLoading() {
  return (
    <div className="space-y-4">
      <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
        {[...Array(6)].map((_, i) => (
          <Skeleton key={i} className="h-48" />
        ))}
      </div>
    </div>
  );
}

export default function CompetitorsPage() {
  return (
    <div className="container mx-auto py-6 space-y-6">
      <div className="flex flex-col gap-4">
        <div className="flex items-center justify-between">
          <div>
            <h1 className="text-3xl font-bold tracking-tight">Competitor Analysis</h1>
            <p className="text-muted-foreground">Track competitors and analyze their positioning</p>
          </div>
          <AddCompetitorDialog>
            <Button>
              <Plus className="mr-2 h-4 w-4" />
              Add Competitor
            </Button>
          </AddCompetitorDialog>
        </div>

        <Tabs defaultValue="overview" className="w-full">
          <TabsList>
            <TabsList value="overview">Overview</TabsList>
            <TabsTrigger value="active">Active Competitors</TabsTrigger>
            <TabsTrigger value="all">All Competitors</TabsTrigger>
          </TabsList>

          <TabsContent value="overview" className="space-y-4">
            <Suspense fallback={<CompetitorLoading />}>
              <CompetitorAnalysisDashboard />
            </Suspense>
          </TabsContent>

          <TabsContent value="active" className="space-y-4">
            <Suspense fallback={<CompetitorLoading />}>
              <CompetitorList activeOnly />
            </Suspense>
          </TabsContent>

          <TabsContent value="all" className="space-y-4">
            <Suspense fallback={<CompetitorLoading />}>
              <CompetitorList />
            </Suspense>
          </TabsContent>
        </Tabs>
      </div>
    </div>
  );
}
