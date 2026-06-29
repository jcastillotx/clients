import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { FileText, Calendar, Send, TrendingUp } from "lucide-react";

interface ReportStatsProps {
  totalTemplates: number;
  scheduledReports: number;
  reportsSent: number;
  activeSchedules: number;
}

export function ReportStats({ totalTemplates, scheduledReports, reportsSent, activeSchedules }: ReportStatsProps) {
  const activePercent =
    scheduledReports > 0 ? ((activeSchedules / scheduledReports) * 100).toFixed(0) : "0";

  return (
    <div className="grid gap-4 md:grid-cols-4">
      <Card>
        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
          <CardTitle className="text-sm font-medium">Report Templates</CardTitle>
          <FileText className="h-4 w-4 text-muted-foreground" />
        </CardHeader>
        <CardContent>
          <div className="text-2xl font-bold">{totalTemplates}</div>
          <p className="text-xs text-muted-foreground">Available templates</p>
        </CardContent>
      </Card>

      <Card>
        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
          <CardTitle className="text-sm font-medium">Scheduled Reports</CardTitle>
          <Calendar className="h-4 w-4 text-muted-foreground" />
        </CardHeader>
        <CardContent>
          <div className="text-2xl font-bold">{scheduledReports}</div>
          <p className="text-xs text-muted-foreground">{activeSchedules} active</p>
        </CardContent>
      </Card>

      <Card>
        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
          <CardTitle className="text-sm font-medium">Reports Sent</CardTitle>
          <Send className="h-4 w-4 text-muted-foreground" />
        </CardHeader>
        <CardContent>
          <div className="text-2xl font-bold">{reportsSent}</div>
          <p className="text-xs text-muted-foreground">Last 30 days</p>
        </CardContent>
      </Card>

      <Card>
        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
          <CardTitle className="text-sm font-medium">Active Schedules</CardTitle>
          <TrendingUp className="h-4 w-4 text-muted-foreground" />
        </CardHeader>
        <CardContent>
          <div className="text-2xl font-bold">{activeSchedules}</div>
          <p className="text-xs text-muted-foreground">{activePercent}% of total</p>
        </CardContent>
      </Card>
    </div>
  );
}
