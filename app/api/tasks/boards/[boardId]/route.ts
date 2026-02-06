import { NextRequest, NextResponse } from "next/server";
import { createClient } from "@/lib/supabase/server";
import { db } from "@/lib/db";
import { staffTaskBoards } from "@/lib/db/schema/staff-tasks";
import { eq } from "drizzle-orm";

/**
 * GET /api/tasks/boards/[boardId]
 * Get a specific board with all related data
 */
export async function GET(request: NextRequest, { params }: { params: Promise<{ boardId: string }> }) {
  const { boardId } = await params;
  try {
    const supabase = await createClient();
    const {
      data: { user },
      error: authError,
    } = await supabase.auth.getUser();

    if (authError || !user) {
      return NextResponse.json({ error: "Unauthorized" }, { status: 401 });
    }

    const board = await db.query.staffTaskBoards.findFirst({
      where: eq(staffTaskBoards.id, boardId),
      with: {
        columns: {
          orderBy: (columns, { asc }) => [asc(columns.position)],
          with: {
            tasks: {
              orderBy: (tasks: any, { asc }: any) => [asc(tasks.position)],
              with: {
                assignees: {
                  with: {
                    user: {
                      columns: {
                        id: true,
                        name: true,
                        email: true,
                        avatar: true,
                      },
                    },
                  },
                },
                labelRelations: {
                  with: {
                    label: true,
                  },
                },
                checklists: {
                  orderBy: (checklists: any, { asc }: any) => [asc(checklists.position)],
                },
              },
            },
          },
        },
        labels: true,
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

    if (!board) {
      return NextResponse.json({ error: "Board not found" }, { status: 404 });
    }

    return NextResponse.json({ board });
  } catch (error) {
    console.error("Error fetching board:", error);
    return NextResponse.json({ error: "Failed to fetch board" }, { status: 500 });
  }
}

/**
 * PATCH /api/tasks/boards/[boardId]
 * Update a board
 */
export async function PATCH(request: NextRequest, { params }: { params: Promise<{ boardId: string }> }) {
  const { boardId } = await params;
  try {
    const supabase = await createClient();
    const {
      data: { user },
      error: authError,
    } = await supabase.auth.getUser();

    if (authError || !user) {
      return NextResponse.json({ error: "Unauthorized" }, { status: 401 });
    }

    const body = await request.json();
    const { name, description, color, isDefault, isArchived } = body;

    // If setting as default, unset other default boards
    if (isDefault) {
      await db.update(staffTaskBoards).set({ isDefault: false }).where(eq(staffTaskBoards.isDefault, true));
    }

    const [board] = await db
      .update(staffTaskBoards)
      .set({
        name,
        description,
        color,
        isDefault,
        isArchived,
        updatedAt: new Date(),
      })
      .where(eq(staffTaskBoards.id, boardId))
      .returning();

    if (!board) {
      return NextResponse.json({ error: "Board not found" }, { status: 404 });
    }

    return NextResponse.json({ board });
  } catch (error) {
    console.error("Error updating board:", error);
    return NextResponse.json({ error: "Failed to update board" }, { status: 500 });
  }
}

/**
 * DELETE /api/tasks/boards/[boardId]
 * Delete a board (soft delete by archiving)
 */
export async function DELETE(request: NextRequest, { params }: { params: Promise<{ boardId: string }> }) {
  const { boardId } = await params;
  try {
    const supabase = await createClient();
    const {
      data: { user },
      error: authError,
    } = await supabase.auth.getUser();

    if (authError || !user) {
      return NextResponse.json({ error: "Unauthorized" }, { status: 401 });
    }

    const [board] = await db
      .update(staffTaskBoards)
      .set({
        isArchived: true,
        updatedAt: new Date(),
      })
      .where(eq(staffTaskBoards.id, boardId))
      .returning();

    if (!board) {
      return NextResponse.json({ error: "Board not found" }, { status: 404 });
    }

    return NextResponse.json({ success: true });
  } catch (error) {
    console.error("Error deleting board:", error);
    return NextResponse.json({ error: "Failed to delete board" }, { status: 500 });
  }
}
