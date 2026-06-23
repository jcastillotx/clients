import { NextRequest } from "next/server";
import { createClient } from "@/lib/supabase/server";
import {
  apiInternalError,
  apiNotFound,
  apiSuccess,
  apiUnauthorized,
} from "@/lib/api/response";
import { db } from "@/lib/db";
import { staffTaskBoards } from "@/lib/db/schema/staff-tasks";
import { eq } from "drizzle-orm";

/**
 * GET /api/tasks/boards/[boardId]
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
      return apiUnauthorized(request);
    }

    const board = await db.query.staffTaskBoards.findFirst({
      where: eq(staffTaskBoards.id, boardId),
      with: {
        columns: {
          orderBy: (columns, { asc }) => [asc(columns.position)],
          with: {
            tasks: {
              orderBy: (tasks, { asc }) => [asc(tasks.position)],
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
                  orderBy: (checklists, { asc }) => [asc(checklists.position)],
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
      return apiNotFound(request, "Board not found");
    }

    return apiSuccess(request, board, { extra: { board } });
  } catch (error) {
    console.error("Error fetching board:", error);
    return apiInternalError(request, "Failed to fetch board");
  }
}

/**
 * PATCH /api/tasks/boards/[boardId]
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
      return apiUnauthorized(request);
    }

    const body = await request.json();
    const { name, description, color, isDefault, isArchived } = body;

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
      return apiNotFound(request, "Board not found");
    }

    return apiSuccess(request, board, { extra: { board } });
  } catch (error) {
    console.error("Error updating board:", error);
    return apiInternalError(request, "Failed to update board");
  }
}

/**
 * DELETE /api/tasks/boards/[boardId]
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
      return apiUnauthorized(request);
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
      return apiNotFound(request, "Board not found");
    }

    return apiSuccess(request, { deleted: true });
  } catch (error) {
    console.error("Error deleting board:", error);
    return apiInternalError(request, "Failed to delete board");
  }
}
