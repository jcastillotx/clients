"use client";

import { useEffect, useState } from "react";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Zap, Activity, CheckCircle, XCircle } from "lucide-react";

interface AutomationRule {
  id: string;
  is_active: boolean;
  run_count: number;
}

interface Stats {
  totalRules: number;
  activeRules: number;
  totalRuns: number;
}

export function AutomationStats() {
  const [stats, setStats] = useState<Stats>({ totalRules: 0, activeRules: 0, totalRuns: 0 });
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const fetchStats = async () => {
      try {
        const res = await fetch("/api/automation/rules");
        if (!res.ok) return;
        const json = await res.json();
        const rules: AutomationRule[] = json.data ?? json.rules ?? [];
        const totalRules = rules.length;
        const activeRules = rules.filter((r) => r.is_active).length;
        const totalRuns = rules.reduce((sum, r) => sum + (r.run_count ?? 0), 0);
        setStats({ totalRules, activeRules, totalRuns });
      } finally {
        setLoading(false);
      }
    };
    fetchStats();
  }, []);

  const activePercent = stats.totalRules > 0 ? ((stats.activeRules / stats.totalRules) * 100).toFixed(0) : "0";

  return (
    <div className="grid gap-4 md:grid-cols-3">
      <Card>
        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
          <CardTitle className="text-sm font-medium">Total Rules</CardTitle>
          <Zap className="h-4 w-4 text-muted-foreground" />
        </CardHeader>
        <CardContent>
          <div className="text-2xl font-bold">{loading ? "—" : stats.totalRules}</div>
          <p className="text-xs text-muted-foreground">{stats.activeRules} active</p>
        </CardContent>
      </Card>

      <Card>
        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
          <CardTitle className="text-sm font-medium">Active Rules</CardTitle>
          <Activity className="h-4 w-4 text-muted-foreground" />
        </CardHeader>
        <CardContent>
          <div className="text-2xl font-bold">{loading ? "—" : stats.activeRules}</div>
          <p className="text-xs text-muted-foreground">{activePercent}% of total</p>
        </CardContent>
      </Card>

      <Card>
        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
          <CardTitle className="text-sm font-medium">Total Runs</CardTitle>
          <CheckCircle className="h-4 w-4 text-muted-foreground" />
        </CardHeader>
        <CardContent>
          <div className="text-2xl font-bold">{loading ? "—" : stats.totalRuns}</div>
          <p className="text-xs text-muted-foreground">All time across all rules</p>
        </CardContent>
      </Card>
    </div>
  );
}
