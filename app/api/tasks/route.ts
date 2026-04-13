import { NextRequest, NextResponse } from "next/server";
import { createClient } from "@/lib/supabase/server";
import { getAuthBaseUrl } from "@/lib/supabase/redirect-url";
import { dispatchNotification } from "@/lib/notifications/service";
import { db } from "@/lib/db";
import { staffTasks, staffTaskAssignees, staffTaskLabelRelations } from "@/lib/db/schema/staff-tasks";
import { eq } from "drizzle-orm";

/**
 * POST /api/tasks
 * Create a new task
 */
export async function POST(request: NextRequest) {
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
    const { boardId, columnId, title, description, priority, dueDate, estimatedHours, assignees, labels, parentId } =
      body;

    if (!boardId || !columnId || !title) {
      return NextResponse.json({ error: "Board ID, Column ID, and Title are required" }, { status: 400 });
    }

    // Get the max position in the column
    const existingTasks = await db.query.staffTasks.findMany({
      where: eq(staffTasks.columnId, columnId),
      orderBy: (tasks, { desc }) => [desc(tasks.position)],
      limit: 1,
    });

    const position = existingTasks.length > 0 ? (existingTasks[0].position || 0) + 1 : 0;

    // Create the task
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

    // Add assignees if provided
    if (assignees && assignees.length > 0) {
      await db.insert(staffTaskAssignees).values(
        assignees.map((userId: string) => ({
          taskId: task.id,
          userId,
        })),
      );
    }

    // Add labels if provided
    if (labels && labels.length > 0) {
      await db.insert(staffTaskLabelRelations).values(
        labels.map((labelId: string) => ({
          taskId: task.id,
          labelId,
        })),
      );
    }

    // Fetch the complete task with relations
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

    return NextResponse.json({ task: completeTask }, { status: 201 });
  } catch (error) {
    console.error("Error creating task:", error);
    return NextResponse.json({ error: "Failed to create task" }, { status: 500 });
  }
}
