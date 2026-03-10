import { NextRequest, NextResponse } from "next/server";
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

/**
 * GET /api/projects/[id]
 * Get a single project with all related data
 */
export async function GET(request: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;
  try {
    const supabase = await createClient();
    const { data: { user } } = await supabase.auth.getUser();
    if (!user) {
      return NextResponse.json({ success: false, error: "Unauthorized" }, { status: 401 });
    }

    // Fetch project
    const [project] = await db
      .select()
      .from(projects)
      .where(and(eq(projects.id, id), isNull(projects.deletedAt)))
      .limit(1);

    if (!project) {
      return NextResponse.json(
        {
          success: false,
          error: "Project not found",
        },
        { status: 404 },
      );
    }

    // Fetch related data
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

    return NextResponse.json({
      success: true,
      data: {
        ...project,
        budgets,
        milestones,
        deliverables,
        costEntries,
      },
    });
  } catch (error) {
    console.error("Error fetching project:", error);
    return NextResponse.json(
      {
        success: false,
        error: "Failed to fetch project",
      },
      { status: 500 },
    );
  }
}

/**
 * PATCH /api/projects/[id]
 * Update a project
 */
export async function PATCH(request: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;
  try {
    const supabase = await createClient();
    const { data: { user } } = await supabase.auth.getUser();
    if (!user) {
      return NextResponse.json({ success: false, error: "Unauthorized" }, { status: 401 });
    }
    const [{ data: dbUser }, { data: roleRows }] = await Promise.all([
      supabase.from("users").select("is_super_admin").eq("id", user.id).maybeSingle(),
      supabase.from("user_roles").select("role:roles(name)").eq("user_id", user.id),
    ]);
    if (!isAdminUser(user, dbUser, roleRows)) {
      return NextResponse.json({ success: false, error: "Forbidden" }, { status: 403 });
    }

    const body = await request.json();

    // Check if project exists
    const [existingProject] = await db
      .select()
      .from(projects)
      .where(and(eq(projects.id, id), isNull(projects.deletedAt)))
      .limit(1);

    if (!existingProject) {
      return NextResponse.json(
        {
          success: false,
          error: "Project not found",
        },
        { status: 404 },
      );
    }

    // Update project
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

    return NextResponse.json({
      success: true,
      data: updatedProject,
    });
  } catch (error) {
    console.error("Error updating project:", error);
    return NextResponse.json(
      {
        success: false,
        error: "Failed to update project",
      },
      { status: 500 },
    );
  }
}

/**
 * DELETE /api/projects/[id]
 * Soft delete a project
 */
export async function DELETE(request: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;
  try {
    const supabase = await createClient();
    const { data: { user } } = await supabase.auth.getUser();
    if (!user) {
      return NextResponse.json({ success: false, error: "Unauthorized" }, { status: 401 });
    }
    const [{ data: dbUser }, { data: roleRows }] = await Promise.all([
      supabase.from("users").select("is_super_admin").eq("id", user.id).maybeSingle(),
      supabase.from("user_roles").select("role:roles(name)").eq("user_id", user.id),
    ]);
    if (!isAdminUser(user, dbUser, roleRows)) {
      return NextResponse.json({ success: false, error: "Forbidden" }, { status: 403 });
    }

    // Check if project exists
    const [existingProject] = await db
      .select()
      .from(projects)
      .where(and(eq(projects.id, id), isNull(projects.deletedAt)))
      .limit(1);

    if (!existingProject) {
      return NextResponse.json(
        {
          success: false,
          error: "Project not found",
        },
        { status: 404 },
      );
    }

    // Soft delete
    await db
      .update(projects)
      .set({
        deletedAt: new Date(),
        updatedAt: new Date(),
      })
      .where(eq(projects.id, id));

    return NextResponse.json({
      success: true,
      message: "Project deleted successfully",
    });
  } catch (error) {
    console.error("Error deleting project:", error);
    return NextResponse.json(
      {
        success: false,
        error: "Failed to delete project",
      },
      { status: 500 },
    );
  }
}
