import { NextRequest, NextResponse } from "next/server";
import { db } from "@/lib/db";
import { projects, projectBudgets, projectMilestones, projectDeliverables } from "@/lib/db/schema/projects";
import { eq, and, isNull, desc, sql } from "drizzle-orm";
import { z } from "zod";
import { createProjectSchema } from "@/lib/validations/project";

/**
 * GET /api/projects
 * List all projects with optional filtering
 */
export async function GET(request: NextRequest) {
  try {
    const searchParams = request.nextUrl.searchParams;
    const clientId = searchParams.get("clientId");
    const status = searchParams.get("status");
    const includeDeleted = searchParams.get("includeDeleted") === "true";

    let query = db
      .select({
        project: projects,
        budgetSummary: sql<number>`COALESCE(SUM(${projectBudgets.allocatedAmount}), 0)`,
        milestonesCount: sql<number>`COUNT(DISTINCT ${projectMilestones.id})`,
        deliverablesCount: sql<number>`COUNT(DISTINCT ${projectDeliverables.id})`,
      })
      .from(projects)
      .leftJoin(projectBudgets, eq(projects.id, projectBudgets.projectId))
      .leftJoin(projectMilestones, eq(projects.id, projectMilestones.projectId))
      .leftJoin(projectDeliverables, eq(projects.id, projectDeliverables.projectId))
      .groupBy(projects.id)
      .orderBy(desc(projects.createdAt));

    const conditions = [];

    if (!includeDeleted) {
      conditions.push(isNull(projects.deletedAt));
    }

    if (clientId) {
      conditions.push(eq(projects.clientId, clientId));
    }

    if (status) {
      conditions.push(eq(projects.status, status as any));
    }

    if (conditions.length > 0) {
      query = query.where(and(...conditions)) as any;
    }

    const result = await query;

    return NextResponse.json({
      success: true,
      data: result,
    });
  } catch (error) {
    console.error("Error fetching projects:", error);
    return NextResponse.json(
      {
        success: false,
        error: "Failed to fetch projects",
      },
      { status: 500 },
    );
  }
}

/**
 * POST /api/projects
 * Create a new project
 */
export async function POST(request: NextRequest) {
  try {
    const body = await request.json();

    const {
      clientId,
      name,
      description,
      status = "planning",
      startDate,
      endDate,
      estimatedHours,
      budgetAmount,
      currency = "USD",
      projectManagerId,
      teamMembers,
      metadata,
      budgets,
      milestones,
    } = body;

    // Validate required fields
    if (!clientId || !name) {
      return NextResponse.json(
        {
          success: false,
          error: "Client ID and name are required",
        },
        { status: 400 },
      );
    }

    // Create project
    const [project] = await db
      .insert(projects)
      .values({
        clientId,
        name,
        description,
        status,
        startDate: startDate ? new Date(startDate) : null,
        endDate: endDate ? new Date(endDate) : null,
        estimatedHours,
        budgetAmount,
        currency,
        projectManagerId,
        teamMembers,
        metadata,
      })
      .returning();

    // Create budgets if provided
    if (budgets && Array.isArray(budgets) && budgets.length > 0) {
      await db.insert(projectBudgets).values(
        budgets.map((budget: any) => ({
          projectId: project.id,
          category: budget.category,
          allocatedAmount: budget.allocatedAmount,
          currency: budget.currency || currency,
          notes: budget.notes,
        })),
      );
    }

    // Create milestones if provided
    if (milestones && Array.isArray(milestones) && milestones.length > 0) {
      await db.insert(projectMilestones).values(
        milestones.map((milestone: any, index: number) => ({
          projectId: project.id,
          title: milestone.title,
          description: milestone.description,
          dueDate: milestone.dueDate ? new Date(milestone.dueDate) : null,
          sortOrder: index,
        })),
      );
    }

    return NextResponse.json({
      success: true,
      data: project,
    });
  } catch (error) {
    console.error("Error creating project:", error);
    return NextResponse.json(
      {
        success: false,
        error: "Failed to create project",
      },
      { status: 500 },
    );
  }
}
