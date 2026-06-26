import { NextRequest } from "next/server";
import { db } from "@/lib/db";
import { projects } from "@/lib/db/schema/projects";
import { projectTaskTemplates } from "@/lib/db/schema/project-templates";
import {
  staffTaskBoards,
  staffTaskColumns,
  staffTasks,
  staffTaskChecklists,
  staffTaskLabels,
} from "@/lib/db/schema/staff-tasks";
import { eq, and, isNull } from "drizzle-orm";
import { createClient } from "@/lib/supabase/server";
import { isAdminUser } from "@/lib/rbac/check";
import { builtInProjectTemplates } from "@/lib/templates/project-templates";
import type { ProjectTemplatePhase } from "@/lib/db/schema/project-templates";
import {
  apiError,
  apiForbidden,
  apiInternalError,
  apiNotFound,
  apiSuccess,
  apiUnauthorized,
} from "@/lib/api/response";

type ProjectMetadata = NonNullable<typeof projects.$inferSelect.metadata>;

const asProjectMetadata = (value: unknown): ProjectMetadata => {
  return value && typeof value === "object" && !Array.isArray(value) ? (value as ProjectMetadata) : {};
};

/**
 * POST /api/projects/[id]/apply-template
 *
 * Applies a project task template to a project by creating:
 * - A task board for the project
 * - Columns for each phase
 * - Tasks for each task in the template
 * - Checklists for each checklist item
 */
export async function POST(request: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  try {
    const { id: projectId } = await params;

    const supabase = await createClient();
    const {
      data: { user },
    } = await supabase.auth.getUser();
    if (!user) {
      return apiUnauthorized(request);
    }
    const [{ data: dbUser }, { data: roleRows }] = await Promise.all([
      supabase.from("users").select("is_super_admin").eq("id", user.id).maybeSingle(),
      supabase.from("user_roles").select("role:roles(name)").eq("user_id", user.id),
    ]);
    if (!isAdminUser(user, dbUser, roleRows)) {
      return apiForbidden(request);
    }

    // Verify project exists
    const [project] = await db
      .select()
      .from(projects)
      .where(and(eq(projects.id, projectId), isNull(projects.deletedAt)))
      .limit(1);

    if (!project) {
      return apiNotFound(request, "Project not found");
    }

    const body = await request.json();
    const { templateId } = body;

    if (!templateId) {
      return apiError(request, {
        status: 400,
        code: "BAD_REQUEST",
        message: "Template ID is required",
      });
    }

    // Resolve template phases
    let phases: ProjectTemplatePhase[];
    let templateName: string;

    if (templateId.startsWith("builtin-")) {
      const slug = templateId.replace("builtin-", "");
      const builtIn = builtInProjectTemplates.find(
        (t) => t.name.toLowerCase().replace(/\s+/g, "-") === slug,
      );
      if (!builtIn) {
        return apiNotFound(request, "Built-in template not found");
      }
      phases = builtIn.phases;
      templateName = builtIn.name;
    } else {
      const [dbTemplate] = await db
        .select()
        .from(projectTaskTemplates)
        .where(and(eq(projectTaskTemplates.id, templateId), isNull(projectTaskTemplates.deletedAt)))
        .limit(1);

      if (!dbTemplate) {
        return apiNotFound(request, "Template not found");
      }
      phases = dbTemplate.phases;
      templateName = dbTemplate.name;
    }

    // Create a task board for this project
    const [board] = await db
      .insert(staffTaskBoards)
      .values({
        name: `${project.name} - ${templateName}`,
        description: `Task board generated from template: ${templateName}`,
        createdBy: user.id,
        isDefault: false,
        columnOrder: [],
      })
      .returning();

    // Create columns for each phase
    const columnIds: string[] = [];
    for (const phase of phases) {
      const [column] = await db
        .insert(staffTaskColumns)
        .values({
          boardId: board.id,
          name: phase.name,
          position: phase.sortOrder,
        })
        .returning();
      columnIds.push(column.id);
    }

    // Update board with column order
    await db.update(staffTaskBoards).set({ columnOrder: columnIds }).where(eq(staffTaskBoards.id, board.id));
    await db
      .update(projects)
      .set({
        metadata: {
          ...asProjectMetadata(project.metadata),
          taskBoardId: board.id,
        },
        updatedAt: new Date(),
      })
      .where(eq(projects.id, projectId));

    // Collect all unique labels from the template
    const allLabels = new Set<string>();
    for (const phase of phases) {
      for (const task of phase.tasks) {
        if (task.labels) {
          task.labels.forEach((l) => allLabels.add(l));
        }
      }
    }

    // Create labels on the board
    const labelMap = new Map<string, string>();
    if (allLabels.size > 0) {
      for (const labelName of allLabels) {
        const [label] = await db
          .insert(staffTaskLabels)
          .values({
            boardId: board.id,
            name: labelName,
            color: "#6366f1",
          })
          .returning();
        labelMap.set(labelName, label.id);
      }
    }

    // Create tasks and checklists
    let totalTasks = 0;
    let totalChecklists = 0;

    for (let phaseIdx = 0; phaseIdx < phases.length; phaseIdx++) {
      const phase = phases[phaseIdx];
      const columnId = columnIds[phaseIdx];

      for (const task of phase.tasks) {
        const [createdTask] = await db
          .insert(staffTasks)
          .values({
            boardId: board.id,
            columnId,
            title: task.title,
            description: task.description || null,
            priority: task.priority || "normal",
            estimatedHours: task.estimatedHours?.toString() || null,
            position: task.sortOrder,
            createdBy: user.id,
            clientId: project.clientId,
            metadata: {
              tags: task.labels || [],
            },
          })
          .returning();
        totalTasks++;

        // Create checklists
        if (task.checklist && task.checklist.length > 0) {
          await db.insert(staffTaskChecklists).values(
            task.checklist.map((item) => ({
              taskId: createdTask.id,
              title: item.title,
              position: item.sortOrder,
              isCompleted: false,
            })),
          );
          totalChecklists += task.checklist.length;
        }
      }
    }

    const result = {
      boardId: board.id,
      boardName: board.name,
      totalPhases: phases.length,
      totalTasks,
      totalChecklists,
    };

    return apiSuccess(request, result);
  } catch (error) {
    console.error("Error applying template to project:", error);
    return apiInternalError(request, "Failed to apply template");
  }
}
