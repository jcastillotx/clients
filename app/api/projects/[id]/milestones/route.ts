import { NextRequest, NextResponse } from "next/server";
import { and, eq } from "drizzle-orm";
import { canAccessClient, resolveRouteAccess } from "@/lib/auth/route-access";
import { db } from "@/lib/db";
import {
  projectDeliverables,
  projectMilestones,
  projects,
} from "@/lib/db/schema/projects";
import { createClient } from "@/lib/supabase/server";

async function requireProjectAccess(projectId: string) {
  const supabase = await createClient();
  const {
    data: { user },
    error: authError,
  } = await supabase.auth.getUser();

  if (authError || !user) {
    return {
      error: NextResponse.json(
        { success: false, error: "Unauthorized" },
        { status: 401 },
      ),
    };
  }

  const access = await resolveRouteAccess(supabase, user);
  const [project] = await db
    .select({ clientId: projects.clientId })
    .from(projects)
    .where(eq(projects.id, projectId))
    .limit(1);

  if (!project) {
    return {
      error: NextResponse.json(
        { success: false, error: "Project not found" },
        { status: 404 },
      ),
    };
  }

  if (!canAccessClient(access, project.clientId)) {
    return {
      error: NextResponse.json(
        { success: false, error: "Forbidden" },
        { status: 403 },
      ),
    };
  }

  return { user, access };
}

/**
 * GET /api/projects/[id]/milestones
 * Get all milestones for a project
 */
export async function GET(
  request: NextRequest,
  { params }: { params: Promise<{ id: string }> },
) {
  const { id } = await params;
  try {
    const guard = await requireProjectAccess(id);
    if ("error" in guard) {
      return guard.error;
    }

    const milestones = await db
      .select()
      .from(projectMilestones)
      .where(eq(projectMilestones.projectId, id))
      .orderBy(projectMilestones.sortOrder);

    // Fetch deliverables for each milestone
    const milestonesWithDeliverables = await Promise.all(
      milestones.map(async (milestone: (typeof milestones)[number]) => {
        const deliverables = await db
          .select()
          .from(projectDeliverables)
          .where(eq(projectDeliverables.milestoneId, milestone.id))
          .orderBy(projectDeliverables.sortOrder);

        return {
          ...milestone,
          deliverables,
        };
      }),
    );

    return NextResponse.json({
      success: true,
      data: milestonesWithDeliverables,
    });
  } catch (error) {
    console.error("Error fetching milestones:", error);
    return NextResponse.json(
      {
        success: false,
        error: "Failed to fetch milestones",
      },
      { status: 500 },
    );
  }
}

/**
 * POST /api/projects/[id]/milestones
 * Create a new milestone
 */
export async function POST(
  request: NextRequest,
  { params }: { params: Promise<{ id: string }> },
) {
  const { id } = await params;
  try {
    const guard = await requireProjectAccess(id);
    if ("error" in guard) {
      return guard.error;
    }

    const body = await request.json();

    const { title, description, dueDate, sortOrder } = body;

    if (!title) {
      return NextResponse.json(
        {
          success: false,
          error: "Title is required",
        },
        { status: 400 },
      );
    }

    const [milestone] = await db
      .insert(projectMilestones)
      .values({
        projectId: id,
        title,
        description,
        dueDate: dueDate ? new Date(dueDate) : null,
        sortOrder: sortOrder ?? 0,
      })
      .returning();

    return NextResponse.json({
      success: true,
      data: milestone,
    });
  } catch (error) {
    console.error("Error creating milestone:", error);
    return NextResponse.json(
      {
        success: false,
        error: "Failed to create milestone",
      },
      { status: 500 },
    );
  }
}

/**
 * PATCH /api/projects/[id]/milestones
 * Update a milestone
 */
export async function PATCH(
  request: NextRequest,
  { params }: { params: Promise<{ id: string }> },
) {
  const { id } = await params;
  try {
    const guard = await requireProjectAccess(id);
    if ("error" in guard) {
      return guard.error;
    }

    const body = await request.json();
    const { milestoneId, ...updates } = body;

    if (!milestoneId) {
      return NextResponse.json(
        {
          success: false,
          error: "Milestone ID is required",
        },
        { status: 400 },
      );
    }

    const [milestone] = await db
      .update(projectMilestones)
      .set({
        ...updates,
        dueDate: updates.dueDate ? new Date(updates.dueDate) : undefined,
        completedAt: updates.completedAt
          ? new Date(updates.completedAt)
          : undefined,
        updatedAt: new Date(),
      })
      .where(
        and(
          eq(projectMilestones.id, milestoneId),
          eq(projectMilestones.projectId, id),
        ),
      )
      .returning();

    return NextResponse.json({
      success: true,
      data: milestone,
    });
  } catch (error) {
    console.error("Error updating milestone:", error);
    return NextResponse.json(
      {
        success: false,
        error: "Failed to update milestone",
      },
      { status: 500 },
    );
  }
}

/**
 * DELETE /api/projects/[id]/milestones
 * Delete a milestone
 */
export async function DELETE(
  request: NextRequest,
  { params }: { params: Promise<{ id: string }> },
) {
  const { id } = await params;
  try {
    const guard = await requireProjectAccess(id);
    if ("error" in guard) {
      return guard.error;
    }

    const searchParams = request.nextUrl.searchParams;
    const milestoneId = searchParams.get("milestoneId");

    if (!milestoneId) {
      return NextResponse.json(
        {
          success: false,
          error: "Milestone ID is required",
        },
        { status: 400 },
      );
    }

    await db
      .delete(projectMilestones)
      .where(
        and(
          eq(projectMilestones.id, milestoneId),
          eq(projectMilestones.projectId, id),
        ),
      );

    return NextResponse.json({
      success: true,
      message: "Milestone deleted successfully",
    });
  } catch (error) {
    console.error("Error deleting milestone:", error);
    return NextResponse.json(
      {
        success: false,
        error: "Failed to delete milestone",
      },
      { status: 500 },
    );
  }
}
