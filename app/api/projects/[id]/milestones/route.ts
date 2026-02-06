import { NextRequest, NextResponse } from "next/server";
import { db } from "@/lib/db";
import { projectMilestones, projectDeliverables } from "@/lib/db/schema/projects";
import { eq, desc } from "drizzle-orm";

/**
 * GET /api/projects/[id]/milestones
 * Get all milestones for a project
 */
export async function GET(request: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;
  try {

    const milestones = await db
      .select()
      .from(projectMilestones)
      .where(eq(projectMilestones.projectId, id))
      .orderBy(projectMilestones.sortOrder);

    // Fetch deliverables for each milestone
    const milestonesWithDeliverables = await Promise.all(
      milestones.map(async (milestone) => {
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
export async function POST(request: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;
  try {
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
export async function PATCH(request: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;
  try {
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
        completedAt: updates.completedAt ? new Date(updates.completedAt) : undefined,
        updatedAt: new Date(),
      })
      .where(eq(projectMilestones.id, milestoneId))
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
export async function DELETE(request: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;
  try {
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

    await db.delete(projectMilestones).where(eq(projectMilestones.id, milestoneId));

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
