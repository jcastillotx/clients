import { NextRequest, NextResponse } from "next/server";
import { db } from "@/lib/db";
import { timeEntries } from "@/lib/db/schema/time-tracking";
import { eq, and, isNull } from "drizzle-orm";
import { createClient } from "@/lib/supabase/server";

/**
 * POST /api/time-tracking/stop
 * Stop the running timer
 */
export async function POST(request: NextRequest) {
  try {
    const supabase = createClient();
    const { data: { user } } = await supabase.auth.getUser();

    if (!user) {
      return NextResponse.json({ error: "Unauthorized" }, { status: 401 });
    }
    const userId = user.id;

    const body = await request.json();
    const { id } = body;

    // If ID is provided, stop that specific entry
    // Otherwise, stop the currently running timer
    let query = eq(timeEntries.userId, userId);
    if (id) {
      query = and(eq(timeEntries.id, id), eq(timeEntries.userId, userId)) as any;
    } else {
      query = and(eq(timeEntries.userId, userId), isNull(timeEntries.endedAt)) as any;
    }

    const [runningTimer] = await db.select().from(timeEntries).where(query).limit(1);

    if (!runningTimer) {
      return NextResponse.json({ error: "No running timer found" }, { status: 404 });
    }

    // Calculate duration
    const endedAt = new Date();
    const startedAt = runningTimer.startedAt || new Date();
    const durationMs = endedAt.getTime() - startedAt.getTime();
    const durationMinutes = Math.round(durationMs / 1000 / 60);

    // Calculate total amount
    let totalAmount = null;
    if (runningTimer.hourlyRate) {
      totalAmount = (parseFloat(runningTimer.hourlyRate) * durationMinutes) / 60;
    }

    // Update the entry
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

    return NextResponse.json(stoppedEntry);
  } catch (error) {
    console.error("Error stopping timer:", error);
    return NextResponse.json({ error: "Failed to stop timer" }, { status: 500 });
  }
}
