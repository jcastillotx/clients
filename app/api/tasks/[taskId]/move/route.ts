import { NextRequest } from "next/server";
import { createClient } from "@/lib/supabase/server";
import {
  apiError,
  apiInternalError,
  apiNotFound,
  apiSuccess,
  apiUnauthorized,
} from "@/lib/api/response";
import { db } from "@/lib/db";
import { staffTasks, staffTaskComments } from "@/lib/db/schema/staff-tasks";
import { eq, and, gte, lte, sql } from "drizzle-orm";

/**
 * POST /api/tasks/[taskId]/move
 */
export async function POST(request: NextRequest, { params }: { params: Promise<{ taskId: string }> }) {
  const { taskId } = await params;
  try {
    const supabase = await createClient();
    const {
      data: { user },
      error: authError,
    } = await supabase.auth.getUser();

    if (authError || !user) {
      return apiUnauthorized(request);
    }

    const body = await request.json();
    const { columnId, position } = body;

    if (!columnId || position === undefined) {
      return apiError(request, {
        status: 400,
        code: "BAD_REQUEST",
        message: "Column ID and position are required",
      });
    }

    const task = await db.query.staffTasks.findFirst({
      where: eq(staffTasks.id, taskId),
      with: {
        column: true,
      },
    });

    if (!task) {
      return apiNotFound(request, "Task not found");
    }

    const currentColumn = task.column as unknown as { name: string };
    const currentColumnName = currentColumn?.name || "Unknown Column";

    const oldColumnId = task.columnId;
    const oldPosition = task.position;

    if (columnId !== oldColumnId) {
      await db
        .update(staffTasks)
        .set({
          position: sql`position - 1`,
          updatedAt: new Date(),
        })
        .where(and(eq(staffTasks.columnId, oldColumnId), gte(staffTasks.position, oldPosition)));

      await db
        .update(staffTasks)
        .set({
          position: sql`position + 1`,
          updatedAt: new Date(),
        })
        .where(and(eq(staffTasks.columnId, columnId), gte(staffTasks.position, position)));

      const [updatedTask] = await db
        .update(staffTasks)
        .set({
          columnId,
          position,
          updatedAt: new Date(),
        })
        .where(eq(staffTasks.id, taskId))
        .returning();

      await db.insert(staffTaskComments).values({
        taskId: taskId,
        userId: user.id,
        content: `Moved task from ${currentColumnName} to new column`,
        isSystem: true,
      });

      return apiSuccess(request, updatedTask, { extra: { task: updatedTask } });
    }

    if (position > oldPosition) {
      await db
        .update(staffTasks)
        .set({
          position: sql`position - 1`,
          updatedAt: new Date(),
        })
        .where(
          and(
            eq(staffTasks.columnId, columnId),
            gte(staffTasks.position, oldPosition),
            lte(staffTasks.position, position),
          ),
        );
    } else if (position < oldPosition) {
      await db
        .update(staffTasks)
        .set({
          position: sql`position + 1`,
          updatedAt: new Date(),
        })
        .where(
          and(
            eq(staffTasks.columnId, columnId),
            gte(staffTasks.position, position),
            lte(staffTasks.position, oldPosition),
          ),
        );
    }

    const [updatedTask] = await db
      .update(staffTasks)
      .set({
        position,
        updatedAt: new Date(),
      })
      .where(eq(staffTasks.id, taskId))
      .returning();

    return apiSuccess(request, updatedTask, { extra: { task: updatedTask } });
  } catch (error) {
    console.error("Error moving task:", error);
    return apiInternalError(request, "Failed to move task");
  }
}
