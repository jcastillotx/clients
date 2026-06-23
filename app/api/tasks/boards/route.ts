import { NextRequest } from "next/server";
import { createClient } from "@/lib/supabase/server";
import {
  apiError,
  apiInternalError,
  apiSuccess,
  apiUnauthorized,
} from "@/lib/api/response";
import { db } from "@/lib/db";
import { staffTaskBoards, staffTaskColumns, NewStaffTaskColumn } from "@/lib/db/schema/staff-tasks";
import { eq, desc } from "drizzle-orm";

/**
 * GET /api/tasks/boards
 */
export async function GET(request: NextRequest) {
  try {
    const supabase = await createClient();
    const {
      data: { user },
      error: authError,
    } = await supabase.auth.getUser();

    if (authError || !user) {
      return apiUnauthorized(request);
    }

    const boards = await db.query.staffTaskBoards.findMany({
      where: eq(staffTaskBoards.isArchived, false),
      orderBy: [desc(staffTaskBoards.isDefault), desc(staffTaskBoards.sortOrder)],
      with: {
        columns: {
          orderBy: (columns, { asc }) => [asc(columns.position)],
        },
        creator: {
          columns: {
            id: true,
            name: true,
            email: true,
            avatar: true,
          },
        },
      },
    });

    return apiSuccess(request, boards, { extra: { boards } });
  } catch (error) {
    console.error("Error fetching boards:", error);
    return apiInternalError(request, "Failed to fetch boards");
  }
}

/**
 * POST /api/tasks/boards
 */
export async function POST(request: NextRequest) {
  try {
    const supabase = await createClient();
    const {
      data: { user },
      error: authError,
    } = await supabase.auth.getUser();

    if (authError || !user) {
      return apiUnauthorized(request);
    }

    const body = await request.json();
    const { name, description, color, teamId, isDefault } = body;

    if (!name) {
      return apiError(request, {
        status: 400,
        code: "BAD_REQUEST",
        message: "Board name is required",
      });
    }

    if (isDefault) {
      await db.update(staffTaskBoards).set({ isDefault: false }).where(eq(staffTaskBoards.isDefault, true));
    }

    const [board] = await db
      .insert(staffTaskBoards)
      .values({
        name,
        description,
        color: color || "#3b82f6",
        teamId,
        isDefault: isDefault || false,
        createdBy: user.id,
      })
      .returning();

    const defaultColumns: NewStaffTaskColumn[] = [
      {
        boardId: board.id,
        name: "To Do",
        position: 0,
        color: "#94a3b8",
        icon: "list",
      },
      {
        boardId: board.id,
        name: "In Progress",
        position: 1,
        color: "#3b82f6",
        icon: "loader",
      },
      {
        boardId: board.id,
        name: "Review",
        position: 2,
        color: "#f59e0b",
        icon: "eye",
      },
      {
        boardId: board.id,
        name: "Done",
        position: 3,
        color: "#10b981",
        icon: "check",
        isDoneColumn: true,
      },
    ];

    const columns = await db.insert(staffTaskColumns).values(defaultColumns).returning();

    await db
      .update(staffTaskBoards)
      .set({ columnOrder: columns.map((c) => c.id) })
      .where(eq(staffTaskBoards.id, board.id));

    const payload = { ...board, columns };

    return apiSuccess(request, payload, {
      status: 201,
      extra: { board: payload },
    });
  } catch (error) {
    console.error("Error creating board:", error);
    return apiInternalError(request, "Failed to create board");
  }
}
