import { NextRequest } from "next/server";
import { createClient } from "@/lib/supabase/server";
import { getAuthBaseUrl } from "@/lib/supabase/redirect-url";
import { dispatchNotification } from "@/lib/notifications/service";
import {
  apiError,
  apiInternalError,
  apiSuccess,
  apiUnauthorized,
} from "@/lib/api/response";
import { db } from "@/lib/db";
import { staffTasks, staffTaskAssignees, staffTaskLabelRelations } from "@/lib/db/schema/staff-tasks";
import { eq } from "drizzle-orm";

/**
 * POST /api/tasks
 */
export async function POST(request: NextRequest) {
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
    const { boardId, columnId, title, description, priority, dueDate, estimatedHours, assignees, labels, parentId } =
      body;

    if (!boardId || !columnId || !title) {
      return apiError(request, {
        status: 400,
        code: "BAD_REQUEST",
        message: "Board ID, Column ID, and Title are required",
      });
    }

    const existingTasks = await db.query.staffTasks.findMany({
      where: eq(staffTasks.columnId, columnId),
      orderBy: (tasks, { desc }) => [desc(tasks.position)],
      limit: 1,
    });

    const position = existingTasks.length > 0 ? (existingTasks[0].position || 0) + 1 : 0;

    const [task] = await db
      .insert(staffTasks)
      .values({
        boardId,
        columnId,
        parentId,
        title,
        description,
        priority: priority || "normal",
        dueDate: dueDate ? new Date(dueDate) : null,
        estimatedHours,
        position,
        createdBy: user.id,
      })
      .returning();

    if (assignees && assignees.length > 0) {
      await db.insert(staffTaskAssignees).values(
        assignees.map((userId: string) => ({
          taskId: task.id,
          userId,
        })),
      );
    }

    if (labels && labels.length > 0) {
      await db.insert(staffTaskLabelRelations).values(
        labels.map((labelId: string) => ({
          taskId: task.id,
          labelId,
        })),
      );
    }

    const completeTask = await db.query.staffTasks.findFirst({
      where: eq(staffTasks.id, task.id),
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

    const { data: profile } = await supabase
      .from("users")
      .select("client_id, name, email")
      .eq("id", user.id)
      .maybeSingle();

    if (profile?.client_id && completeTask) {
      try {
        const base = getAuthBaseUrl();
        const assigneeIds = (assignees as string[] | undefined)?.filter(Boolean) ?? [];
        await dispatchNotification({
          eventType: "staff_task_created",
          clientId: profile.client_id,
          subjectType: "task",
          subjectId: completeTask.id,
          actorUserId: user.id,
          recipientUserIds: assigneeIds.length > 0 ? assigneeIds : undefined,
          data: {
            task_title: completeTask.title,
            task_priority: completeTask.priority,
            task_url: `${base}/tasks/${boardId}`,
            created_by_name: profile.name || profile.email || "",
          },
        });
      } catch (notifyErr) {
        console.error("[POST /api/tasks] notification dispatch:", notifyErr);
      }
    }

    return apiSuccess(request, completeTask, { status: 201, extra: { task: completeTask } });
  } catch (error) {
    console.error("Error creating task:", error);
    return apiInternalError(request, "Failed to create task");
  }
}
