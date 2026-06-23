import { NextRequest } from "next/server";
import { db } from "@/lib/db";
import {
  projectBudgets,
  projectCostEntries,
  projects,
} from "@/lib/db/schema/projects";
import { eq, sql } from "drizzle-orm";
import { createClient } from "@/lib/supabase/server";
import { canAccessClient, resolveRouteAccess } from "@/lib/auth/route-access";
import {
  apiError,
  apiForbidden,
  apiInternalError,
  apiNotFound,
  apiSuccess,
  apiUnauthorized,
} from "@/lib/api/response";

async function requireProjectAccess(request: Request, projectId: string) {
  const supabase = await createClient();
  const {
    data: { user },
    error: authError,
  } = await supabase.auth.getUser();

  if (authError || !user) {
    return { error: apiUnauthorized(request) };
  }

  const access = await resolveRouteAccess(supabase, user);
  const [project] = await db
    .select({ clientId: projects.clientId })
    .from(projects)
    .where(eq(projects.id, projectId))
    .limit(1);

  if (!project) {
    return { error: apiNotFound(request, "Project not found") };
  }

  if (!canAccessClient(access, project.clientId)) {
    return { error: apiForbidden(request) };
  }

  return { user, access };
}

export async function GET(
  request: NextRequest,
  { params }: { params: Promise<{ id: string }> },
) {
  const { id } = await params;
  try {
    const guard = await requireProjectAccess(request, id);
    if ("error" in guard) {
      return guard.error;
    }

    const budgets = await db
      .select({
        budget: projectBudgets,
        totalSpent: sql<number>`COALESCE(SUM(${projectCostEntries.amount}), 0)`,
        entriesCount: sql<number>`COUNT(${projectCostEntries.id})`,
      })
      .from(projectBudgets)
      .leftJoin(
        projectCostEntries,
        eq(projectBudgets.id, projectCostEntries.budgetId),
      )
      .where(eq(projectBudgets.projectId, id))
      .groupBy(projectBudgets.id);

    const costEntries = await db
      .select()
      .from(projectCostEntries)
      .where(eq(projectCostEntries.projectId, id))
      .orderBy(projectCostEntries.entryDate);

    const totalAllocated = budgets.reduce(
      (sum: number, b: (typeof budgets)[number]) =>
        sum + parseFloat(b.budget.allocatedAmount),
      0,
    );
    const totalSpent = costEntries.reduce(
      (sum: number, e: (typeof costEntries)[number]) =>
        sum + parseFloat(e.amount),
      0,
    );

    return apiSuccess(request, {
      budgets,
      costEntries,
      summary: {
        totalAllocated,
        totalSpent,
        remaining: totalAllocated - totalSpent,
        percentageUsed:
          totalAllocated > 0 ? (totalSpent / totalAllocated) * 100 : 0,
      },
    });
  } catch (error) {
    console.error("Error fetching budget data:", error);
    return apiInternalError(request, "Failed to fetch budget data");
  }
}

export async function POST(
  request: NextRequest,
  { params }: { params: Promise<{ id: string }> },
) {
  const { id } = await params;
  try {
    const guard = await requireProjectAccess(request, id);
    if ("error" in guard) {
      return guard.error;
    }

    const body = await request.json();
    const { type } = body;

    if (type === "budget") {
      const { category, allocatedAmount, currency, notes } = body;

      if (!category || !allocatedAmount) {
        return apiError(request, {
          status: 400,
          code: "BAD_REQUEST",
          message: "Category and allocated amount are required",
        });
      }

      const [budget] = await db
        .insert(projectBudgets)
        .values({
          projectId: id,
          category,
          allocatedAmount,
          currency: currency || "USD",
          notes,
        })
        .returning();

      return apiSuccess(request, budget);
    }

    if (type === "cost_entry") {
      const { budgetId, userId, description, amount, entryDate, metadata } =
        body;

      if (!description || !amount || !entryDate) {
        return apiError(request, {
          status: 400,
          code: "BAD_REQUEST",
          message: "Description, amount, and entry date are required",
        });
      }

      const [costEntry] = await db
        .insert(projectCostEntries)
        .values({
          projectId: id,
          budgetId: budgetId || null,
          userId: userId || null,
          description,
          amount,
          entryDate: new Date(entryDate),
          metadata,
        })
        .returning();

      if (budgetId) {
        await db
          .update(projectBudgets)
          .set({
            spentAmount: sql`${projectBudgets.spentAmount} + ${amount}`,
            updatedAt: new Date(),
          })
          .where(eq(projectBudgets.id, budgetId));
      }

      return apiSuccess(request, costEntry);
    }

    return apiError(request, {
      status: 400,
      code: "BAD_REQUEST",
      message: "Invalid type. Must be 'budget' or 'cost_entry'",
    });
  } catch (error) {
    console.error("Error creating budget data:", error);
    return apiInternalError(request, "Failed to create budget data");
  }
}

export async function PATCH(
  request: NextRequest,
  { params }: { params: Promise<{ id: string }> },
) {
  const { id } = await params;
  try {
    const guard = await requireProjectAccess(request, id);
    if ("error" in guard) {
      return guard.error;
    }

    const body = await request.json();
    const { type, itemId, ...updates } = body;

    if (!itemId) {
      return apiError(request, {
        status: 400,
        code: "BAD_REQUEST",
        message: "Item ID is required",
      });
    }

    if (type === "budget") {
      const [budget] = await db
        .update(projectBudgets)
        .set({
          ...updates,
          updatedAt: new Date(),
        })
        .where(eq(projectBudgets.id, itemId))
        .returning();

      return apiSuccess(request, budget);
    }

    if (type === "cost_entry") {
      const [costEntry] = await db
        .update(projectCostEntries)
        .set({
          ...updates,
          approvedAt: updates.approvedBy ? new Date() : undefined,
          updatedAt: new Date(),
        })
        .where(eq(projectCostEntries.id, itemId))
        .returning();

      return apiSuccess(request, costEntry);
    }

    return apiError(request, {
      status: 400,
      code: "BAD_REQUEST",
      message: "Invalid type. Must be 'budget' or 'cost_entry'",
    });
  } catch (error) {
    console.error("Error updating budget data:", error);
    return apiInternalError(request, "Failed to update budget data");
  }
}
