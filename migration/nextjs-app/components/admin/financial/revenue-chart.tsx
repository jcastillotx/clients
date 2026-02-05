"use client";

import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { format, parseISO } from "date-fns";
import { useMemo } from "react";

interface RevenueChartProps {
  data: Array<{
    amount: number;
    paid_at: string | null;
    created_at: string;
  }>;
}

export function RevenueChart({ data }: RevenueChartProps) {
  // Group revenue by day
  const chartData = useMemo(() => {
    const grouped = data.reduce(
      (acc, item) => {
        const date = item.paid_at
          ? format(parseISO(item.paid_at), "yyyy-MM-dd")
          : format(parseISO(item.created_at), "yyyy-MM-dd");
        if (!acc[date]) {
          acc[date] = 0;
        }
        acc[date] += item.amount;
        return acc;
      },
      {} as Record<string, number>,
    );

    return Object.entries(grouped)
      .map(([date, amount]) => ({
        date,
        amount,
        displayDate: format(parseISO(date), "MMM dd"),
      }))
      .sort((a, b) => a.date.localeCompare(b.date))
      .slice(-30); // Last 30 days
  }, [data]);

  const maxAmount = Math.max(...chartData.map((d) => d.amount), 0);
  const totalRevenue = chartData.reduce((sum, d) => sum + d.amount, 0);

  return (
    <Card>
      <CardHeader>
        <CardTitle>Revenue Trend</CardTitle>
        <CardDescription>Last 30 days - Total: ${totalRevenue.toLocaleString()}</CardDescription>
      </CardHeader>
      <CardContent>
        <div className="space-y-4">
          {/* Simple bar chart */}
          <div className="flex items-end gap-1 h-64">
            {chartData.map((item) => {
              const heightPercent = maxAmount > 0 ? (item.amount / maxAmount) * 100 : 0;
              return (
                <div key={item.date} className="flex-1 flex flex-col items-center gap-2 group">
                  <div className="relative w-full">
                    <div
                      className="w-full bg-primary rounded-t transition-all hover:bg-primary/80"
                      style={{ height: `${Math.max(heightPercent * 2.4, 2)}px` }}
                      title={`${item.displayDate}: $${item.amount.toLocaleString()}`}
                    />
                  </div>
                  {/* Show every 5th label to avoid crowding */}
                  {chartData.indexOf(item) % 5 === 0 && (
                    <span className="text-xs text-muted-foreground rotate-45 origin-top-left whitespace-nowrap">
                      {item.displayDate}
                    </span>
                  )}
                </div>
              );
            })}
          </div>

          {/* Legend */}
          <div className="flex justify-between text-sm text-muted-foreground">
            <span>Daily Revenue</span>
            <span>Max: ${Math.max(...chartData.map((d) => d.amount)).toLocaleString()}</span>
          </div>
        </div>
      </CardContent>
    </Card>
  );
}
