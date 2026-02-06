import { NextRequest, NextResponse } from "next/server";
import { createClient } from "@/lib/supabase/server";
import { db } from "@/lib/db";
import { staffTasks, staffTaskComments } from "@/lib/db/schema/staff-tasks";
import { eq, and, gte, lte, sql } from "drizzle-orm";

/**
 * POST /api/tasks/[taskId]/move
 * Move a task to a different column and/or position
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
      return NextResponse.json({ error: "Unauthorized" }, { status: 401 });
    }

    const body = await request.json();
    const { columnId, position } = body;

    if (!columnId || position === undefined) {
      return NextResponse.json({ error: "Column ID and position are required" }, { status: 400 });
    }

    // Get current task
    const task = await db.query.staffTasks.findFirst({
      where: eq(staffTasks.id, taskId),
      with: {
        column: true,
      },
    });

    if (!task) {
      return NextResponse.json({ error: "Task not found" }, { status: 404 });
    }

    // Force cast to ensure TS understands the shape, though Drizzle usually infers this.
    // The error suggests it might think 'column' is an array or untyped object.
    const currentColumn = task.column as unknown as { name: string };
    const currentColumnName = currentColumn?.name || "Unknown Column";

    const oldColumnId = task.columnId;
    const oldPosition = task.position;

    // If moving to a different column
    if (columnId !== oldColumnId) {
      // Adjust positions in old column (shift down tasks below old position)
      await db
        .update(staffTasks)
        .set({
          position: sql`position - 1`,
          updatedAt: new Date(),
        })
        .where(and(eq(staffTasks.columnId, oldColumnId), gte(staffTasks.position, oldPosition)));

      // Adjust positions in new column (shift up tasks at or above new position)
      await db
        .update(staffTasks)
        .set({
          position: sql`position + 1`,
          updatedAt: new Date(),
        })
        .where(and(eq(staffTasks.columnId, columnId), gte(staffTasks.position, position)));

      // Update the task with new column and position
      const [updatedTask] = await db
        .update(staffTasks)
        .set({
          columnId,
          position,
          updatedAt: new Date(),
        })
        .where(eq(staffTasks.id, taskId))
        .returning();

      // Log activity
      await db.insert(staffTaskComments).values({
        taskId: taskId,
        userId: user.id,
        content: `Moved task from ${currentColumnName} to new column`,
        isSystem: true,
      });

      return NextResponse.json({ task: updatedTask });
    } else {
      // Moving within the same column
      if (position > oldPosition) {
        // Moving down: shift up tasks between old and new position
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
        // Moving up: shift down tasks between new and old position
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

      // Update the task's position
      const [updatedTask] = await db
        .update(staffTasks)
        .set({
          position,
          updatedAt: new Date(),
        })
        .where(eq(staffTasks.id, taskId))
        .returning();

      return NextResponse.json({ task: updatedTask });
    }
  } catch (error) {
    console.error("Error moving task:", error);
    return NextResponse.json({ error: "Failed to move task" }, { status: 500 });
  }
}
