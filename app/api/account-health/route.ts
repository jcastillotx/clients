import { NextRequest } from "next/server";
import {
  apiError,
  apiForbidden,
  apiInternalError,
  apiNotFound,
  apiSuccess,
  apiUnauthorized,
  apiValidationError,
} from "@/lib/api/response";
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

export async function GET(request: NextRequest) {
  try {
    const supabase = await createClient();
    const {
      data: { user },
      error: authError,
    } = await supabase.auth.getUser();

    if (authError || !user) {
      return apiUnauthorized(request);
    }

    const access = await resolveRouteAccess(supabase, user);
    const parsed = querySchema.safeParse({
      clientId: request.nextUrl.searchParams.get("clientId"),
    });
    if (!parsed.success) {
      return apiError(request, {
        status: 400,
        code: "BAD_REQUEST",
        message: "Client ID is required",
      });
    }

    const { clientId } = parsed.data;
    if (!canAccessClient(access, clientId)) {
      return apiForbidden(request);
    }

    const health = await db
      .select()
      .from(accountHealth)
      .where(eq(accountHealth.clientId, clientId))
      .orderBy(desc(accountHealth.calculatedAt))
      .limit(1);

    if (health.length === 0) {
      return apiNotFound(request, "Account health not found");
    }

    return apiSuccess(request, health[0]);
  } catch (error) {
    console.error("Error fetching account health:", error);
    return apiInternalError(request, "Failed to fetch account health");
  }
}

export async function POST(request: NextRequest) {
  try {
    const supabase = await createClient();
    const {
      data: { user },
      error: authError,
    } = await supabase.auth.getUser();

    if (authError || !user) {
      return apiUnauthorized(request);
    }

    const access = await resolveRouteAccess(supabase, user);
    const body = await request.json();
    const parsed = upsertSchema.safeParse(body);
    if (!parsed.success) {
      return apiValidationError(request, parsed.error);
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
      return apiForbidden(request);
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

    return apiSuccess(request, newHealth[0], { status: 201 });
  } catch (error) {
    console.error("Error creating account health:", error);
    return apiInternalError(request, "Failed to create account health");
  }
}
