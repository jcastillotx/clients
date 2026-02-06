"use client";

import { useState, useEffect } from "react";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Label } from "@/components/ui/label";
import { Input } from "@/components/ui/input";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { BarChart3, TrendingUp, DollarSign, Clock } from "lucide-react";
import { toast } from "sonner";
import { format, startOfWeek, endOfWeek, startOfMonth, endOfMonth, subDays } from "date-fns";

interface ReportData {
  summary: {
    totalMinutes: number;
    totalHours: number;
    billableMinutes: number;
    billableHours: number;
    nonBillableMinutes: number;
    nonBillableHours: number;
    totalAmount: number;
    entriesCount: number;
  };
  groupedData: any[];
  entries: any[];
}

export function TimeReport() {
  const [loading, setLoading] = useState(false);
  const [reportData, setReportData] = useState<ReportData | null>(null);
  const [startDate, setStartDate] = useState("");
  const [endDate, setEndDate] = useState("");
  const [groupBy, setGroupBy] = useState("day");

  useEffect(() => {
    // Set default to current week
    const now = new Date();
    const weekStart = startOfWeek(now, { weekStartsOn: 1 });
    const weekEnd = endOfWeek(now, { weekStartsOn: 1 });
    setStartDate(format(weekStart, "yyyy-MM-dd"));
    setEndDate(format(weekEnd, "yyyy-MM-dd"));
  }, []);

  const generateReport = async () => {
    if (!startDate || !endDate) {
      toast.error("Please select start and end dates");
      return;
    }

    try {
      setLoading(true);

      const params = new URLSearchParams({
        startDate,
        endDate,
        groupBy,
      });

      const response = await fetch(`/api/time-tracking/reports?${params}`);
      if (!response.ok) {
        throw new Error("Failed to generate report");
      }

      const data = await response.json();
      setReportData(data);
    } catch (error) {
      toast.error("Failed to generate report");
    } finally {
      setLoading(false);
    }
  };

  const setQuickDate = (type: "today" | "yesterday" | "thisWeek" | "lastWeek" | "thisMonth" | "lastMonth") => {
    const now = new Date();
    let start: Date;
    let end: Date;

    switch (type) {
      case "today":
        start = now;
        end = now;
        break;
      case "yesterday":
        start = subDays(now, 1);
        end = subDays(now, 1);
        break;
      case "thisWeek":
        start = startOfWeek(now, { weekStartsOn: 1 });
        end = endOfWeek(now, { weekStartsOn: 1 });
        break;
      case "lastWeek":
        const lastWeek = subDays(now, 7);
        start = startOfWeek(lastWeek, { weekStartsOn: 1 });
        end = endOfWeek(lastWeek, { weekStartsOn: 1 });
        break;
      case "thisMonth":
        start = startOfMonth(now);
        end = endOfMonth(now);
        break;
      case "lastMonth":
        const lastMonth = new Date(now.getFullYear(), now.getMonth() - 1, 1);
        start = startOfMonth(lastMonth);
        end = endOfMonth(lastMonth);
        break;
    }

    setStartDate(format(start, "yyyy-MM-dd"));
    setEndDate(format(end, "yyyy-MM-dd"));
  };

  const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat("en-US", {
      style: "currency",
      currency: "USD",
    }).format(amount);
  };

  const formatHours = (hours: number) => {
    return `${hours.toFixed(2)}h`;
  };

  return (
    <div className="space-y-6">
      {/* Report Controls */}
      <Card>
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            <BarChart3 className="h-5 w-5" />
            Generate Time Report
          </CardTitle>
        </CardHeader>
        <CardContent className="space-y-4">
          {/* Quick Date Buttons */}
          <div className="flex flex-wrap gap-2">
            <Button variant="outline" size="sm" onClick={() => setQuickDate("today")}>
              Today
            </Button>
            <Button variant="outline" size="sm" onClick={() => setQuickDate("yesterday")}>
              Yesterday
            </Button>
            <Button variant="outline" size="sm" onClick={() => setQuickDate("thisWeek")}>
              This Week
            </Button>
            <Button variant="outline" size="sm" onClick={() => setQuickDate("lastWeek")}>
              Last Week
            </Button>
            <Button variant="outline" size="sm" onClick={() => setQuickDate("thisMonth")}>
              This Month
            </Button>
            <Button variant="outline" size="sm" onClick={() => setQuickDate("lastMonth")}>
              Last Month
            </Button>
          </div>

          {/* Date Range and Group By */}
          <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div className="space-y-2">
              <Label htmlFor="reportStartDate">Start Date</Label>
              <Input
                id="reportStartDate"
                type="date"
                value={startDate}
                onChange={(e) => setStartDate(e.target.value)}
              />
            </div>

            <div className="space-y-2">
              <Label htmlFor="reportEndDate">End Date</Label>
              <Input id="reportEndDate" type="date" value={endDate} onChange={(e) => setEndDate(e.target.value)} />
            </div>

            <div className="space-y-2">
              <Label htmlFor="groupBy">Group By</Label>
              <Select value={groupBy} onValueChange={setGroupBy}>
                <SelectTrigger id="groupBy">
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="day">Day</SelectItem>
                  <SelectItem value="week">Week</SelectItem>
                  <SelectItem value="month">Month</SelectItem>
                  <SelectItem value="client">Client</SelectItem>
                  <SelectItem value="request">Request</SelectItem>
                </SelectContent>
              </Select>
            </div>
          </div>

          <Button onClick={generateReport} disabled={loading} className="w-full">
            {loading ? "Generating..." : "Generate Report"}
          </Button>
        </CardContent>
      </Card>

      {/* Summary Cards */}
      {reportData && (
        <>
          <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
            <Card>
              <CardContent className="pt-6">
                <div className="flex items-center justify-between">
                  <div>
                    <p className="text-sm text-muted-foreground">Total Hours</p>
                    <p className="text-2xl font-bold">{formatHours(reportData.summary.totalHours)}</p>
                  </div>
                  <Clock className="h-8 w-8 text-muted-foreground" />
                </div>
              </CardContent>
            </Card>

            <Card>
              <CardContent className="pt-6">
                <div className="flex items-center justify-between">
                  <div>
                    <p className="text-sm text-muted-foreground">Billable Hours</p>
                    <p className="text-2xl font-bold text-green-600">{formatHours(reportData.summary.billableHours)}</p>
                  </div>
                  <TrendingUp className="h-8 w-8 text-green-600" />
                </div>
              </CardContent>
            </Card>

            <Card>
              <CardContent className="pt-6">
                <div className="flex items-center justify-between">
                  <div>
                    <p className="text-sm text-muted-foreground">Non-Billable Hours</p>
                    <p className="text-2xl font-bold text-orange-600">
                      {formatHours(reportData.summary.nonBillableHours)}
                    </p>
                  </div>
                  <Clock className="h-8 w-8 text-orange-600" />
                </div>
              </CardContent>
            </Card>

            <Card>
              <CardContent className="pt-6">
                <div className="flex items-center justify-between">
                  <div>
                    <p className="text-sm text-muted-foreground">Total Amount</p>
                    <p className="text-2xl font-bold">{formatCurrency(reportData.summary.totalAmount)}</p>
                  </div>
                  <DollarSign className="h-8 w-8 text-muted-foreground" />
                </div>
              </CardContent>
            </Card>
          </div>

          {/* Grouped Data Table */}
          <Card>
            <CardHeader>
              <CardTitle>Breakdown by {groupBy.charAt(0).toUpperCase() + groupBy.slice(1)}</CardTitle>
            </CardHeader>
            <CardContent>
              <div className="rounded-md border">
                <table className="w-full">
                  <thead>
                    <tr className="border-b bg-muted/50">
                      <th className="p-3 text-left font-medium">
                        {groupBy === "day" && "Date"}
                        {groupBy === "week" && "Week Starting"}
                        {groupBy === "month" && "Month"}
                        {groupBy === "client" && "Client"}
                        {groupBy === "request" && "Request"}
                      </th>
                      <th className="p-3 text-right font-medium">Total Hours</th>
                      <th className="p-3 text-right font-medium">Billable Hours</th>
                      <th className="p-3 text-right font-medium">Total Amount</th>
                      <th className="p-3 text-right font-medium">Entries</th>
                    </tr>
                  </thead>
                  <tbody>
                    {reportData.groupedData.map((row, index) => (
                      <tr key={index} className="border-b">
                        <td className="p-3">
                          {row.date || row.week || row.month || row.clientName || row.requestTitle}
                        </td>
                        <td className="p-3 text-right">{formatHours(row.totalMinutes / 60)}</td>
                        <td className="p-3 text-right">{formatHours(row.billableMinutes / 60)}</td>
                        <td className="p-3 text-right">{formatCurrency(row.totalAmount)}</td>
                        <td className="p-3 text-right">{row.entries}</td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            </CardContent>
          </Card>
        </>
      )}
    </div>
  );
}
