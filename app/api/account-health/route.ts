import { NextRequest, NextResponse } from "next/server";
import { db } from "@/lib/db";
import { accountHealth } from "@/lib/db/schema/additional-features";
import { eq, desc } from "drizzle-orm";
import { createClient } from "@/lib/supabase/server";
import { canAccessClient, resolveRouteAccess } from "@/lib/auth/route-access";
import { z } from "zod";

const querySchema = z.object({
  clientId: z.string().uuid(),
});

const upsertSchema = z.object({
  clientId: z.string().uuid(),
  score: z.number(),
  factors: z.object({
    engagement: z.number().optional(),
    payment_history: z.number().optional(),
    support_satisfaction: z.number().optional(),
    feature_adoption: z.number().optional(),
    communication: z.number().optional(),
  }),
  lastInteraction: z.string().optional(),
  revenueTrend: z.enum(["increasing", "stable", "decreasing"]).optional(),
  satisfactionScore: z.number().optional(),
  riskLevel: z.enum(["low", "medium", "high", "critical"]),
  recommendations: z
    .array(
      z.object({
        type: z.enum(["upsell", "retention", "engagement", "support"]),
        priority: z.enum(["low", "medium", "high"]),
        message: z.string(),
        actionUrl: z.string().optional(),
      }),
    )
    .optional(),
});

/**
 * GET /api/account-health
 * Retrieve account health for a client
 */
export async function GET(request: NextRequest) {
  try {
    const supabase = await createClient();
    const {
      data: { user },
      error: authError,
    } = await supabase.auth.getUser();

    if (authError || !user) {
      return NextResponse.json({ error: "Unauthorized" }, { status: 401 });
    }

    const access = await resolveRouteAccess(supabase, user);

    const searchParams = request.nextUrl.searchParams;
    const parsed = querySchema.safeParse({
      clientId: searchParams.get("clientId"),
    });
    if (!parsed.success) {
      return NextResponse.json(
        { error: "Client ID is required" },
        { status: 400 },
      );
    }

    const { clientId } = parsed.data;
    if (!canAccessClient(access, clientId)) {
      return NextResponse.json({ error: "Forbidden" }, { status: 403 });
    }

    const health = await db
      .select()
      .from(accountHealth)
      .where(eq(accountHealth.clientId, clientId))
      .orderBy(desc(accountHealth.calculatedAt))
      .limit(1);

    if (health.length === 0) {
      return NextResponse.json(
        { error: "Account health not found" },
        { status: 404 },
      );
    }

    return NextResponse.json(health[0]);
  } catch (error) {
    console.error("Error fetching account health:", error);
    return NextResponse.json(
      { error: "Failed to fetch account health" },
      { status: 500 },
    );
  }
}

/**
 * POST /api/account-health
 * Calculate and store new account health
 */
export async function POST(request: NextRequest) {
  try {
    const supabase = await createClient();
    const {
      data: { user },
      error: authError,
    } = await supabase.auth.getUser();

    if (authError || !user) {
      return NextResponse.json({ error: "Unauthorized" }, { status: 401 });
    }

    const access = await resolveRouteAccess(supabase, user);

    const body = await request.json();
    const parsed = upsertSchema.safeParse(body);
    if (!parsed.success) {
      return NextResponse.json(
        { error: "Missing required fields" },
        { status: 400 },
      );
    }

    const {
      clientId,
      score,
      factors,
      lastInteraction,
      revenueTrend,
      satisfactionScore,
      riskLevel,
      recommendations,
    } = parsed.data;

    if (!canAccessClient(access, clientId)) {
      return NextResponse.json({ error: "Forbidden" }, { status: 403 });
    }

    const newHealthRow: typeof accountHealth.$inferInsert = {
      clientId,
      score,
      factors,
      lastInteraction: lastInteraction ? new Date(lastInteraction) : null,
      revenueTrend,
      satisfactionScore,
      riskLevel,
      recommendations,
      calculatedAt: new Date(),
    };

    const newHealth = await db
      .insert(accountHealth)
      .values(newHealthRow)
      .returning();

    return NextResponse.json(newHealth[0], { status: 201 });
  } catch (error) {
    console.error("Error creating account health:", error);
    return NextResponse.json(
      { error: "Failed to create account health" },
      { status: 500 },
    );
  }
}
