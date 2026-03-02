"use client";

import { useEffect, useMemo, useState } from "react";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { ClipboardList, BarChart, CheckCircle, TrendingUp } from "lucide-react";

export function SurveysStats() {
  const [surveys, setSurveys] = useState<Array<{ is_active: boolean; response_count: number }>>([]);

  useEffect(() => {
    const fetchSurveys = async () => {
      try {
        const response = await fetch("/api/surveys", {
          method: "GET",
          cache: "no-store",
        });
        const payload = await response.json();
        if (!response.ok) {
          return;
        }
        setSurveys((payload?.data || []) as Array<{ is_active: boolean; response_count: number }>);
      } catch {
        // Keep zeroed stats if fetch fails.
      }
    };

    void fetchSurveys();
  }, []);

  const stats = useMemo(() => {
    const totalSurveys = surveys.length;
    const activeSurveys = surveys.filter((survey) => survey.is_active).length;
    const totalResponses = surveys.reduce((sum, survey) => sum + (survey.response_count || 0), 0);
    const avgResponseRate = totalSurveys > 0 ? Math.round((activeSurveys / totalSurveys) * 1000) / 10 : 0;
    return {
      totalSurveys,
      activeSurveys,
      totalResponses,
      avgResponseRate,
    };
  }, [surveys]);

  return (
    <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
      <Card>
        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
          <CardTitle className="text-sm font-medium">Total Surveys</CardTitle>
          <ClipboardList className="h-4 w-4 text-muted-foreground" />
        </CardHeader>
        <CardContent>
          <div className="text-2xl font-bold">{stats.totalSurveys}</div>
          <p className="text-xs text-muted-foreground">All time surveys</p>
        </CardContent>
      </Card>

      <Card>
        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
          <CardTitle className="text-sm font-medium">Active Surveys</CardTitle>
          <CheckCircle className="h-4 w-4 text-muted-foreground" />
        </CardHeader>
        <CardContent>
          <div className="text-2xl font-bold">{stats.activeSurveys}</div>
          <p className="text-xs text-muted-foreground">Currently active</p>
        </CardContent>
      </Card>

      <Card>
        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
          <CardTitle className="text-sm font-medium">Total Responses</CardTitle>
          <BarChart className="h-4 w-4 text-muted-foreground" />
        </CardHeader>
        <CardContent>
          <div className="text-2xl font-bold">{stats.totalResponses}</div>
          <p className="text-xs text-muted-foreground">All time responses</p>
        </CardContent>
      </Card>

      <Card>
        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
          <CardTitle className="text-sm font-medium">Avg Response Rate</CardTitle>
          <TrendingUp className="h-4 w-4 text-muted-foreground" />
        </CardHeader>
        <CardContent>
          <div className="text-2xl font-bold">{stats.avgResponseRate}%</div>
          <p className="text-xs text-muted-foreground">Response rate</p>
        </CardContent>
      </Card>
    </div>
  );
}
