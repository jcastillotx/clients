import { NextRequest } from "next/server";
import { db } from "@/lib/db";
import { timeEntries } from "@/lib/db/schema/time-tracking";
import { eq, and, isNull, type SQL } from "drizzle-orm";
import { createClient } from "@/lib/supabase/server";
import {
  apiError,
  apiInternalError,
  apiNotFound,
  apiSuccess,
  apiUnauthorized,
} from "@/lib/api/response";

/**
 * POST /api/time-tracking/stop
 */
export async function POST(request: NextRequest) {
  try {
    const supabase = await createClient();
    const { data: { user } } = await supabase.auth.getUser();

    if (!user) {
      return apiUnauthorized(request);
    }
    const userId = user.id;

    const body = await request.json();
    const { id } = body;

    let query: SQL | undefined;
    if (id) {
      query = and(eq(timeEntries.id, id), eq(timeEntries.userId, userId));
    } else {
      query = and(eq(timeEntries.userId, userId), isNull(timeEntries.endedAt));
    }

    if (!query) {
      return apiError(request, {
        status: 400,
        code: "BAD_REQUEST",
        message: "Invalid timer query",
      });
    }

    const [runningTimer] = await db.select().from(timeEntries).where(query).limit(1);

    if (!runningTimer) {
      return apiNotFound(request, "No running timer found");
    }

    const endedAt = new Date();
    const startedAt = runningTimer.startedAt || new Date();
    const durationMs = endedAt.getTime() - startedAt.getTime();
    const durationMinutes = Math.round(durationMs / 1000 / 60);

    let totalAmount = null;
    if (runningTimer.hourlyRate) {
      totalAmount = (parseFloat(runningTimer.hourlyRate) * durationMinutes) / 60;
    }

    const [stoppedEntry] = await db
      .update(timeEntries)
      .set({
        endedAt,
        durationMinutes,
        totalAmount: totalAmount?.toFixed(2),
        updatedAt: new Date(),
      })
      .where(eq(timeEntries.id, runningTimer.id))
      .returning();

    return apiSuccess(request, stoppedEntry, { extra: stoppedEntry as Record<string, unknown> });
  } catch (error) {
    console.error("Error stopping timer:", error);
    return apiInternalError(request, "Failed to stop timer");
  }
}
