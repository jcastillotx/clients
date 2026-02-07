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
        <Card key={stat.title} className="bg-gradient-to-br from-card to-secondary/20">
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">{stat.title}</CardTitle>
            <div className="rounded-lg bg-primary/10 p-2 text-primary">
              <stat.icon className="h-4 w-4" />
            </div>
          </CardHeader>
          <CardContent>
            <div className="text-3xl font-bold">{stat.value}</div>
            <p className="text-xs text-muted-foreground">{stat.description}</p>
          </CardContent>
        </Card>
      ))}
    </div>
  );
}
