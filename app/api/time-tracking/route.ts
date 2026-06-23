import { NextRequest } from "next/server";
import { db } from "@/lib/db";
import { timeEntries, timeEntryLocks, type TimeEntryStatus } from "@/lib/db/schema/time-tracking";
import { eq, and, gte, lte, desc } from "drizzle-orm";
import { createClient } from "@/lib/supabase/server";
import {
  apiError,
  apiInternalError,
  apiNotFound,
  apiSuccess,
  apiUnauthorized,
} from "@/lib/api/response";

export async function GET(request: NextRequest) {
  try {
    const supabase = await createClient();
    const { data: { user } } = await supabase.auth.getUser();

    if (!user) {
      return apiUnauthorized(request);
    }
    const userId = user.id;

    const searchParams = request.nextUrl.searchParams;
    const startDate = searchParams.get("startDate");
    const endDate = searchParams.get("endDate");
    const status = searchParams.get("status");
    const clientId = searchParams.get("clientId");
    const requestId = searchParams.get("requestId");

    const conditions = [eq(timeEntries.userId, userId)];

    if (startDate) {
      conditions.push(gte(timeEntries.startedAt, new Date(startDate)));
    }
    if (endDate) {
      conditions.push(lte(timeEntries.startedAt, new Date(endDate)));
    }
    if (status) {
      conditions.push(eq(timeEntries.status, status as TimeEntryStatus));
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

    return apiSuccess(request, entries, { extra: { entries } });
  } catch (error) {
    console.error("Error fetching time entries:", error);
    return apiInternalError(request, "Failed to fetch time entries");
  }
}

export async function POST(request: NextRequest) {
  try {
    const supabase = await createClient();
    const { data: { user } } = await supabase.auth.getUser();

    if (!user) {
      return apiUnauthorized(request);
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

    if (startedAt) {
      const startDate = new Date(startedAt);
      const isLocked = await checkPeriodLock(userId, startDate);
      if (isLocked) {
        return apiError(request, {
          status: 400,
          code: "BAD_REQUEST",
          message: "Cannot create entry in a locked period",
        });
      }
    }

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

    return apiSuccess(request, entry, { status: 201, extra: { entry } });
  } catch (error) {
    console.error("Error creating time entry:", error);
    return apiInternalError(request, "Failed to create time entry");
  }
}

export async function PATCH(request: NextRequest) {
  try {
    const supabase = await createClient();
    const { data: { user } } = await supabase.auth.getUser();

    if (!user) {
      return apiUnauthorized(request);
    }
    const userId = user.id;

    const body = await request.json();
    const { id, ...updates } = body;

    if (!id) {
      return apiError(request, {
        status: 400,
        code: "BAD_REQUEST",
        message: "Entry ID is required",
      });
    }

    const [existingEntry] = await db
      .select()
      .from(timeEntries)
      .where(and(eq(timeEntries.id, id), eq(timeEntries.userId, userId)))
      .limit(1);

    if (!existingEntry) {
      return apiNotFound(request, "Entry not found");
    }

    if (existingEntry.lockedAt) {
      return apiError(request, {
        status: 400,
        code: "BAD_REQUEST",
        message: "Cannot update a locked entry",
      });
    }

    if (existingEntry.startedAt) {
      const isLocked = await checkPeriodLock(userId, existingEntry.startedAt);
      if (isLocked) {
        return apiError(request, {
          status: 400,
          code: "BAD_REQUEST",
          message: "Cannot update entry in a locked period",
        });
      }
    }

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

    return apiSuccess(request, updatedEntry, { extra: { entry: updatedEntry } });
  } catch (error) {
    console.error("Error updating time entry:", error);
    return apiInternalError(request, "Failed to update time entry");
  }
}

export async function DELETE(request: NextRequest) {
  try {
    const supabase = await createClient();
    const { data: { user } } = await supabase.auth.getUser();

    if (!user) {
      return apiUnauthorized(request);
    }
    const userId = user.id;

    const searchParams = request.nextUrl.searchParams;
    const id = searchParams.get("id");

    if (!id) {
      return apiError(request, {
        status: 400,
        code: "BAD_REQUEST",
        message: "Entry ID is required",
      });
    }

    const [existingEntry] = await db
      .select()
      .from(timeEntries)
      .where(and(eq(timeEntries.id, id), eq(timeEntries.userId, userId)))
      .limit(1);

    if (!existingEntry) {
      return apiNotFound(request, "Entry not found");
    }

    if (existingEntry.lockedAt) {
      return apiError(request, {
        status: 400,
        code: "BAD_REQUEST",
        message: "Cannot delete a locked entry",
      });
    }

    if (existingEntry.startedAt) {
      const isLocked = await checkPeriodLock(userId, existingEntry.startedAt);
      if (isLocked) {
        return apiError(request, {
          status: 400,
          code: "BAD_REQUEST",
          message: "Cannot delete entry in a locked period",
        });
      }
    }

    await db.delete(timeEntries).where(eq(timeEntries.id, id));

    return apiSuccess(request, { deleted: true }, { extra: { success: true } });
  } catch (error) {
    console.error("Error deleting time entry:", error);
    return apiInternalError(request, "Failed to delete time entry");
  }
}

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
