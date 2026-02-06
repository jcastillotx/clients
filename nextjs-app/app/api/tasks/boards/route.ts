import { NextRequest, NextResponse } from "next/server";
import { createClient } from "@/lib/supabase/server";
import { db } from "@/lib/db";
import { staffTaskBoards, staffTaskColumns, NewStaffTaskBoard, NewStaffTaskColumn } from "@/lib/db/schema/staff-tasks";
import { eq, and, desc } from "drizzle-orm";

/**
 * GET /api/tasks/boards
 * Get all boards for the current user
 */
export async function GET(request: NextRequest) {
  try {
    const supabase = createClient();
    const {
      data: { user },
      error: authError,
    } = await supabase.auth.getUser();

    if (authError || !user) {
      return NextResponse.json({ error: "Unauthorized" }, { status: 401 });
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

    return NextResponse.json({ boards });
  } catch (error) {
    console.error("Error fetching boards:", error);
    return NextResponse.json({ error: "Failed to fetch boards" }, { status: 500 });
  }
}

/**
 * POST /api/tasks/boards
 * Create a new board with default columns
 */
export async function POST(request: NextRequest) {
  try {
    const supabase = createClient();
    const {
      data: { user },
      error: authError,
    } = await supabase.auth.getUser();

    if (authError || !user) {
      return NextResponse.json({ error: "Unauthorized" }, { status: 401 });
    }

    const body = await request.json();
    const { name, description, color, teamId, isDefault } = body;

    if (!name) {
      return NextResponse.json({ error: "Board name is required" }, { status: 400 });
    }

    // If setting as default, unset other default boards
    if (isDefault) {
      await db.update(staffTaskBoards).set({ isDefault: false }).where(eq(staffTaskBoards.isDefault, true));
    }

    // Create the board
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

    // Create default columns
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

    // Update column order
    await db
      .update(staffTaskBoards)
      .set({ columnOrder: columns.map((c) => c.id) })
      .where(eq(staffTaskBoards.id, board.id));

    return NextResponse.json(
      {
        board: { ...board, columns },
      },
      { status: 201 },
    );
  } catch (error) {
    console.error("Error creating board:", error);
    return NextResponse.json({ error: "Failed to create board" }, { status: 500 });
  }
}
