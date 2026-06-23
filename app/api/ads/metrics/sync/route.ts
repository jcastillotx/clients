import {
  apiError,
  apiInternalError,
  apiSuccess,
  apiUnauthorized,
} from "@/lib/api/response";
import { db } from "@/lib/db";
import { adMetrics } from "@/lib/db/schema/social-media";
import type { AdMetricsInput } from "@/lib/api/route-types";
import { eq } from "drizzle-orm";
import { isAdsPlatformSyncEnabled } from "@/lib/features/incomplete-features";
import { createClient } from "@/lib/supabase/server";

export async function POST(request: Request) {
  try {
    const supabase = await createClient();
    const { data: { user } } = await supabase.auth.getUser();
    if (!user) {
      return apiUnauthorized(request);
    }

    const body = (await request.json()) as {
      adId?: string;
      date?: string;
      metrics?: AdMetricsInput;
    };

    const { adId, date, metrics } = body;

    if (!adId || !date || !metrics) {
      return apiError(request, {
        status: 400,
        code: "BAD_REQUEST",
        message: "Missing required fields",
      });
    }

    const existingMetrics = await db.select().from(adMetrics).where(eq(adMetrics.adId, adId)).limit(1);

    const metricData = {
      adId,
      metricDate: new Date(date).toISOString().split("T")[0],
      impressions: String(metrics.impressions || 0),
      clicks: String(metrics.clicks || 0),
      spend: String(metrics.spend || 0),
      conversions: String(metrics.conversions || 0),
      ctr: String(metrics.ctr || 0),
      cpc: String(metrics.cpc || 0),
      cpm: String(metrics.cpm || 0),
      roas: String(metrics.roas || 0),
      metadata: {
        videoViews: metrics.videoViews,
        videoViewsP25: metrics.videoViewsP25,
        videoViewsP50: metrics.videoViewsP50,
        videoViewsP75: metrics.videoViewsP75,
        videoViewsP100: metrics.videoViewsP100,
        linkClicks: metrics.linkClicks,
        postEngagement: metrics.postEngagement,
        reach: metrics.reach,
        frequency: metrics.frequency,
      },
    };

    if (existingMetrics.length > 0) {
      const [updatedMetrics] = await db
        .update(adMetrics)
        .set({
          ...metricData,
          updatedAt: new Date(),
        })
        .where(eq(adMetrics.id, existingMetrics[0].id))
        .returning();

      return apiSuccess(request, updatedMetrics);
    }

    const [newMetrics] = await db.insert(adMetrics).values(metricData).returning();
    return apiSuccess(request, newMetrics, { status: 201 });
  } catch (error) {
    console.error("Error syncing ad metrics:", error);
    return apiInternalError(request, "Failed to sync ad metrics");
  }
}

export async function GET(request: Request) {
  try {
    const supabase = await createClient();
    const { data: { user } } = await supabase.auth.getUser();
    if (!user) {
      return apiUnauthorized(request);
    }

    const { searchParams } = new URL(request.url);
    const clientId = searchParams.get("clientId");

    if (!clientId) {
      return apiError(request, {
        status: 400,
        code: "BAD_REQUEST",
        message: "Client ID is required",
      });
    }

    if (!isAdsPlatformSyncEnabled()) {
      return apiError(request, {
        status: 501,
        code: "FEATURE_NOT_ENABLED",
        message: "Ad platform metrics sync is not enabled",
        details: {
          hint: "Set FEATURE_ADS_PLATFORM_SYNC=true after configuring Facebook/Google Ads credentials.",
        },
      });
    }

    return apiSuccess(
      request,
      { clientId, status: "processing" },
      { extra: { message: "Metrics sync initiated", clientId, status: "processing" } },
    );
  } catch (error) {
    console.error("Error initiating metrics sync:", error);
    return apiInternalError(request, "Failed to initiate metrics sync");
  }
}
