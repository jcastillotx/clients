"use client";

import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { formatCurrency, formatCompactNumber } from "@/lib/utils/format";
import { 
  TrendingUp, 
  Users, 
  MousePointer2, 
  Target,
  BarChart3,
  Layers,
  Layout
} from "lucide-react";
import { UsageCharts } from "@/components/ai/usage-charts"; // Reusing the chart component or similar

interface CampaignDashboardProps {
  campaign: any;
  metrics: {
    impressions: number;
    clicks: number;
    spend: number;
    conversions: number;
    ctr: number;
    cpc: number;
    cpm: number;
  };
  adSets: any[];
}

export function CampaignDashboard({ campaign, metrics, adSets }: CampaignDashboardProps) {
  return (
    <div className="space-y-6">
      <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Total Spend</CardTitle>
            <TrendingUp className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{formatCurrency(metrics.spend)}</div>
            <p className="text-xs text-muted-foreground">
              Total campaign expenditure
            </p>
          </CardContent>
        </Card>
        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Impressions</CardTitle>
            <Users className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{formatCompactNumber(metrics.impressions)}</div>
            <p className="text-xs text-muted-foreground">
              Total views across all ads
            </p>
          </CardContent>
        </Card>
        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Clicks</CardTitle>
            <MousePointer2 className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{formatCompactNumber(metrics.clicks)}</div>
            <p className="text-xs text-muted-foreground">
              {metrics.ctr.toFixed(2)}% Click-through rate
            </p>
          </CardContent>
        </Card>
        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Conversions</CardTitle>
            <Target className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{formatCompactNumber(metrics.conversions)}</div>
            <p className="text-xs text-muted-foreground">
              Total successful actions
            </p>
          </CardContent>
        </Card>
      </div>

      <Tabs defaultValue="overview" className="space-y-4">
        <TabsList>
          <TabsTrigger value="overview">
            <BarChart3 className="mr-2 h-4 w-4" />
            Overview
          </TabsTrigger>
          <TabsTrigger value="adsets">
            <Layers className="mr-2 h-4 w-4" />
            Ad Sets ({adSets.length})
          </TabsTrigger>
        </TabsList>

        <TabsContent value="overview" className="space-y-4">
          <Card>
            <CardHeader>
              <CardTitle>Performance Trends</CardTitle>
              <CardDescription>Metrics trend over the campaign period</CardDescription>
            </CardHeader>
            <CardContent className="h-[300px]">
              <UsageCharts type="performance" />
            </CardContent>
          </Card>
        </TabsContent>

        <TabsContent value="adsets">
          <Card>
            <CardHeader>
              <CardTitle>Ad Sets Performance</CardTitle>
              <CardDescription>Breakdown of metrics by ad set</CardDescription>
            </CardHeader>
            <CardContent>
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Ad Set Name</TableHead>
                    <TableHead>Status</TableHead>
                    <TableHead>Budget</TableHead>
                    <TableHead>Impressions</TableHead>
                    <TableHead>CTR</TableHead>
                    <TableHead className="text-right">Spend</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {adSets.map((adSet) => (
                    <TableRow key={adSet.id}>
                      <TableCell className="font-medium">{adSet.name}</TableCell>
                      <TableCell>
                        <Badge variant="outline">{adSet.status}</Badge>
                      </TableCell>
                      <TableCell>{formatCurrency(adSet.budget || 0)}</TableCell>
                      <TableCell>---</TableCell>
                      <TableCell>---</TableCell>
                      <TableCell className="text-right">{formatCurrency(0)}</TableCell>
                    </TableRow>
                  ))}
                  {adSets.length === 0 && (
                    <TableRow>
                      <TableCell colSpan={6} className="text-center py-4 text-muted-foreground">
                        No ad sets found for this campaign.
                      </TableCell>
                    </TableRow>
                  )}
                </TableBody>
              </Table>
            </CardContent>
          </Card>
        </TabsContent>
      </Tabs>
    </div>
  );
}
