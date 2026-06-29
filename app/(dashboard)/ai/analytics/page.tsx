import { Metadata } from "next";
import { createClient } from "@/lib/supabase/server";
import { UsageCharts } from "@/components/ai/usage-charts";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { TrendingUp, TrendingDown, DollarSign, Zap, MessageSquare, Activity } from "lucide-react";

export const metadata: Metadata = {
  title: "AI Analytics",
  description: "Track AI usage, costs, and performance",
};

function formatTokens(n: number) {
  if (n >= 1_000_000) return `${(n / 1_000_000).toFixed(2)}M`;
  if (n >= 1_000) return `${(n / 1_000).toFixed(1)}K`;
  return String(n);
}

export default async function AiAnalyticsPage() {
  const supabase = await createClient();
  const {
    data: { user },
  } = await supabase.auth.getUser();

  const { data: userData } = user
    ? await supabase.from("users").select("client_id, is_super_admin").eq("id", user.id).single()
    : { data: null };

  const clientId = userData?.client_id || null;

  const now = new Date();
  const thirtyDaysAgo = new Date(now.getTime() - 30 * 24 * 60 * 60 * 1000).toISOString();
  const sixtyDaysAgo = new Date(now.getTime() - 60 * 24 * 60 * 60 * 1000).toISOString();
  const sevenDaysAgo = new Date(now.getTime() - 7 * 24 * 60 * 60 * 1000).toISOString();

  let usageQuery = supabase
    .from("ai_usage_tracking")
    .select("tokens_used, cost, model, provider, request_type, metadata, created_at")
    .gte("created_at", thirtyDaysAgo);

  let prevUsageQuery = supabase
    .from("ai_usage_tracking")
    .select("tokens_used, cost")
    .gte("created_at", sixtyDaysAgo)
    .lt("created_at", thirtyDaysAgo);

  let convQuery = supabase
    .from("ai_conversations")
    .select("id", { count: "exact", head: true })
    .gte("created_at", thirtyDaysAgo);

  let prevConvQuery = supabase
    .from("ai_conversations")
    .select("id", { count: "exact", head: true })
    .gte("created_at", sixtyDaysAgo)
    .lt("created_at", thirtyDaysAgo);

  let dailyQuery = supabase
    .from("ai_usage_tracking")
    .select("tokens_used, cost, created_at")
    .gte("created_at", sevenDaysAgo)
    .order("created_at");

  if (clientId) {
    usageQuery = usageQuery.eq("client_id", clientId);
    prevUsageQuery = prevUsageQuery.eq("client_id", clientId);
    convQuery = convQuery.eq("client_id", clientId);
    prevConvQuery = prevConvQuery.eq("client_id", clientId);
    dailyQuery = dailyQuery.eq("client_id", clientId);
  }

  const [
    { data: usageRows },
    { data: prevUsageRows },
    { count: convCount },
    { count: prevConvCount },
    { data: dailyRows },
  ] = await Promise.all([usageQuery, prevUsageQuery, convQuery, prevConvQuery, dailyQuery]);

  // Aggregate current period
  const totalTokens = (usageRows || []).reduce((s, r) => s + (r.tokens_used ?? 0), 0);
  const totalCost = (usageRows || []).reduce((s, r) => s + parseFloat(r.cost ?? "0"), 0);
  const avgDuration = (() => {
    const rows = (usageRows || []).filter((r) => {
      const meta = r.metadata as { duration?: number } | null;
      return typeof meta?.duration === "number";
    });
    if (rows.length === 0) return null;
    const sum = rows.reduce((s, r) => s + ((r.metadata as { duration?: number })?.duration ?? 0), 0);
    return (sum / rows.length / 1000).toFixed(1);
  })();

  // Previous period for % change
  const prevTokens = (prevUsageRows || []).reduce((s, r) => s + (r.tokens_used ?? 0), 0);
  const prevCost = (prevUsageRows || []).reduce((s, r) => s + parseFloat(r.cost ?? "0"), 0);

  const tokenPct = prevTokens > 0 ? (((totalTokens - prevTokens) / prevTokens) * 100).toFixed(1) : null;
  const costPct = prevCost > 0 ? (((totalCost - prevCost) / prevCost) * 100).toFixed(1) : null;
  const convPct =
    (prevConvCount ?? 0) > 0
      ? ((((convCount ?? 0) - (prevConvCount ?? 0)) / (prevConvCount ?? 1)) * 100).toFixed(1)
      : null;

  // Aggregate by model
  const modelMap = new Map<string, { tokens: number; cost: number }>();
  for (const row of usageRows || []) {
    const key = row.model || "Unknown";
    const existing = modelMap.get(key) ?? { tokens: 0, cost: 0 };
    modelMap.set(key, {
      tokens: existing.tokens + (row.tokens_used ?? 0),
      cost: existing.cost + parseFloat(row.cost ?? "0"),
    });
  }
  const modelBreakdown = [...modelMap.entries()]
    .map(([model, { tokens, cost }]) => ({
      model,
      tokens,
      cost,
      pct: totalTokens > 0 ? Math.round((tokens / totalTokens) * 100) : 0,
    }))
    .sort((a, b) => b.tokens - a.tokens)
    .slice(0, 5);

  // Aggregate by request type
  const typeMap = new Map<string, number>();
  for (const row of usageRows || []) {
    const key = row.request_type || "chat";
    typeMap.set(key, (typeMap.get(key) ?? 0) + (row.tokens_used ?? 0));
  }
  const typeBreakdown = [...typeMap.entries()]
    .map(([type, tokens]) => ({
      type,
      tokens,
      pct: totalTokens > 0 ? Math.round((tokens / totalTokens) * 100) : 0,
    }))
    .sort((a, b) => b.tokens - a.tokens);

  // Build daily chart data (last 7 days)
  const dayBuckets = new Map<string, { tokens: number; cost: number }>();
  for (let i = 6; i >= 0; i--) {
    const d = new Date(now.getTime() - i * 24 * 60 * 60 * 1000);
    const label = d.toLocaleDateString("en-US", { month: "short", day: "numeric" });
    dayBuckets.set(label, { tokens: 0, cost: 0 });
  }
  for (const row of dailyRows || []) {
    const d = new Date(row.created_at);
    const label = d.toLocaleDateString("en-US", { month: "short", day: "numeric" });
    const existing = dayBuckets.get(label);
    if (existing) {
      existing.tokens += row.tokens_used ?? 0;
      existing.cost += parseFloat(row.cost ?? "0");
    }
  }
  const tokenChartData = [...dayBuckets.entries()].map(([date, { tokens, cost }]) => ({
    date,
    tokens,
    cost: parseFloat(cost.toFixed(2)),
  }));

  return (
    <div className="container mx-auto p-6">
      <div className="mb-6">
        <h1 className="text-3xl font-bold tracking-tight">AI Analytics</h1>
        <p className="text-muted-foreground mt-2">Monitor usage, costs, and performance metrics</p>
      </div>

      <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4 mb-6">
        <Card>
          <CardHeader className="flex flex-row items-center justify-between pb-2">
            <CardTitle className="text-sm font-medium">Total Tokens</CardTitle>
            <Zap className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{formatTokens(totalTokens)}</div>
            {tokenPct != null ? (
              <p className="text-xs text-muted-foreground flex items-center gap-1">
                {parseFloat(tokenPct) >= 0 ? (
                  <TrendingUp className="h-3 w-3 text-green-500" />
                ) : (
                  <TrendingDown className="h-3 w-3 text-red-500" />
                )}
                <span className={parseFloat(tokenPct) >= 0 ? "text-green-500" : "text-red-500"}>
                  {parseFloat(tokenPct) >= 0 ? "+" : ""}{tokenPct}%
                </span>{" "}
                from last month
              </p>
            ) : (
              <p className="text-xs text-muted-foreground">Last 30 days</p>
            )}
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between pb-2">
            <CardTitle className="text-sm font-medium">Total Cost</CardTitle>
            <DollarSign className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">${totalCost.toFixed(2)}</div>
            {costPct != null ? (
              <p className="text-xs text-muted-foreground flex items-center gap-1">
                {parseFloat(costPct) <= 0 ? (
                  <TrendingDown className="h-3 w-3 text-green-500" />
                ) : (
                  <TrendingUp className="h-3 w-3 text-red-500" />
                )}
                <span className={parseFloat(costPct) <= 0 ? "text-green-500" : "text-red-500"}>
                  {parseFloat(costPct) >= 0 ? "+" : ""}{costPct}%
                </span>{" "}
                from last month
              </p>
            ) : (
              <p className="text-xs text-muted-foreground">Last 30 days</p>
            )}
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between pb-2">
            <CardTitle className="text-sm font-medium">Conversations</CardTitle>
            <MessageSquare className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{(convCount ?? 0).toLocaleString()}</div>
            {convPct != null ? (
              <p className="text-xs text-muted-foreground flex items-center gap-1">
                {parseFloat(convPct) >= 0 ? (
                  <TrendingUp className="h-3 w-3 text-green-500" />
                ) : (
                  <TrendingDown className="h-3 w-3 text-red-500" />
                )}
                <span className={parseFloat(convPct) >= 0 ? "text-green-500" : "text-red-500"}>
                  {parseFloat(convPct) >= 0 ? "+" : ""}{convPct}%
                </span>{" "}
                from last month
              </p>
            ) : (
              <p className="text-xs text-muted-foreground">Last 30 days</p>
            )}
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between pb-2">
            <CardTitle className="text-sm font-medium">Avg Response Time</CardTitle>
            <Activity className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{avgDuration != null ? `${avgDuration}s` : "—"}</div>
            <p className="text-xs text-muted-foreground">Last 30 days</p>
          </CardContent>
        </Card>
      </div>

      <Tabs defaultValue="usage" className="space-y-4">
        <TabsList>
          <TabsTrigger value="usage">Token Usage</TabsTrigger>
          <TabsTrigger value="breakdown">Breakdown</TabsTrigger>
        </TabsList>

        <TabsContent value="usage" className="space-y-4">
          <Card>
            <CardHeader>
              <CardTitle>Token Usage — Last 7 Days</CardTitle>
              <CardDescription>Daily token consumption</CardDescription>
            </CardHeader>
            <CardContent>
              <UsageCharts type="tokens" data={tokenChartData} />
            </CardContent>
          </Card>
        </TabsContent>

        <TabsContent value="breakdown" className="space-y-4">
          <div className="grid gap-4 md:grid-cols-2">
            <Card>
              <CardHeader>
                <CardTitle>Usage by Model</CardTitle>
                <CardDescription>Token distribution across models</CardDescription>
              </CardHeader>
              <CardContent>
                {modelBreakdown.length === 0 ? (
                  <p className="text-sm text-muted-foreground text-center py-6">No usage data yet</p>
                ) : (
                  <div className="space-y-4">
                    {modelBreakdown.map((m) => (
                      <div key={m.model} className="flex items-center justify-between">
                        <div>
                          <div className="font-medium">{m.model}</div>
                          <div className="text-sm text-muted-foreground">{formatTokens(m.tokens)} tokens</div>
                        </div>
                        <div className="text-right">
                          <div className="font-medium">{m.pct}%</div>
                          <div className="text-sm text-muted-foreground">${m.cost.toFixed(2)}</div>
                        </div>
                      </div>
                    ))}
                  </div>
                )}
              </CardContent>
            </Card>

            <Card>
              <CardHeader>
                <CardTitle>Usage by Type</CardTitle>
                <CardDescription>Request type breakdown</CardDescription>
              </CardHeader>
              <CardContent>
                {typeBreakdown.length === 0 ? (
                  <p className="text-sm text-muted-foreground text-center py-6">No usage data yet</p>
                ) : (
                  <div className="space-y-4">
                    {typeBreakdown.map((t) => (
                      <div key={t.type} className="flex items-center justify-between">
                        <div>
                          <div className="font-medium capitalize">{t.type}</div>
                          <div className="text-sm text-muted-foreground">{formatTokens(t.tokens)} tokens</div>
                        </div>
                        <div className="text-right">
                          <div className="font-medium">{t.pct}%</div>
                        </div>
                      </div>
                    ))}
                  </div>
                )}
              </CardContent>
            </Card>
          </div>
        </TabsContent>
      </Tabs>
    </div>
  );
}
