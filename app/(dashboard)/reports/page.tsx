import { Suspense } from "react";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Plus, FileText, Calendar, TrendingUp } from "lucide-react";
import Link from "next/link";
import { ReportsList } from "@/components/reports/reports-list";
import { ReportStats } from "@/components/reports/report-stats";
import { ScheduledReportsList } from "@/components/reports/scheduled-reports-list";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";

export const metadata = {
  title: "Reports & Analytics",
  description: "Generate and manage reports and custom dashboards",
};

export default function ReportsPage() {
  return (
    <div className="container mx-auto py-8 space-y-8">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold tracking-tight">Reports & Analytics</h1>
          <p className="text-muted-foreground">Generate custom reports and manage scheduled deliveries</p>
        </div>
        <div className="flex gap-2">
          <Link href="/reports/custom">
            <Button variant="outline">
              <TrendingUp className="mr-2 h-4 w-4" />
              Custom Dashboard
            </Button>
          </Link>
          <Link href="/reports/custom">
            <Button>
              <Plus className="mr-2 h-4 w-4" />
              New Report
            </Button>
          </Link>
        </div>
      </div>

      <Suspense fallback={<StatsLoadingSkeleton />}>
        <ReportStats />
      </Suspense>

      <Tabs defaultValue="templates" className="space-y-4">
        <TabsList>
          <TabsTrigger value="templates">
            <FileText className="mr-2 h-4 w-4" />
            Templates
          </TabsTrigger>
          <TabsTrigger value="scheduled">
            <Calendar className="mr-2 h-4 w-4" />
            Scheduled Reports
          </TabsTrigger>
        </TabsList>

        <TabsContent value="templates" className="space-y-4">
          <Card>
            <CardHeader>
              <CardTitle>Report Templates</CardTitle>
              <CardDescription>Create and manage reusable report templates</CardDescription>
            </CardHeader>
            <CardContent>
              <Suspense fallback={<ListLoadingSkeleton />}>
                <ReportsList />
              </Suspense>
            </CardContent>
          </Card>
        </TabsContent>

        <TabsContent value="scheduled" className="space-y-4">
          <Card>
            <CardHeader>
              <CardTitle>Scheduled Reports</CardTitle>
              <CardDescription>Automated report generation and delivery</CardDescription>
            </CardHeader>
            <CardContent>
              <Suspense fallback={<ListLoadingSkeleton />}>
                <ScheduledReportsList />
              </Suspense>
            </CardContent>
          </Card>
        </TabsContent>
      </Tabs>
    </div>
  );
}

function StatsLoadingSkeleton() {
  return (
    <div className="grid gap-4 md:grid-cols-4">
      {[1, 2, 3, 4].map((i) => (
        <Card key={i}>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <div className="h-4 w-24 bg-muted animate-pulse rounded" />
            <div className="h-4 w-4 bg-muted animate-pulse rounded" />
          </CardHeader>
          <CardContent>
            <div className="h-8 w-16 bg-muted animate-pulse rounded mb-2" />
            <div className="h-3 w-32 bg-muted animate-pulse rounded" />
          </CardContent>
        </Card>
      ))}
    </div>
  );
}

function ListLoadingSkeleton() {
  return (
    <div className="space-y-4">
      {[1, 2, 3].map((i) => (
        <div key={i} className="flex items-center space-x-4 p-4 border rounded-lg">
          <div className="h-12 w-12 bg-muted animate-pulse rounded" />
          <div className="flex-1 space-y-2">
            <div className="h-4 w-48 bg-muted animate-pulse rounded" />
            <div className="h-3 w-64 bg-muted animate-pulse rounded" />
          </div>
          <div className="h-8 w-24 bg-muted animate-pulse rounded" />
        </div>
      ))}
    </div>
  );
}
