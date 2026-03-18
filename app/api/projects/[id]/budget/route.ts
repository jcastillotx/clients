import { NextRequest, NextResponse } from "next/server";
import { db } from "@/lib/db";
import {
  projectBudgets,
  projectCostEntries,
  projects,
} from "@/lib/db/schema/projects";
import { eq, sql } from "drizzle-orm";
import { createClient } from "@/lib/supabase/server";
import { canAccessClient, resolveRouteAccess } from "@/lib/auth/route-access";

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
 * GET /api/projects/[id]/budget
 * Get budget summary and cost entries for a project
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

    // Fetch budgets with spent amounts
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

    // Fetch all cost entries
    const costEntries = await db
      .select()
      .from(projectCostEntries)
      .where(eq(projectCostEntries.projectId, id))
      .orderBy(projectCostEntries.entryDate);

    // Calculate totals
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

    return NextResponse.json({
      success: true,
      data: {
        budgets,
        costEntries,
        summary: {
          totalAllocated,
          totalSpent,
          remaining: totalAllocated - totalSpent,
          percentageUsed:
            totalAllocated > 0 ? (totalSpent / totalAllocated) * 100 : 0,
        },
      },
    });
  } catch (error) {
    console.error("Error fetching budget data:", error);
    return NextResponse.json(
      {
        success: false,
        error: "Failed to fetch budget data",
      },
      { status: 500 },
    );
  }
}

/**
 * POST /api/projects/[id]/budget
 * Create a new budget category or cost entry
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
    const { type } = body;

    if (type === "budget") {
      // Create budget category
      const { category, allocatedAmount, currency, notes } = body;

      if (!category || !allocatedAmount) {
        return NextResponse.json(
          {
            success: false,
            error: "Category and allocated amount are required",
          },
          { status: 400 },
        );
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

      return NextResponse.json({
        success: true,
        data: budget,
      });
    } else if (type === "cost_entry") {
      // Create cost entry
      const { budgetId, userId, description, amount, entryDate, metadata } =
        body;

      if (!description || !amount || !entryDate) {
        return NextResponse.json(
          {
            success: false,
            error: "Description, amount, and entry date are required",
          },
          { status: 400 },
        );
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

      // Update budget spent amount if budgetId provided
      if (budgetId) {
        await db
          .update(projectBudgets)
          .set({
            spentAmount: sql`${projectBudgets.spentAmount} + ${amount}`,
            updatedAt: new Date(),
          })
          .where(eq(projectBudgets.id, budgetId));
      }

      return NextResponse.json({
        success: true,
        data: costEntry,
      });
    } else {
      return NextResponse.json(
        {
          success: false,
          error: "Invalid type. Must be 'budget' or 'cost_entry'",
        },
        { status: 400 },
      );
    }
  } catch (error) {
    console.error("Error creating budget data:", error);
    return NextResponse.json(
      {
        success: false,
        error: "Failed to create budget data",
      },
      { status: 500 },
    );
  }
}

/**
 * PATCH /api/projects/[id]/budget
 * Update budget or approve cost entry
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
    const { type, itemId, ...updates } = body;

    if (!itemId) {
      return NextResponse.json(
        {
          success: false,
          error: "Item ID is required",
        },
        { status: 400 },
      );
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

      return NextResponse.json({
        success: true,
        data: budget,
      });
    } else if (type === "cost_entry") {
      const [costEntry] = await db
        .update(projectCostEntries)
        .set({
          ...updates,
          approvedAt: updates.approvedBy ? new Date() : undefined,
          updatedAt: new Date(),
        })
        .where(eq(projectCostEntries.id, itemId))
        .returning();

      return NextResponse.json({
        success: true,
        data: costEntry,
      });
    } else {
      return NextResponse.json(
        {
          success: false,
          error: "Invalid type. Must be 'budget' or 'cost_entry'",
        },
        { status: 400 },
      );
    }
  } catch (error) {
    console.error("Error updating budget data:", error);
    return NextResponse.json(
      {
        success: false,
        error: "Failed to update budget data",
      },
      { status: 500 },
    );
  }
}
