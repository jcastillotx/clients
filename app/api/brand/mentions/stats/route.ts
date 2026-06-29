import { NextRequest } from "next/server";
import { createClient } from "@/lib/supabase/server";
import { resolveStaffAccess } from "@/lib/api/resolve-staff-access";
import { apiForbidden, apiInternalError, apiSuccess, apiUnauthorized } from "@/lib/api/response";

export async function GET(req: NextRequest) {
  try {
    const access = await resolveStaffAccess();
    if (!access) return apiUnauthorized(req);
    if (!access.isStaff) return apiForbidden(req);

    const supabase = await createClient();
    const { searchParams } = req.nextUrl;
    const clientIdParam = searchParams.get("client_id");

    // Determine the effective client filter
    const effectiveClientId =
      clientIdParam ?? (!access.isAdmin ? (access.clientId ?? null) : null);

    // Calculate date ranges for trend comparison
    const now = new Date();
    const currentPeriodStart = new Date(now);
    currentPeriodStart.setDate(currentPeriodStart.getDate() - 30);
    const previousPeriodStart = new Date(currentPeriodStart);
    previousPeriodStart.setDate(previousPeriodStart.getDate() - 30);

    // Fetch all mentions in current period for stats
    let currentQuery = supabase
      .from("brand_mentions")
      .select("id, sentiment, responded_at, created_at")
      .gte("created_at", currentPeriodStart.toISOString());

    if (effectiveClientId) {
      currentQuery = currentQuery.eq("client_id", effectiveClientId);
    }

    // Fetch previous period count for trend
    let previousQuery = supabase
      .from("brand_mentions")
      .select("id", { count: "exact", head: true })
      .gte("created_at", previousPeriodStart.toISOString())
      .lt("created_at", currentPeriodStart.toISOString());

    if (effectiveClientId) {
      previousQuery = previousQuery.eq("client_id", effectiveClientId);
    }

    const [currentResult, previousResult] = await Promise.all([currentQuery, previousQuery]);

    if (currentResult.error) return apiInternalError(req, currentResult.error.message);
    if (previousResult.error) return apiInternalError(req, previousResult.error.message);

    const mentions = currentResult.data ?? [];
    const previousCount = previousResult.count ?? 0;

    // Aggregate sentiment breakdown
    const sentimentBreakdown = { positive: 0, neutral: 0, negative: 0, unclassified: 0 };
    let respondedCount = 0;

    for (const m of mentions) {
      if (m.sentiment === "positive") sentimentBreakdown.positive++;
      else if (m.sentiment === "neutral") sentimentBreakdown.neutral++;
      else if (m.sentiment === "negative") sentimentBreakdown.negative++;
      else sentimentBreakdown.unclassified++;

      if (m.responded_at) respondedCount++;
    }

    const total = mentions.length;
    const classifiedCount = sentimentBreakdown.positive + sentimentBreakdown.neutral + sentimentBreakdown.negative;

    // Compute a simple average sentiment score: positive=1, neutral=0, negative=-1
    const avgSentimentScore =
      classifiedCount > 0
        ? (sentimentBreakdown.positive * 1 + sentimentBreakdown.neutral * 0 + sentimentBreakdown.negative * -1) /
          classifiedCount
        : 0;

    // Trend: percentage change vs previous period
    const mentionsTrend =
      previousCount > 0 ? ((total - previousCount) / previousCount) * 100 : null;

    const stats = {
      total,
      sentimentBreakdown,
      avgSentimentScore: parseFloat(avgSentimentScore.toFixed(4)),
      respondedCount,
      responseRate: total > 0 ? parseFloat(((respondedCount / total) * 100).toFixed(2)) : 0,
      mentionsTrend: mentionsTrend !== null ? parseFloat(mentionsTrend.toFixed(2)) : null,
      previousPeriodTotal: previousCount,
      period: {
        start: currentPeriodStart.toISOString(),
        end: now.toISOString(),
        days: 30,
      },
    };

    return apiSuccess(req, stats, { extra: { stats } });
  } catch (err) {
    console.error("Error fetching brand mention stats:", err);
    return apiInternalError(req, "Failed to fetch brand mention stats");
  }
}
