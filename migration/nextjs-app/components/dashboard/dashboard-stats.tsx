"use client";

import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { FileText, CheckCircle2, Receipt } from "lucide-react";

interface DashboardStatsProps {
  totalRequests: number;
  openRequests: number;
  totalInvoices: number;
}

export function DashboardStats({ totalRequests, openRequests, totalInvoices }: DashboardStatsProps) {
  const stats = [
    {
      title: "Total Requests",
      value: totalRequests,
      icon: FileText,
      description: "All time",
    },
    {
      title: "Open Requests",
      value: openRequests,
      icon: CheckCircle2,
      description: "In progress or pending",
    },
    {
      title: "Total Invoices",
      value: totalInvoices,
      icon: Receipt,
      description: "All time",
    },
  ];

  return (
    <div className="grid gap-4 md:grid-cols-3">
      {stats.map((stat) => (
        <Card key={stat.title}>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">{stat.title}</CardTitle>
            <stat.icon className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{stat.value}</div>
            <p className="text-xs text-muted-foreground">{stat.description}</p>
          </CardContent>
        </Card>
      ))}
    </div>
  );
}
