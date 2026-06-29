import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Plus, FileText, Calendar, TrendingUp } from "lucide-react";
import Link from "next/link";
import { ReportsList } from "@/components/reports/reports-list";
import { ReportStats } from "@/components/reports/report-stats";
import { ScheduledReportsList } from "@/components/reports/scheduled-reports-list";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { createClient } from "@/lib/supabase/server";

export const metadata = {
  title: "Reports & Analytics",
  description: "Generate and manage reports and custom dashboards",
};

export default async function ReportsPage() {
  const supabase = await createClient();

  const {
    data: { user },
  } = await supabase.auth.getUser();

  const [templatesResult, schedulesResult, deliveriesResult] = await Promise.all([
    supabase
      .from("report_templates")
      .select("*, created_by_user:users!report_templates_created_by_fkey(id, name)")
      .order("created_at", { ascending: false }),
    user
      ? supabase
          .from("report_schedules")
          .select("*, template:report_templates(id, name, report_type)")
          .order("created_at", { ascending: false })
      : Promise.resolve({ data: [], error: null }),
    supabase
      .from("report_deliveries")
      .select("id, sent_at, status")
      .eq("status", "sent")
      .gte("sent_at", new Date(Date.now() - 30 * 24 * 60 * 60 * 1000).toISOString()),
  ]);

  const templates = templatesResult.data ?? [];
  const schedules = schedulesResult.data ?? [];
  const deliveries = deliveriesResult.data ?? [];

  const totalSchedules = schedules.length;
  const activeSchedules = schedules.filter((s) => s.is_active).length;
  const reportsSent = deliveries.length;

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

      <ReportStats
        totalTemplates={templates.length}
        scheduledReports={totalSchedules}
        reportsSent={reportsSent}
        activeSchedules={activeSchedules}
      />

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
              <ReportsList templates={templates} />
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
              <ScheduledReportsList schedules={schedules} />
            </CardContent>
          </Card>
        </TabsContent>
      </Tabs>
    </div>
  );
}
