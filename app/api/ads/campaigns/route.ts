import { NextResponse } from "next/server";
import { db } from "@/lib/db";
import { adCampaigns, adAccounts, adSets, ads, adMetrics } from "@/lib/db/schema/social-media";
import { eq, and, isNull, desc, sql } from "drizzle-orm";

/**
 * GET /api/ads/campaigns
 * List ad campaigns with aggregated metrics
 */
export async function GET(request: Request) {
  try {
    const { searchParams } = new URL(request.url);
    const clientId = searchParams.get("clientId");
    const adAccountId = searchParams.get("adAccountId");

    if (!clientId && !adAccountId) {
      return NextResponse.json({ error: "Client ID or Ad Account ID is required" }, { status: 400 });
    }

    // Fetch campaigns with account info
    const conditions = [isNull(adCampaigns.deletedAt)];
    if (adAccountId) {
      conditions.push(eq(adCampaigns.adAccountId, adAccountId));
    }
    if (clientId) {
      conditions.push(eq(adAccounts.clientId, clientId));
    }

    const campaigns = await db
      .select({
        campaign: adCampaigns,
        account: adAccounts,
      })
      .from(adCampaigns)
      .leftJoin(adAccounts, eq(adCampaigns.adAccountId, adAccounts.id))
      .where(and(...conditions))
      .orderBy(desc(adCampaigns.createdAt));

    // TODO: Aggregate metrics for each campaign
    // For now, returning campaigns without metrics
    // In production, join with adMetrics and sum by campaign

    return NextResponse.json(campaigns);
  } catch (error) {
    console.error("Error fetching ad campaigns:", error);
    return NextResponse.json({ error: "Failed to fetch ad campaigns" }, { status: 500 });
  }
}

/**
 * POST /api/ads/campaigns
 * Create a new ad campaign
 */
export async function POST(request: Request) {
  try {
    const body = await request.json();
    const {
      adAccountId,
      campaignId,
      name,
      objective,
      status,
      dailyBudget,
      lifetimeBudget,
      startDate,
      endDate,
      metadata,
    } = body;

    if (!adAccountId || !name || !objective) {
      return NextResponse.json({ error: "Missing required fields" }, { status: 400 });
    }

    const [newCampaign] = await db
      .insert(adCampaigns)
      .values({
        adAccountId: adAccountId as string,
        campaignId: (campaignId || `campaign_${Date.now()}`) as string,
        name: name as string,
        objective: objective as string,
        status: (status || "active") as any,
        dailyBudget: dailyBudget ? String(dailyBudget) : null,
        lifetimeBudget: lifetimeBudget ? String(lifetimeBudget) : null,
        startDate: startDate ? new Date(startDate).toISOString().split("T")[0] : null,
        endDate: endDate ? new Date(endDate).toISOString().split("T")[0] : null,
        metadata,
      })
      .returning();

    return NextResponse.json(newCampaign, { status: 201 });
  } catch (error) {
    console.error("Error creating ad campaign:", error);
    return NextResponse.json({ error: "Failed to create ad campaign" }, { status: 500 });
  }
}

/**
 * PATCH /api/ads/campaigns/:id
 * Update an ad campaign
 */
export async function PATCH(request: Request) {
  try {
    const { searchParams } = new URL(request.url);
    const campaignId = searchParams.get("id");
    const body = await request.json();

    if (!campaignId) {
      return NextResponse.json({ error: "Campaign ID is required" }, { status: 400 });
    }

    const { name, objective, status, dailyBudget, lifetimeBudget, startDate, endDate, metadata } = body;

    const [updatedCampaign] = await db
      .update(adCampaigns)
      .set({
        name,
        objective,
        status,
        dailyBudget: dailyBudget ? String(dailyBudget) : undefined,
        lifetimeBudget: lifetimeBudget ? String(lifetimeBudget) : undefined,
        startDate: startDate ? new Date(startDate).toISOString().split("T")[0] : undefined,
        endDate: endDate ? new Date(endDate).toISOString().split("T")[0] : undefined,
        metadata,
        updatedAt: new Date(),
      })
      .where(eq(adCampaigns.id, campaignId))
      .returning();

    return NextResponse.json(updatedCampaign);
  } catch (error) {
    console.error("Error updating ad campaign:", error);
    return NextResponse.json({ error: "Failed to update ad campaign" }, { status: 500 });
  }
}

/**
 * DELETE /api/ads/campaigns/:id
 * Soft delete an ad campaign
 */
export async function DELETE(request: Request) {
  try {
    const { searchParams } = new URL(request.url);
    const campaignId = searchParams.get("id");

    if (!campaignId) {
      return NextResponse.json({ error: "Campaign ID is required" }, { status: 400 });
    }

    await db
      .update(adCampaigns)
      .set({
        deletedAt: new Date(),
        status: "deleted",
      })
      .where(eq(adCampaigns.id, campaignId));

    return NextResponse.json({ success: true });
  } catch (error) {
    console.error("Error deleting ad campaign:", error);
    return NextResponse.json({ error: "Failed to delete ad campaign" }, { status: 500 });
  }
}
