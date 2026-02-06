import { NextRequest, NextResponse } from "next/server";
import { db } from "@/lib/db";
import { timeEntries, timeEntryLocks } from "@/lib/db/schema/time-tracking";
import { eq, and, gte, lte, desc, sql } from "drizzle-orm";
import { createClient } from "@/lib/supabase/server";

/**
 * GET /api/time-tracking
 * Fetch time entries with optional filters
 */
export async function GET(request: NextRequest) {
  try {
    const supabase = createClient();
    const { data: { user } } = await supabase.auth.getUser();

    if (!user) {
      return NextResponse.json({ error: "Unauthorized" }, { status: 401 });
    }
    const userId = user.id;

    const searchParams = request.nextUrl.searchParams;
    const startDate = searchParams.get("startDate");
    const endDate = searchParams.get("endDate");
    const status = searchParams.get("status");
    const clientId = searchParams.get("clientId");
    const requestId = searchParams.get("requestId");

    // Build query conditions
    const conditions = [eq(timeEntries.userId, userId)];

    if (startDate) {
      conditions.push(gte(timeEntries.startedAt, new Date(startDate)));
    }
    if (endDate) {
      conditions.push(lte(timeEntries.startedAt, new Date(endDate)));
    }
    if (status) {
      conditions.push(eq(timeEntries.status, status as any));
    }
    if (clientId) {
      conditions.push(eq(timeEntries.clientId, clientId));
    }
    if (requestId) {
      conditions.push(eq(timeEntries.requestId, requestId));
    }

    const entries = await db.query.timeEntries.findMany({
      where: and(...conditions),
      orderBy: [desc(timeEntries.startedAt)],
      with: {
        client: true,
        request: true,
        user: true,
      },
    });

    return NextResponse.json(entries);
  } catch (error) {
    console.error("Error fetching time entries:", error);
    return NextResponse.json({ error: "Failed to fetch time entries" }, { status: 500 });
  }
}

/**
 * POST /api/time-tracking
 * Create a new time entry (manual entry)
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
    const {
      clientId,
      requestId,
      taskId,
      projectId,
      description,
      startedAt,
      endedAt,
      durationMinutes,
      isBillable,
      hourlyRate,
    } = body;

    // Validate that the period is not locked
    if (startedAt) {
      const startDate = new Date(startedAt);
      const isLocked = await checkPeriodLock(userId, startDate);
      if (isLocked) {
        return NextResponse.json({ error: "Cannot create entry in a locked period" }, { status: 400 });
      }
    }

    // Calculate total amount if hourly rate and duration are provided
    let totalAmount = null;
    if (hourlyRate && durationMinutes) {
      totalAmount = (parseFloat(hourlyRate) * durationMinutes) / 60;
    }

    const [entry] = await db
      .insert(timeEntries)
      .values({
        userId,
        clientId,
        requestId,
        taskId,
        projectId,
        description,
        startedAt: startedAt ? new Date(startedAt) : null,
        endedAt: endedAt ? new Date(endedAt) : null,
        durationMinutes,
        isBillable: isBillable ?? true,
        hourlyRate,
        totalAmount: totalAmount?.toFixed(2),
        status: "pending",
      })
      .returning();

    return NextResponse.json(entry, { status: 201 });
  } catch (error) {
    console.error("Error creating time entry:", error);
    return NextResponse.json({ error: "Failed to create time entry" }, { status: 500 });
  }
}

/**
 * PATCH /api/time-tracking
 * Update an existing time entry
 */
export async function PATCH(request: NextRequest) {
  try {
    const supabase = createClient();
    const { data: { user } } = await supabase.auth.getUser();

    if (!user) {
      return NextResponse.json({ error: "Unauthorized" }, { status: 401 });
    }
    const userId = user.id;

    const body = await request.json();
    const { id, ...updates } = body;

    if (!id) {
      return NextResponse.json({ error: "Entry ID is required" }, { status: 400 });
    }

    // Fetch the existing entry
    const [existingEntry] = await db
      .select()
      .from(timeEntries)
      .where(and(eq(timeEntries.id, id), eq(timeEntries.userId, userId)))
      .limit(1);

    if (!existingEntry) {
      return NextResponse.json({ error: "Entry not found" }, { status: 404 });
    }

    // Check if entry is locked
    if (existingEntry.lockedAt) {
      return NextResponse.json({ error: "Cannot update a locked entry" }, { status: 400 });
    }

    // Check if period is locked
    if (existingEntry.startedAt) {
      const isLocked = await checkPeriodLock(userId, existingEntry.startedAt);
      if (isLocked) {
        return NextResponse.json({ error: "Cannot update entry in a locked period" }, { status: 400 });
      }
    }

    // Recalculate total amount if necessary
    if (updates.hourlyRate || updates.durationMinutes) {
      const rate = updates.hourlyRate ?? existingEntry.hourlyRate;
      const duration = updates.durationMinutes ?? existingEntry.durationMinutes;
      if (rate && duration) {
        updates.totalAmount = ((parseFloat(rate) * duration) / 60).toFixed(2);
      }
    }

    const [updatedEntry] = await db
      .update(timeEntries)
      .set({ ...updates, updatedAt: new Date() })
      .where(eq(timeEntries.id, id))
      .returning();

    return NextResponse.json(updatedEntry);
  } catch (error) {
    console.error("Error updating time entry:", error);
    return NextResponse.json({ error: "Failed to update time entry" }, { status: 500 });
  }
}

/**
 * DELETE /api/time-tracking
 * Delete a time entry
 */
export async function DELETE(request: NextRequest) {
  try {
    const supabase = createClient();
    const { data: { user } } = await supabase.auth.getUser();

    if (!user) {
      return NextResponse.json({ error: "Unauthorized" }, { status: 401 });
    }
    const userId = user.id;

    const searchParams = request.nextUrl.searchParams;
    const id = searchParams.get("id");

    if (!id) {
      return NextResponse.json({ error: "Entry ID is required" }, { status: 400 });
    }

    // Fetch the existing entry
    const [existingEntry] = await db
      .select()
      .from(timeEntries)
      .where(and(eq(timeEntries.id, id), eq(timeEntries.userId, userId)))
      .limit(1);

    if (!existingEntry) {
      return NextResponse.json({ error: "Entry not found" }, { status: 404 });
    }

    // Check if entry is locked
    if (existingEntry.lockedAt) {
      return NextResponse.json({ error: "Cannot delete a locked entry" }, { status: 400 });
    }

    // Check if period is locked
    if (existingEntry.startedAt) {
      const isLocked = await checkPeriodLock(userId, existingEntry.startedAt);
      if (isLocked) {
        return NextResponse.json({ error: "Cannot delete entry in a locked period" }, { status: 400 });
      }
    }

    await db.delete(timeEntries).where(eq(timeEntries.id, id));

    return NextResponse.json({ success: true });
  } catch (error) {
    console.error("Error deleting time entry:", error);
    return NextResponse.json({ error: "Failed to delete time entry" }, { status: 500 });
  }
}

/**
 * Helper function to check if a period is locked
 */
async function checkPeriodLock(userId: string, date: Date): Promise<boolean> {
  const locks = await db
    .select()
    .from(timeEntryLocks)
    .where(
      and(
        eq(timeEntryLocks.userId, userId),
        lte(timeEntryLocks.periodStart, date.toISOString().split("T")[0]),
        gte(timeEntryLocks.periodEnd, date.toISOString().split("T")[0]),
      ),
    )
    .limit(1);

  return locks.length > 0;
}
