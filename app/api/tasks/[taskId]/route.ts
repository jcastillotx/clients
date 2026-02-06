import { NextRequest, NextResponse } from "next/server";
import { createClient } from "@/lib/supabase/server";
import { db } from "@/lib/db";
import {
  staffTasks,
  staffTaskAssignees,
  staffTaskLabelRelations,
  staffTaskComments,
} from "@/lib/db/schema/staff-tasks";
import { eq } from "drizzle-orm";

/**
 * GET /api/tasks/[taskId]
 * Get a specific task with all related data
 */
export async function GET(request: NextRequest, { params }: { params: Promise<{ taskId: string }> }) {
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

    const task = await db.query.staffTasks.findFirst({
      where: eq(staffTasks.id, taskId),
      with: {
        board: true,
        column: true,
        creator: {
          columns: {
            id: true,
            name: true,
            email: true,
            avatar: true,
          },
        },
        assignees: {
          with: {
            user: {
              columns: {
                id: true,
                name: true,
                email: true,
                avatar: true,
              },
            },
          },
        },
        labelRelations: {
          with: {
            label: true,
          },
        },
        checklists: {
          orderBy: (checklists, { asc }) => [asc(checklists.position)],
        },
        comments: {
          orderBy: (comments, { desc }) => [desc(comments.createdAt)],
          with: {
            user: {
              columns: {
                id: true,
                name: true,
                email: true,
                avatar: true,
              },
            },
          },
        },
        subtasks: {
          orderBy: (subtasks, { asc }) => [asc(subtasks.position)],
        },
      },
    });

    if (!task) {
      return NextResponse.json({ error: "Task not found" }, { status: 404 });
    }

    return NextResponse.json({ task });
  } catch (error) {
    console.error("Error fetching task:", error);
    return NextResponse.json({ error: "Failed to fetch task" }, { status: 500 });
  }
}

/**
 * PATCH /api/tasks/[taskId]
 * Update a task
 */
export async function PATCH(request: NextRequest, { params }: { params: Promise<{ taskId: string }> }) {
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
    const {
      title,
      description,
      priority,
      dueDate,
      estimatedHours,
      actualHours,
      progress,
      assignees,
      labels,
      completedAt,
    } = body;

    // Update task
    const [task] = await db
      .update(staffTasks)
      .set({
        title,
        description,
        priority,
        dueDate: dueDate ? new Date(dueDate) : null,
        estimatedHours,
        actualHours,
        progress,
        completedAt: completedAt ? new Date(completedAt) : null,
        updatedAt: new Date(),
      })
      .where(eq(staffTasks.id, taskId))
      .returning();

    if (!task) {
      return NextResponse.json({ error: "Task not found" }, { status: 404 });
    }

    // Update assignees if provided
    if (assignees !== undefined) {
      // Remove existing assignees
      await db.delete(staffTaskAssignees).where(eq(staffTaskAssignees.taskId, taskId));

      // Add new assignees
      if (assignees.length > 0) {
        await db.insert(staffTaskAssignees).values(
          assignees.map((userId: string) => ({
            taskId: taskId,
            userId,
          })),
        );
      }

      // Log activity
      await db.insert(staffTaskComments).values({
        taskId: taskId,
        userId: user.id,
        content: `Updated assignees`,
        isSystem: true,
      });
    }

    // Update labels if provided
    if (labels !== undefined) {
      // Remove existing labels
      await db.delete(staffTaskLabelRelations).where(eq(staffTaskLabelRelations.taskId, taskId));

      // Add new labels
      if (labels.length > 0) {
        await db.insert(staffTaskLabelRelations).values(
          labels.map((labelId: string) => ({
            taskId: taskId,
            labelId,
          })),
        );
      }
    }

    // Fetch updated task with relations
    const updatedTask = await db.query.staffTasks.findFirst({
      where: eq(staffTasks.id, taskId),
      with: {
        assignees: {
          with: {
            user: {
              columns: {
                id: true,
                name: true,
                email: true,
                avatar: true,
              },
            },
          },
        },
        labelRelations: {
          with: {
            label: true,
          },
        },
        checklists: true,
      },
    });

    return NextResponse.json({ task: updatedTask });
  } catch (error) {
    console.error("Error updating task:", error);
    return NextResponse.json({ error: "Failed to update task" }, { status: 500 });
  }
}

/**
 * DELETE /api/tasks/[taskId]
 * Delete a task
 */
export async function DELETE(request: NextRequest, { params }: { params: Promise<{ taskId: string }> }) {
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

    await db.delete(staffTasks).where(eq(staffTasks.id, taskId));

    return NextResponse.json({ success: true });
  } catch (error) {
    console.error("Error deleting task:", error);
    return NextResponse.json({ error: "Failed to delete task" }, { status: 500 });
  }
}
