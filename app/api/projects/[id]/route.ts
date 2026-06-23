import { NextRequest } from "next/server";
import { db } from "@/lib/db";
import {
  projects,
  projectBudgets,
  projectMilestones,
  projectDeliverables,
  projectCostEntries,
} from "@/lib/db/schema/projects";
import { eq, and, isNull } from "drizzle-orm";
import { createClient } from "@/lib/supabase/server";
import { isAdminUser } from "@/lib/rbac/check";
import {
  apiForbidden,
  apiInternalError,
  apiNotFound,
  apiSuccess,
  apiUnauthorized,
} from "@/lib/api/response";

/**
 * GET /api/projects/[id]
 */
export async function GET(request: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;
  try {
    const supabase = await createClient();
    const { data: { user } } = await supabase.auth.getUser();
    if (!user) {
      return apiUnauthorized(request);
    }

    const [project] = await db
      .select()
      .from(projects)
      .where(and(eq(projects.id, id), isNull(projects.deletedAt)))
      .limit(1);

    if (!project) {
      return apiNotFound(request, "Project not found");
    }

    const [budgets, milestones, deliverables, costEntries] = await Promise.all([
      db.select().from(projectBudgets).where(eq(projectBudgets.projectId, id)),
      db
        .select()
        .from(projectMilestones)
        .where(eq(projectMilestones.projectId, id))
        .orderBy(projectMilestones.sortOrder),
      db
        .select()
        .from(projectDeliverables)
        .where(eq(projectDeliverables.projectId, id))
        .orderBy(projectDeliverables.sortOrder),
      db
        .select()
        .from(projectCostEntries)
        .where(eq(projectCostEntries.projectId, id))
        .orderBy(projectCostEntries.entryDate),
    ]);

    return apiSuccess(request, {
      ...project,
      budgets,
      milestones,
      deliverables,
      costEntries,
    });
  } catch (error) {
    console.error("Error fetching project:", error);
    return apiInternalError(request, "Failed to fetch project");
  }
}

/**
 * PATCH /api/projects/[id]
 */
export async function PATCH(request: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;
  try {
    const supabase = await createClient();
    const { data: { user } } = await supabase.auth.getUser();
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

    const body = await request.json();

    const [existingProject] = await db
      .select()
      .from(projects)
      .where(and(eq(projects.id, id), isNull(projects.deletedAt)))
      .limit(1);

    if (!existingProject) {
      return apiNotFound(request, "Project not found");
    }

    const [updatedProject] = await db
      .update(projects)
      .set({
        ...body,
        startDate: body.startDate ? new Date(body.startDate) : undefined,
        endDate: body.endDate ? new Date(body.endDate) : undefined,
        updatedAt: new Date(),
      })
      .where(eq(projects.id, id))
      .returning();

    return apiSuccess(request, updatedProject);
  } catch (error) {
    console.error("Error updating project:", error);
    return apiInternalError(request, "Failed to update project");
  }
}

/**
 * DELETE /api/projects/[id]
 */
export async function DELETE(request: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;
  try {
    const supabase = await createClient();
    const { data: { user } } = await supabase.auth.getUser();
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

    const [existingProject] = await db
      .select()
      .from(projects)
      .where(and(eq(projects.id, id), isNull(projects.deletedAt)))
      .limit(1);

    if (!existingProject) {
      return apiNotFound(request, "Project not found");
    }

    await db
      .update(projects)
      .set({
        deletedAt: new Date(),
        updatedAt: new Date(),
      })
      .where(eq(projects.id, id));

    return apiSuccess(request, { deleted: true }, {
      extra: { message: "Project deleted successfully" },
    });
  } catch (error) {
    console.error("Error deleting project:", error);
    return apiInternalError(request, "Failed to delete project");
  }
}
