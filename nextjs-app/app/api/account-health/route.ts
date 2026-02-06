import { NextRequest, NextResponse } from "next/server";
import { db } from "@/lib/db";
import { accountHealth } from "@/lib/db/schema/additional-features";
import { eq, desc } from "drizzle-orm";

/**
 * GET /api/account-health
 * Retrieve account health for a client
 */
export async function GET(request: NextRequest) {
  try {
    const searchParams = request.nextUrl.searchParams;
    const clientId = searchParams.get("clientId");

    if (!clientId) {
      return NextResponse.json({ error: "Client ID is required" }, { status: 400 });
    }

    const health = await db
      .select()
      .from(accountHealth)
      .where(eq(accountHealth.clientId, clientId))
      .orderBy(desc(accountHealth.calculatedAt))
      .limit(1);

    if (health.length === 0) {
      return NextResponse.json({ error: "Account health not found" }, { status: 404 });
    }

    return NextResponse.json(health[0]);
  } catch (error) {
    console.error("Error fetching account health:", error);
    return NextResponse.json({ error: "Failed to fetch account health" }, { status: 500 });
  }
}

/**
 * POST /api/account-health
 * Calculate and store new account health
 */
export async function POST(request: NextRequest) {
  try {
    const body = await request.json();
    const { clientId, score, factors, lastInteraction, revenueTrend, satisfactionScore, riskLevel, recommendations } =
      body;

    if (!clientId || !score || !factors || !riskLevel) {
      return NextResponse.json({ error: "Missing required fields" }, { status: 400 });
    }

    const newHealth = await db
      .insert(accountHealth)
      .values({
        clientId,
        score,
        factors,
        lastInteraction: lastInteraction ? new Date(lastInteraction) : null,
        revenueTrend,
        satisfactionScore,
        riskLevel,
        recommendations,
        calculatedAt: new Date(),
      })
      .returning();

    return NextResponse.json(newHealth[0], { status: 201 });
  } catch (error) {
    console.error("Error creating account health:", error);
    return NextResponse.json({ error: "Failed to create account health" }, { status: 500 });
  }
}
