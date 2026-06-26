"use client";

import { formatDistanceToNow } from "date-fns";
import Link from "next/link";
import {
  Activity,
  AlertTriangle,
  BarChart3,
  Building2,
  CalendarClock,
  DollarSign,
  FileText,
  FolderOpen,
  HeartPulse,
  HelpCircle,
  Receipt,
  Server,
  ShieldCheck,
  Users,
} from "lucide-react";
import { Badge } from "@/components/ui/badge";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Progress } from "@/components/ui/progress";

type Severity = "critical" | "high" | "medium";

type AdminDashboardData = {
  generatedAt: string;
  platform: {
    clients: { total: number; active: number };
    users: { total: number; active: number };
    requests: { total: number; open: number; last30: number };
    projects: { total: number; active: number };
    documents: { total: number; last30: number };
    tickets: { total: number; open: number; urgent: number; breached: number; last30: number };
    meetings: { upcoming: number };
    contracts: { total: number; active: number };
    invoices: { total: number; unpaid: number; overdue: number };
  };
  financials: {
    paidThisMonth: number;
    createdLast30: number;
    outstandingRecent: number;
    unpaidCount: number;
    overdueCount: number;
    recentInvoices: Array<{
      id: string;
      invoiceNumber: string;
      clientName: string;
      amount: number;
      status: string;
      dueDate: string | null;
      createdAt: string | null;
    }>;
  };
  issues: Array<{
    id: string;
    title: string;
    detail: string;
    severity: Severity;
    createdAt: string | null;
  }>;
  analytics: {
    requestVolume30Days: number;
    ticketVolume30Days: number;
    documentUploads30Days: number;
    openRequestRate: number;
    openTicketRate: number;
  };
  recentTickets: Array<{
    id: string;
    ticketNumber: string;
    subject: string;
    status: string;
    priority: string;
    clientName: string;
    assignedTo: string | null;
    createdAt: string | null;
    hasSlaBreach: boolean;
  }>;
  recentActivity: Array<{
    id: string;
    description: string;
    subjectType: string;
    createdAt: string;
    actor: string | null;
  }>;
};

export function AdminDashboard({ data }: { data: AdminDashboardData }) {
  const systemStatus = data.issues.some((issue) => issue.severity === "critical")
    ? "Attention Needed"
    : data.issues.some((issue) => issue.severity === "high")
      ? "Degraded"
      : "Operational";

  const statusBadge =
    systemStatus === "Operational" ? "bg-emerald-500" : systemStatus === "Degraded" ? "bg-amber-500" : "bg-red-500";

  const formatCurrency = (amount: number) =>
    new Intl.NumberFormat("en-US", {
      style: "currency",
      currency: "USD",
      maximumFractionDigits: 2,
    }).format(amount);

  const formatWhen = (value: string | null) => {
    if (!value) {
      return "No timestamp";
    }
    return formatDistanceToNow(new Date(value), { addSuffix: true });
  };

  const severityVariant = (severity: Severity) => {
    if (severity === "critical") return "destructive";
    if (severity === "high") return "default";
    return "secondary";
  };

  const platformCards = [
    {
      title: "Clients",
      value: data.platform.clients.total,
      detail: `${data.platform.clients.active} active`,
      icon: Building2,
    },
    {
      title: "Users",
      value: data.platform.users.total,
      detail: `${data.platform.users.active} active`,
      icon: Users,
    },
    {
      title: "Requests",
      value: data.platform.requests.open,
      detail: `${data.platform.requests.last30} submitted in 30 days`,
      icon: FileText,
    },
    {
      title: "Tickets",
      value: data.platform.tickets.open,
      detail: `${data.platform.tickets.urgent} urgent, ${data.platform.tickets.breached} SLA breached`,
      icon: HelpCircle,
    },
    {
      title: "Projects",
      value: data.platform.projects.active,
      detail: `${data.platform.projects.total} total projects`,
      icon: FolderOpen,
    },
    {
      title: "Meetings",
      value: data.platform.meetings.upcoming,
      detail: "upcoming scheduled meetings",
      icon: CalendarClock,
    },
  ];

  return (
    <div className="space-y-6">
      <div className="flex flex-wrap items-start justify-between gap-4">
        <div>
          <div className="flex items-center gap-2">
            <Server className="h-7 w-7 text-primary" />
            <h1 className="text-3xl font-bold tracking-tight">Platform Status</h1>
          </div>
          <p className="mt-1 text-muted-foreground">
            Admin operations, financial snapshots, errors, and platform analytics.
          </p>
        </div>
        <div className="rounded-lg border bg-card px-4 py-3 text-right">
          <Badge className={statusBadge}>{systemStatus}</Badge>
          <p className="mt-2 text-xs text-muted-foreground">Updated {formatWhen(data.generatedAt)}</p>
        </div>
      </div>

      <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Paid This Month</CardTitle>
            <DollarSign className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{formatCurrency(data.financials.paidThisMonth)}</div>
            <p className="text-xs text-muted-foreground">{formatCurrency(data.financials.createdLast30)} invoiced in 30 days</p>
          </CardContent>
        </Card>
        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Outstanding</CardTitle>
            <Receipt className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{data.financials.unpaidCount.toLocaleString()}</div>
            <p className="text-xs text-muted-foreground">
              {data.financials.overdueCount} overdue invoices in the platform
            </p>
          </CardContent>
        </Card>
        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Open Workload</CardTitle>
            <Activity className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">
              {(data.platform.requests.open + data.platform.tickets.open).toLocaleString()}
            </div>
            <p className="text-xs text-muted-foreground">open requests and support tickets</p>
          </CardContent>
        </Card>
        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Operational Issues</CardTitle>
            <AlertTriangle className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{data.issues.length.toLocaleString()}</div>
            <p className="text-xs text-muted-foreground">errors, failed jobs, or breached SLAs</p>
          </CardContent>
        </Card>
      </div>

      <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
        {platformCards.map((item) => {
          const Icon = item.icon;
          return (
            <Card key={item.title}>
              <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle className="text-sm font-medium">{item.title}</CardTitle>
                <Icon className="h-4 w-4 text-muted-foreground" />
              </CardHeader>
              <CardContent>
                <div className="text-2xl font-bold">{item.value.toLocaleString()}</div>
                <p className="text-xs text-muted-foreground">{item.detail}</p>
              </CardContent>
            </Card>
          );
        })}
      </div>

      <div className="grid gap-4 lg:grid-cols-3">
        <Card className="lg:col-span-2">
          <CardHeader>
            <div className="flex items-center justify-between gap-2">
              <div>
                <CardTitle>Financial Snapshot</CardTitle>
                <CardDescription>Recent invoices and revenue movement from live invoice records.</CardDescription>
              </div>
              <Link href="/admin/financial" className="text-sm text-primary hover:underline">
                Financial overview
              </Link>
            </div>
          </CardHeader>
          <CardContent>
            {data.financials.recentInvoices.length === 0 ? (
              <p className="text-sm text-muted-foreground">No invoices found.</p>
            ) : (
              <div className="space-y-3">
                {data.financials.recentInvoices.map((invoice) => (
                  <div key={invoice.id} className="flex items-center justify-between gap-3 rounded-lg border p-3">
                    <div>
                      <p className="font-medium">{invoice.invoiceNumber}</p>
                      <p className="text-xs text-muted-foreground">
                        {invoice.clientName} • {formatWhen(invoice.createdAt)}
                      </p>
                    </div>
                    <div className="text-right">
                      <p className="font-semibold">{formatCurrency(invoice.amount)}</p>
                      <Badge variant={invoice.status === "paid" ? "secondary" : invoice.status === "overdue" ? "destructive" : "outline"}>
                        {invoice.status}
                      </Badge>
                    </div>
                  </div>
                ))}
              </div>
            )}
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <div className="flex items-center gap-2">
              <BarChart3 className="h-5 w-5 text-primary" />
              <CardTitle>Analytics</CardTitle>
            </div>
            <CardDescription>Last 30 days and current operational ratios.</CardDescription>
          </CardHeader>
          <CardContent className="space-y-5">
            <MetricBar label="Open request rate" value={data.analytics.openRequestRate} />
            <MetricBar label="Open ticket rate" value={data.analytics.openTicketRate} />
            <div className="grid grid-cols-3 gap-2 pt-1 text-center">
              <div className="rounded-lg border p-2">
                <p className="text-lg font-semibold">{data.analytics.requestVolume30Days}</p>
                <p className="text-xs text-muted-foreground">requests</p>
              </div>
              <div className="rounded-lg border p-2">
                <p className="text-lg font-semibold">{data.analytics.ticketVolume30Days}</p>
                <p className="text-xs text-muted-foreground">tickets</p>
              </div>
              <div className="rounded-lg border p-2">
                <p className="text-lg font-semibold">{data.analytics.documentUploads30Days}</p>
                <p className="text-xs text-muted-foreground">uploads</p>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>

      <div className="grid gap-4 lg:grid-cols-2">
        <Card>
          <CardHeader>
            <div className="flex items-center gap-2">
              <AlertTriangle className="h-5 w-5 text-destructive" />
              <CardTitle>Errors & Risks</CardTitle>
            </div>
            <CardDescription>Failed jobs, report delivery errors, urgent tickets, and breached SLAs.</CardDescription>
          </CardHeader>
          <CardContent>
            {data.issues.length === 0 ? (
              <div className="rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-700">
                No current operational issues found.
              </div>
            ) : (
              <div className="space-y-3">
                {data.issues.map((issue) => (
                  <div key={issue.id} className="rounded-lg border p-3">
                    <div className="flex items-start justify-between gap-3">
                      <div>
                        <p className="font-medium">{issue.title}</p>
                        <p className="mt-1 text-sm text-muted-foreground">{issue.detail}</p>
                      </div>
                      <Badge variant={severityVariant(issue.severity)}>{issue.severity}</Badge>
                    </div>
                    <p className="mt-2 text-xs text-muted-foreground">{formatWhen(issue.createdAt)}</p>
                  </div>
                ))}
              </div>
            )}
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <div className="flex items-center justify-between gap-2">
              <div className="flex items-center gap-2">
                <HelpCircle className="h-5 w-5 text-primary" />
                <CardTitle>Recent Support</CardTitle>
              </div>
              <Link href="/admin/tickets" className="text-sm text-primary hover:underline">
                View tickets
              </Link>
            </div>
            <CardDescription>Latest support queue entries across all clients.</CardDescription>
          </CardHeader>
          <CardContent>
            {data.recentTickets.length === 0 ? (
              <p className="text-sm text-muted-foreground">No support tickets found.</p>
            ) : (
              <div className="space-y-3">
                {data.recentTickets.map((ticket) => (
                  <div key={ticket.id} className="rounded-lg border p-3">
                    <div className="flex items-start justify-between gap-3">
                      <div>
                        <p className="font-medium">{ticket.subject}</p>
                        <p className="text-xs text-muted-foreground">
                          {ticket.ticketNumber} • {ticket.clientName} • {formatWhen(ticket.createdAt)}
                        </p>
                      </div>
                      <div className="flex gap-1">
                        {ticket.hasSlaBreach ? <Badge variant="destructive">SLA</Badge> : null}
                        <Badge variant={ticket.priority === "urgent" || ticket.priority === "high" ? "default" : "secondary"}>
                          {ticket.priority}
                        </Badge>
                      </div>
                    </div>
                    <p className="mt-2 text-xs text-muted-foreground">
                      {ticket.assignedTo ? `Assigned to ${ticket.assignedTo}` : "Unassigned"} • {ticket.status.replace(/_/g, " ")}
                    </p>
                  </div>
                ))}
              </div>
            )}
          </CardContent>
        </Card>
      </div>

      <Card>
        <CardHeader>
          <div className="flex items-center gap-2">
            <ShieldCheck className="h-5 w-5 text-primary" />
            <CardTitle>Recent Platform Activity</CardTitle>
          </div>
          <CardDescription>Latest system and user activity from the audit log.</CardDescription>
        </CardHeader>
        <CardContent>
          {data.recentActivity.length === 0 ? (
            <p className="text-sm text-muted-foreground">No recent activity found.</p>
          ) : (
            <div className="space-y-3">
              {data.recentActivity.map((activity) => (
                <div key={activity.id} className="flex items-start gap-3 border-b pb-3 last:border-0 last:pb-0">
                  <HeartPulse className="mt-0.5 h-4 w-4 text-muted-foreground" />
                  <div className="flex-1">
                    <p className="text-sm">{activity.description}</p>
                    <p className="mt-1 text-xs text-muted-foreground">
                      {activity.actor || "System"} • {activity.subjectType} • {formatWhen(activity.createdAt)}
                    </p>
                  </div>
                </div>
              ))}
            </div>
          )}
        </CardContent>
      </Card>
    </div>
  );
}

function MetricBar({ label, value }: { label: string; value: number }) {
  const clamped = Math.max(0, Math.min(100, value));

  return (
    <div className="space-y-2">
      <div className="flex items-center justify-between text-sm">
        <span className="font-medium">{label}</span>
        <span className="text-muted-foreground">{clamped}%</span>
      </div>
      <Progress value={clamped} />
    </div>
  );
}
