import { NextRequest } from "next/server";
import { db } from "@/lib/db";
import { timeEntries } from "@/lib/db/schema/time-tracking";
import { eq, and, isNull } from "drizzle-orm";
import { createClient } from "@/lib/supabase/server";
import {
  apiError,
  apiInternalError,
  apiSuccess,
  apiUnauthorized,
} from "@/lib/api/response";

/**
 * POST /api/time-tracking/start
 */
export async function POST(request: NextRequest) {
  try {
    const supabase = await createClient();
    const { data: { user } } = await supabase.auth.getUser();

    if (!user) {
      return apiUnauthorized(request);
    }
    const userId = user.id;

    const [runningTimer] = await db
      .select()
      .from(timeEntries)
      .where(and(eq(timeEntries.userId, userId), isNull(timeEntries.endedAt)))
      .limit(1);

    if (runningTimer) {
      return apiError(request, {
        status: 400,
        code: "BAD_REQUEST",
        message: "Timer already running",
        details: { entry: runningTimer },
      });
    }

    const body = await request.json();
    const { clientId, requestId, taskId, projectId, description, isBillable, hourlyRate } = body;

    const [entry] = await db
      .insert(timeEntries)
      .values({
        userId,
        clientId,
        requestId,
        taskId,
        projectId,
        description,
        startedAt: new Date(),
        endedAt: null,
        durationMinutes: null,
        isBillable: isBillable ?? true,
        hourlyRate,
        status: "pending",
      })
      .returning();

    return apiSuccess(request, entry, { status: 201, extra: entry as Record<string, unknown> });
  } catch (error) {
    console.error("Error starting timer:", error);
    return apiInternalError(request, "Failed to start timer");
  }
}

/**
 * GET /api/time-tracking/start
 */
export async function GET(request: NextRequest) {
  try {
    const supabase = await createClient();
    const { data: { user } } = await supabase.auth.getUser();

    if (!user) {
      return apiUnauthorized(request);
    }
    const userId = user.id;

    const [runningTimer] = await db.query.timeEntries.findMany({
      where: and(eq(timeEntries.userId, userId), isNull(timeEntries.endedAt)),
      limit: 1,
      with: {
        client: true,
        request: true,
      },
    });

    if (!runningTimer) {
      const payload = { running: false, entry: null };
      return apiSuccess(request, payload, { extra: payload });
    }

    const payload = { running: true, entry: runningTimer };
    return apiSuccess(request, payload, { extra: payload });
  } catch (error) {
    console.error("Error fetching running timer:", error);
    return apiInternalError(request, "Failed to fetch running timer");
  }
}
