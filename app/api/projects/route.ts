import { NextRequest } from "next/server";
import { db } from "@/lib/db";
import { projects, projectBudgets, projectMilestones, projectDeliverables } from "@/lib/db/schema/projects";
import { eq, and, isNull, desc, sql } from "drizzle-orm";
import { createClient } from "@/lib/supabase/server";
import { isAdminUser } from "@/lib/rbac/check";
import {
  apiError,
  apiForbidden,
  apiInternalError,
  apiSuccess,
  apiUnauthorized,
} from "@/lib/api/response";

/**
 * GET /api/projects
 */
export async function GET(request: NextRequest) {
  try {
    const supabase = await createClient();
    const { data: { user } } = await supabase.auth.getUser();
    if (!user) {
      return apiUnauthorized(request);
    }

    const searchParams = request.nextUrl.searchParams;
    const clientId = searchParams.get("clientId");
    const status = searchParams.get("status");
    const includeDeleted = searchParams.get("includeDeleted") === "true";

    const conditions = [];

    if (!includeDeleted) {
      conditions.push(isNull(projects.deletedAt));
    }

    if (clientId) {
      conditions.push(eq(projects.clientId, clientId));
    }

    if (status) {
      const validStatuses = ["planning", "active", "on_hold", "completed", "cancelled"] as const;
      type ProjectStatus = (typeof validStatuses)[number];
      if (validStatuses.includes(status as ProjectStatus)) {
        conditions.push(eq(projects.status, status as ProjectStatus));
      }
    }

    const baseQuery = db
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

    const result =
      conditions.length > 0
        ? await baseQuery.where(and(...conditions))
        : await baseQuery;

    return apiSuccess(request, result);
  } catch (error) {
    console.error("Error fetching projects:", error);
    return apiInternalError(request, "Failed to fetch projects");
  }
}

/**
 * POST /api/projects
 */
export async function POST(request: NextRequest) {
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

    if (!clientId || !name) {
      return apiError(request, {
        status: 400,
        code: "BAD_REQUEST",
        message: "Client ID and name are required",
      });
    }

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

    if (budgets && Array.isArray(budgets) && budgets.length > 0) {
      await db.insert(projectBudgets).values(
        budgets.map((budget: {
          category: "development" | "design" | "marketing" | "infrastructure" | "other";
          allocatedAmount: string;
          currency?: string;
          notes?: string;
        }) => ({
          projectId: project.id,
          category: budget.category,
          allocatedAmount: budget.allocatedAmount,
          currency: budget.currency || currency,
          notes: budget.notes,
        })),
      );
    }

    if (milestones && Array.isArray(milestones) && milestones.length > 0) {
      await db.insert(projectMilestones).values(
        milestones.map((milestone: {
          title: string;
          description?: string;
          dueDate?: string;
        }, index: number) => ({
          projectId: project.id,
          title: milestone.title,
          description: milestone.description,
          dueDate: milestone.dueDate ? new Date(milestone.dueDate) : null,
          sortOrder: index,
        })),
      );
    }

    return apiSuccess(request, project, { status: 201 });
  } catch (error) {
    console.error("Error creating project:", error);
    return apiInternalError(request, "Failed to create project");
  }
}
