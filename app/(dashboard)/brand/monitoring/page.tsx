import { Suspense } from "react";
import { BrandMentionsDashboard } from "@/components/brand/brand-mentions-dashboard";
import { BrandMentionsStats } from "@/components/brand/brand-mentions-stats";
import { BrandMentionsList } from "@/components/brand/brand-mentions-list";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { Skeleton } from "@/components/ui/skeleton";

export const metadata = {
  title: "Brand Monitoring",
  description: "Track brand mentions and sentiment across platforms",
};

function BrandMonitoringLoading() {
  return (
    <div className="space-y-4">
      <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
        {[...Array(4)].map((_, i) => (
          <Skeleton key={i} className="h-32" />
        ))}
      </div>
      <Skeleton className="h-96 w-full" />
    </div>
  );
}

export default function BrandMonitoringPage() {
  return (
    <div className="container mx-auto py-6 space-y-6">
      <div className="flex flex-col gap-4">
        <div>
          <h1 className="text-3xl font-bold tracking-tight">Brand Monitoring</h1>
          <p className="text-muted-foreground">Monitor brand mentions, sentiment, and engagement across platforms</p>
        </div>

        <Suspense fallback={<BrandMonitoringLoading />}>
          <BrandMentionsStats />
        </Suspense>

        <Tabs defaultValue="all" className="w-full">
          <TabsList>
            <TabsTrigger value="all">All Mentions</TabsTrigger>
            <TabsTrigger value="positive">Positive</TabsTrigger>
            <TabsTrigger value="neutral">Neutral</TabsTrigger>
            <TabsTrigger value="negative">Negative</TabsTrigger>
            <TabsTrigger value="needs-response">Needs Response</TabsTrigger>
          </TabsList>

          <TabsContent value="all" className="space-y-4">
            <Suspense fallback={<BrandMonitoringLoading />}>
              <BrandMentionsList />
            </Suspense>
          </TabsContent>

          <TabsContent value="positive" className="space-y-4">
            <Suspense fallback={<BrandMonitoringLoading />}>
              <BrandMentionsList sentiment="positive" />
            </Suspense>
          </TabsContent>

          <TabsContent value="neutral" className="space-y-4">
            <Suspense fallback={<BrandMonitoringLoading />}>
              <BrandMentionsList sentiment="neutral" />
            </Suspense>
          </TabsContent>

          <TabsContent value="negative" className="space-y-4">
            <Suspense fallback={<BrandMonitoringLoading />}>
              <BrandMentionsList sentiment="negative" />
            </Suspense>
          </TabsContent>

          <TabsContent value="needs-response" className="space-y-4">
            <Suspense fallback={<BrandMonitoringLoading />}>
              <BrandMentionsList needsResponse />
            </Suspense>
          </TabsContent>
        </Tabs>
      </div>
    </div>
  );
}
