import { NextRequest } from "next/server";
import { createClient } from "@/lib/supabase/server";
import {
  apiInternalError,
  apiNotFound,
  apiSuccess,
  apiUnauthorized,
} from "@/lib/api/response";
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
      return apiUnauthorized(request);
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
      return apiNotFound(request, "Task not found");
    }

    return apiSuccess(request, task, { extra: { task } });
  } catch (error) {
    console.error("Error fetching task:", error);
    return apiInternalError(request, "Failed to fetch task");
  }
}

/**
 * PATCH /api/tasks/[taskId]
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
      return apiUnauthorized(request);
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
      return apiNotFound(request, "Task not found");
    }

    if (assignees !== undefined) {
      await db.delete(staffTaskAssignees).where(eq(staffTaskAssignees.taskId, taskId));

      if (assignees.length > 0) {
        await db.insert(staffTaskAssignees).values(
          assignees.map((userId: string) => ({
            taskId: taskId,
            userId,
          })),
        );
      }

      await db.insert(staffTaskComments).values({
        taskId: taskId,
        userId: user.id,
        content: `Updated assignees`,
        isSystem: true,
      });
    }

    if (labels !== undefined) {
      await db.delete(staffTaskLabelRelations).where(eq(staffTaskLabelRelations.taskId, taskId));

      if (labels.length > 0) {
        await db.insert(staffTaskLabelRelations).values(
          labels.map((labelId: string) => ({
            taskId: taskId,
            labelId,
          })),
        );
      }
    }

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

    return apiSuccess(request, updatedTask, { extra: { task: updatedTask } });
  } catch (error) {
    console.error("Error updating task:", error);
    return apiInternalError(request, "Failed to update task");
  }
}

/**
 * DELETE /api/tasks/[taskId]
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
      return apiUnauthorized(request);
    }

    const deleted = await db
      .delete(staffTasks)
      .where(eq(staffTasks.id, taskId))
      .returning({ id: staffTasks.id });

    if (deleted.length === 0) {
      return apiNotFound(request, "Task not found");
    }

    return apiSuccess(request, { deleted: true });
  } catch (error) {
    console.error("Error deleting task:", error);
    return apiInternalError(request, "Failed to delete task");
  }
}
