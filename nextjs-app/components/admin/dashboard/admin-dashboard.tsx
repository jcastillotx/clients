"use client";

import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import {
  Users,
  Building2,
  FileText,
  DollarSign,
  FileSignature,
  TrendingUp,
  AlertTriangle,
  Activity,
} from "lucide-react";
import { formatDistanceToNow } from "date-fns";

interface Stats {
  clients: { total: number; active: number };
  users: { total: number };
  requests: { total: number; pending: number };
  invoices: { total: number; unpaid: number };
  contracts: { total: number; active: number };
}

interface ActivityLog {
  id: string;
  description: string;
  created_at: string;
  causer?: {
    id: string;
    name: string;
    email: string;
  } | null;
}

interface TopClient {
  id: string;
  company_name: string;
  totalRevenue: number;
}

interface SLABreach {
  id: string;
  title: string;
  request_number: string;
  sla_breach_type: string;
  sla_breach_at: string;
  client?: {
    id: string;
    company_name: string;
  } | null;
}

interface AdminDashboardProps {
  stats: Stats;
  recentActivity: ActivityLog[];
  topClients: TopClient[];
  slaBreaches: SLABreach[];
}

export function AdminDashboard({ stats, recentActivity, topClients, slaBreaches }: AdminDashboardProps) {
  const statCards = [
    {
      title: "Total Clients",
      value: stats.clients.total,
      subtitle: `${stats.clients.active} active`,
      icon: Building2,
      color: "text-blue-600",
      bgColor: "bg-blue-100",
    },
    {
      title: "Total Users",
      value: stats.users.total,
      icon: Users,
      color: "text-green-600",
      bgColor: "bg-green-100",
    },
    {
      title: "Requests",
      value: stats.requests.total,
      subtitle: `${stats.requests.pending} pending`,
      icon: FileText,
      color: "text-purple-600",
      bgColor: "bg-purple-100",
    },
    {
      title: "Invoices",
      value: stats.invoices.total,
      subtitle: `${stats.invoices.unpaid} unpaid`,
      icon: DollarSign,
      color: "text-yellow-600",
      bgColor: "bg-yellow-100",
    },
    {
      title: "Contracts",
      value: stats.contracts.total,
      subtitle: `${stats.contracts.active} active`,
      icon: FileSignature,
      color: "text-indigo-600",
      bgColor: "bg-indigo-100",
    },
  ];

  const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat("en-US", {
      style: "currency",
      currency: "USD",
    }).format(amount);
  };

  return (
    <div className="space-y-6">
      {/* Header */}
      <div>
        <h1 className="text-3xl font-bold tracking-tight">Admin Dashboard</h1>
        <p className="text-muted-foreground">System overview and key metrics</p>
      </div>

      {/* Stats Grid */}
      <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-5">
        {statCards.map((stat) => {
          const Icon = stat.icon;
          return (
            <Card key={stat.title}>
              <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle className="text-sm font-medium">{stat.title}</CardTitle>
                <div className={`rounded-lg p-2 ${stat.bgColor}`}>
                  <Icon className={`h-4 w-4 ${stat.color}`} />
                </div>
              </CardHeader>
              <CardContent>
                <div className="text-2xl font-bold">{stat.value.toLocaleString()}</div>
                {stat.subtitle && <p className="text-xs text-muted-foreground">{stat.subtitle}</p>}
              </CardContent>
            </Card>
          );
        })}
      </div>

      {/* Main Content Grid */}
      <div className="grid gap-4 md:grid-cols-2">
        {/* Top Clients by Revenue */}
        <Card>
          <CardHeader>
            <div className="flex items-center gap-2">
              <TrendingUp className="h-5 w-5 text-green-600" />
              <CardTitle>Top Clients by Revenue</CardTitle>
            </div>
            <CardDescription>Highest revenue generating clients</CardDescription>
          </CardHeader>
          <CardContent>
            {topClients.length === 0 ? (
              <p className="text-sm text-muted-foreground">No revenue data available</p>
            ) : (
              <div className="space-y-3">
                {topClients.map((client, index) => (
                  <div key={client.id} className="flex items-center justify-between">
                    <div className="flex items-center gap-3">
                      <div className="flex h-8 w-8 items-center justify-center rounded-full bg-primary/10 text-sm font-semibold text-primary">
                        {index + 1}
                      </div>
                      <div>
                        <p className="font-medium">{client.company_name}</p>
                      </div>
                    </div>
                    <div className="text-right">
                      <p className="font-semibold">{formatCurrency(client.totalRevenue)}</p>
                    </div>
                  </div>
                ))}
              </div>
            )}
          </CardContent>
        </Card>

        {/* SLA Breaches */}
        <Card>
          <CardHeader>
            <div className="flex items-center gap-2">
              <AlertTriangle className="h-5 w-5 text-red-600" />
              <CardTitle>Recent SLA Breaches</CardTitle>
            </div>
            <CardDescription>Requests that exceeded SLA targets</CardDescription>
          </CardHeader>
          <CardContent>
            {slaBreaches.length === 0 ? (
              <div className="flex items-center justify-center py-8">
                <div className="text-center">
                  <p className="text-sm font-medium text-green-600">No SLA breaches</p>
                  <p className="text-xs text-muted-foreground">All requests within SLA</p>
                </div>
              </div>
            ) : (
              <div className="space-y-3">
                {slaBreaches.map((breach) => (
                  <div key={breach.id} className="space-y-1 rounded-lg border p-3">
                    <div className="flex items-start justify-between">
                      <div className="flex-1">
                        <p className="font-medium">{breach.title}</p>
                        <p className="text-xs text-muted-foreground">
                          {breach.request_number} • {breach.client?.company_name}
                        </p>
                      </div>
                      <Badge variant="destructive" className="text-xs">
                        {breach.sla_breach_type}
                      </Badge>
                    </div>
                    <p className="text-xs text-muted-foreground">
                      {formatDistanceToNow(new Date(breach.sla_breach_at), {
                        addSuffix: true,
                      })}
                    </p>
                  </div>
                ))}
              </div>
            )}
          </CardContent>
        </Card>
      </div>

      {/* Recent Activity */}
      <Card>
        <CardHeader>
          <div className="flex items-center gap-2">
            <Activity className="h-5 w-5 text-blue-600" />
            <CardTitle>Recent Activity</CardTitle>
          </div>
          <CardDescription>Latest system events and user actions</CardDescription>
        </CardHeader>
        <CardContent>
          {recentActivity.length === 0 ? (
            <p className="text-sm text-muted-foreground">No recent activity</p>
          ) : (
            <div className="space-y-3">
              {recentActivity.map((activity) => (
                <div key={activity.id} className="flex items-start gap-3 border-b pb-3 last:border-0">
                  <div className="mt-1 h-2 w-2 rounded-full bg-primary" />
                  <div className="flex-1 space-y-1">
                    <p className="text-sm">{activity.description}</p>
                    <div className="flex items-center gap-2 text-xs text-muted-foreground">
                      {activity.causer && <span>{activity.causer.name}</span>}
                      <span>•</span>
                      <span>
                        {formatDistanceToNow(new Date(activity.created_at), {
                          addSuffix: true,
                        })}
                      </span>
                    </div>
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
