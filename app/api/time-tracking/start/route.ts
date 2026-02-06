import { NextRequest, NextResponse } from "next/server";
import { db } from "@/lib/db";
import { timeEntries } from "@/lib/db/schema/time-tracking";
import { eq, and, isNull } from "drizzle-orm";
import { createClient } from "@/lib/supabase/server";

/**
 * POST /api/time-tracking/start
 * Start a new timer
 */
export async function POST(request: NextRequest) {
  try {
    const supabase = createClient();
    const { data: { user } } = await supabase.auth.getUser();

    if (!user) {
      return NextResponse.json({ error: "Unauthorized" }, { status: 401 });
    }
    const userId = user.id;

    // Check if there's already a running timer
    const [runningTimer] = await db
      .select()
      .from(timeEntries)
      .where(and(eq(timeEntries.userId, userId), isNull(timeEntries.endedAt)))
      .limit(1);

    if (runningTimer) {
      return NextResponse.json({ error: "Timer already running", entry: runningTimer }, { status: 400 });
    }

    const body = await request.json();
    const { clientId, requestId, taskId, projectId, description, isBillable, hourlyRate } = body;

    // Create a new time entry with started_at but no ended_at
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

    return NextResponse.json(entry, { status: 201 });
  } catch (error) {
    console.error("Error starting timer:", error);
    return NextResponse.json({ error: "Failed to start timer" }, { status: 500 });
  }
}

/**
 * GET /api/time-tracking/start
 * Get currently running timer
 */
export async function GET() {
  try {
    const supabase = createClient();
    const { data: { user } } = await supabase.auth.getUser();

    if (!user) {
      return NextResponse.json({ error: "Unauthorized" }, { status: 401 });
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
      return NextResponse.json({ running: false, entry: null });
    }

    return NextResponse.json({ running: true, entry: runningTimer });
  } catch (error) {
    console.error("Error fetching running timer:", error);
    return NextResponse.json({ error: "Failed to fetch running timer" }, { status: 500 });
  }
}
