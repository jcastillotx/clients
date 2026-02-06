import { Suspense } from "react";
import { Metadata } from "next";
import { UsageCharts } from "@/components/ai/usage-charts";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { Skeleton } from "@/components/ui/skeleton";
import { TrendingUp, TrendingDown, DollarSign, Zap, MessageSquare, Activity } from "lucide-react";

export const metadata: Metadata = {
  title: "AI Analytics",
  description: "Track AI usage, costs, and performance",
};

function AnalyticsLoading() {
  return (
    <div className="space-y-4">
      <Skeleton className="h-64 w-full" />
      <Skeleton className="h-64 w-full" />
    </div>
  );
}

export default function AiAnalyticsPage() {
  return (
    <div className="container mx-auto p-6">
      <div className="mb-6">
        <h1 className="text-3xl font-bold tracking-tight">AI Analytics</h1>
        <p className="text-muted-foreground mt-2">Monitor usage, costs, and performance metrics</p>
      </div>

      <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4 mb-6">
        <Card>
          <CardHeader className="flex flex-row items-center justify-between pb-2">
            <CardTitle className="text-sm font-medium">Total Usage</CardTitle>
            <Zap className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">2.45M</div>
            <p className="text-xs text-muted-foreground flex items-center gap-1">
              <TrendingUp className="h-3 w-3 text-green-500" />
              <span className="text-green-500">+12.3%</span> from last month
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between pb-2">
            <CardTitle className="text-sm font-medium">Total Cost</CardTitle>
            <DollarSign className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">$234.56</div>
            <p className="text-xs text-muted-foreground flex items-center gap-1">
              <TrendingDown className="h-3 w-3 text-green-500" />
              <span className="text-green-500">-5.2%</span> from last month
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between pb-2">
            <CardTitle className="text-sm font-medium">Conversations</CardTitle>
            <MessageSquare className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">1,284</div>
            <p className="text-xs text-muted-foreground flex items-center gap-1">
              <TrendingUp className="h-3 w-3 text-green-500" />
              <span className="text-green-500">+18.7%</span> from last month
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between pb-2">
            <CardTitle className="text-sm font-medium">Avg Response Time</CardTitle>
            <Activity className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">1.2s</div>
            <p className="text-xs text-muted-foreground flex items-center gap-1">
              <TrendingDown className="h-3 w-3 text-green-500" />
              <span className="text-green-500">-0.3s</span> from last month
            </p>
          </CardContent>
        </Card>
      </div>

      <Tabs defaultValue="usage" className="space-y-4">
        <TabsList>
          <TabsTrigger value="usage">Token Usage</TabsTrigger>
          <TabsTrigger value="costs">Costs</TabsTrigger>
          <TabsTrigger value="models">Models</TabsTrigger>
          <TabsTrigger value="performance">Performance</TabsTrigger>
        </TabsList>

        <TabsContent value="usage" className="space-y-4">
          <Card>
            <CardHeader>
              <CardTitle>Token Usage Over Time</CardTitle>
              <CardDescription>Daily token consumption across all AI models</CardDescription>
            </CardHeader>
            <CardContent>
              <Suspense fallback={<AnalyticsLoading />}>
                <UsageCharts type="tokens" />
              </Suspense>
            </CardContent>
          </Card>

          <div className="grid gap-4 md:grid-cols-2">
            <Card>
              <CardHeader>
                <CardTitle>Usage by Model</CardTitle>
                <CardDescription>Token distribution across models</CardDescription>
              </CardHeader>
              <CardContent>
                <div className="space-y-4">
                  <div className="flex items-center justify-between">
                    <div>
                      <div className="font-medium">GPT-4</div>
                      <div className="text-sm text-muted-foreground">1.2M tokens</div>
                    </div>
                    <div className="text-right">
                      <div className="font-medium">49%</div>
                      <div className="text-sm text-muted-foreground">$123.45</div>
                    </div>
                  </div>
                  <div className="flex items-center justify-between">
                    <div>
                      <div className="font-medium">Claude 3 Opus</div>
                      <div className="text-sm text-muted-foreground">850K tokens</div>
                    </div>
                    <div className="text-right">
                      <div className="font-medium">35%</div>
                      <div className="text-sm text-muted-foreground">$89.23</div>
                    </div>
                  </div>
                  <div className="flex items-center justify-between">
                    <div>
                      <div className="font-medium">GPT-3.5 Turbo</div>
                      <div className="text-sm text-muted-foreground">400K tokens</div>
                    </div>
                    <div className="text-right">
                      <div className="font-medium">16%</div>
                      <div className="text-sm text-muted-foreground">$21.88</div>
                    </div>
                  </div>
                </div>
              </CardContent>
            </Card>

            <Card>
              <CardHeader>
                <CardTitle>Usage by Type</CardTitle>
                <CardDescription>Request type breakdown</CardDescription>
              </CardHeader>
              <CardContent>
                <div className="space-y-4">
                  <div className="flex items-center justify-between">
                    <div>
                      <div className="font-medium">Chat</div>
                      <div className="text-sm text-muted-foreground">1.5M tokens</div>
                    </div>
                    <div className="text-right">
                      <div className="font-medium">61%</div>
                    </div>
                  </div>
                  <div className="flex items-center justify-between">
                    <div>
                      <div className="font-medium">Completion</div>
                      <div className="text-sm text-muted-foreground">650K tokens</div>
                    </div>
                    <div className="text-right">
                      <div className="font-medium">27%</div>
                    </div>
                  </div>
                  <div className="flex items-center justify-between">
                    <div>
                      <div className="font-medium">Embeddings</div>
                      <div className="text-sm text-muted-foreground">200K tokens</div>
                    </div>
                    <div className="text-right">
                      <div className="font-medium">8%</div>
                    </div>
                  </div>
                  <div className="flex items-center justify-between">
                    <div>
                      <div className="font-medium">Function Calls</div>
                      <div className="text-sm text-muted-foreground">100K tokens</div>
                    </div>
                    <div className="text-right">
                      <div className="font-medium">4%</div>
                    </div>
                  </div>
                </div>
              </CardContent>
            </Card>
          </div>
        </TabsContent>

        <TabsContent value="costs" className="space-y-4">
          <Card>
            <CardHeader>
              <CardTitle>Cost Trends</CardTitle>
              <CardDescription>Monthly cost analysis and projections</CardDescription>
            </CardHeader>
            <CardContent>
              <Suspense fallback={<AnalyticsLoading />}>
                <UsageCharts type="costs" />
              </Suspense>
            </CardContent>
          </Card>
        </TabsContent>

        <TabsContent value="models" className="space-y-4">
          <Card>
            <CardHeader>
              <CardTitle>Model Performance Comparison</CardTitle>
              <CardDescription>Compare performance metrics across models</CardDescription>
            </CardHeader>
            <CardContent>
              <Suspense fallback={<AnalyticsLoading />}>
                <UsageCharts type="models" />
              </Suspense>
            </CardContent>
          </Card>
        </TabsContent>

        <TabsContent value="performance" className="space-y-4">
          <Card>
            <CardHeader>
              <CardTitle>Response Time Analysis</CardTitle>
              <CardDescription>Average response times and latency metrics</CardDescription>
            </CardHeader>
            <CardContent>
              <Suspense fallback={<AnalyticsLoading />}>
                <UsageCharts type="performance" />
              </Suspense>
            </CardContent>
          </Card>
        </TabsContent>
      </Tabs>
    </div>
  );
}
