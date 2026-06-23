"use client";

import { useState, useEffect } from "react";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Progress } from "@/components/ui/progress";
import { Badge } from "@/components/ui/badge";
import { AlertCircle, TrendingUp, TrendingDown, Minus } from "lucide-react";
import { fetchApi } from "@/lib/api/client";

interface AccountHealthData {
  id: string;
  score: number;
  factors: {
    engagement?: number;
    payment_history?: number;
    support_satisfaction?: number;
    feature_adoption?: number;
    communication?: number;
  };
  lastInteraction: string | null;
  revenueTrend: "increasing" | "stable" | "decreasing" | null;
  satisfactionScore: number | null;
  riskLevel: "low" | "medium" | "high" | "critical";
  recommendations: Array<{
    type: "upsell" | "retention" | "engagement" | "support";
    priority: "low" | "medium" | "high";
    message: string;
    actionUrl?: string;
  }> | null;
  calculatedAt: string;
}

interface AccountHealthDashboardProps {
  clientId: string;
}

export function AccountHealthDashboard({ clientId }: AccountHealthDashboardProps) {
  const [health, setHealth] = useState<AccountHealthData | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    fetchAccountHealth();
  }, [clientId]);

  const fetchAccountHealth = async () => {
    try {
      setLoading(true);
      const data = await fetchApi<AccountHealthData>(
        `/api/account-health?clientId=${clientId}`,
        undefined,
        { fallbackMessage: "Failed to fetch account health" },
      );
      setHealth(data);
    } catch (err) {
      setError(err instanceof Error ? err.message : "An error occurred");
    } finally {
      setLoading(false);
    }
  };

  const getRiskColor = (risk: string) => {
    switch (risk) {
      case "low":
        return "bg-green-500";
      case "medium":
        return "bg-yellow-500";
      case "high":
        return "bg-orange-500";
      case "critical":
        return "bg-red-500";
      default:
        return "bg-gray-500";
    }
  };

  const getScoreColor = (score: number) => {
    if (score >= 80) return "text-green-600";
    if (score >= 60) return "text-yellow-600";
    if (score >= 40) return "text-orange-600";
    return "text-red-600";
  };

  const getTrendIcon = (trend: string | null) => {
    switch (trend) {
      case "increasing":
        return <TrendingUp className="h-4 w-4 text-green-600" />;
      case "decreasing":
        return <TrendingDown className="h-4 w-4 text-red-600" />;
      case "stable":
        return <Minus className="h-4 w-4 text-gray-600" />;
      default:
        return null;
    }
  };

  if (loading) {
    return (
      <div className="flex items-center justify-center p-8">
        <div className="text-center">Loading account health...</div>
      </div>
    );
  }

  if (error || !health) {
    return (
      <div className="flex items-center justify-center p-8">
        <div className="text-center text-red-600">
          <AlertCircle className="h-8 w-8 mx-auto mb-2" />
          <p>{error || "No health data available"}</p>
        </div>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      {/* Overall Health Score */}
      <Card>
        <CardHeader>
          <div className="flex items-center justify-between">
            <div>
              <CardTitle>Account Health Score</CardTitle>
              <CardDescription>Last calculated: {new Date(health.calculatedAt).toLocaleString()}</CardDescription>
            </div>
            <Badge className={getRiskColor(health.riskLevel)}>{health.riskLevel.toUpperCase()} RISK</Badge>
          </div>
        </CardHeader>
        <CardContent>
          <div className="text-center mb-4">
            <div className={`text-6xl font-bold ${getScoreColor(health.score)}`}>{health.score.toFixed(0)}</div>
            <div className="text-sm text-gray-500">out of 100</div>
          </div>
          <Progress value={health.score} className="h-3" />
        </CardContent>
      </Card>

      {/* Health Factors */}
      <Card>
        <CardHeader>
          <CardTitle>Health Factors</CardTitle>
          <CardDescription>Breakdown of account health metrics</CardDescription>
        </CardHeader>
        <CardContent>
          <div className="space-y-4">
            {Object.entries(health.factors).map(([key, value]) => (
              <div key={key}>
                <div className="flex items-center justify-between mb-1">
                  <span className="text-sm font-medium capitalize">{key.replace(/_/g, " ")}</span>
                  <span className={`text-sm font-bold ${getScoreColor(value || 0)}`}>{value?.toFixed(0)}%</span>
                </div>
                <Progress value={value || 0} className="h-2" />
              </div>
            ))}
          </div>
        </CardContent>
      </Card>

      {/* Metrics */}
      <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
        <Card>
          <CardHeader>
            <CardTitle className="text-sm">Revenue Trend</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="flex items-center gap-2">
              {getTrendIcon(health.revenueTrend)}
              <span className="text-lg font-semibold capitalize">{health.revenueTrend || "N/A"}</span>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle className="text-sm">Satisfaction Score</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="text-lg font-semibold">{health.satisfactionScore?.toFixed(1) || "N/A"} / 10</div>
          </CardContent>
        </Card>
      </div>

      {/* Recommendations */}
      {health.recommendations && health.recommendations.length > 0 && (
        <Card>
          <CardHeader>
            <CardTitle>Recommendations</CardTitle>
            <CardDescription>Actions to improve account health</CardDescription>
          </CardHeader>
          <CardContent>
            <div className="space-y-3">
              {health.recommendations.map((rec, index) => (
                <div key={index} className="p-3 border rounded-lg hover:bg-gray-50 transition-colors">
                  <div className="flex items-start justify-between mb-2">
                    <Badge
                      variant={
                        rec.priority === "high" ? "destructive" : rec.priority === "medium" ? "default" : "secondary"
                      }
                    >
                      {rec.priority} priority
                    </Badge>
                    <Badge variant="outline">{rec.type}</Badge>
                  </div>
                  <p className="text-sm">{rec.message}</p>
                  {rec.actionUrl && (
                    <a href={rec.actionUrl} className="text-sm text-blue-600 hover:underline mt-2 inline-block">
                      Take Action →
                    </a>
                  )}
                </div>
              ))}
            </div>
          </CardContent>
        </Card>
      )}
    </div>
  );
}
