import { NextRequest } from "next/server";
import { and, eq } from "drizzle-orm";
import { canAccessClient, resolveRouteAccess } from "@/lib/auth/route-access";
import { db } from "@/lib/db";
import {
  projectDeliverables,
  projects,
} from "@/lib/db/schema/projects";
import { createClient } from "@/lib/supabase/server";
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

    const deliverables = await db
      .select()
      .from(projectDeliverables)
      .where(eq(projectDeliverables.projectId, id))
      .orderBy(projectDeliverables.sortOrder);

    return apiSuccess(request, deliverables);
  } catch (error) {
    console.error("Error fetching deliverables:", error);
    return apiInternalError(request, "Failed to fetch deliverables");
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
    const { title, description, status, dueDate, sortOrder } = body;

    if (!title) {
      return apiError(request, {
        status: 400,
        code: "BAD_REQUEST",
        message: "Title is required",
      });
    }

    const [deliverable] = await db
      .insert(projectDeliverables)
      .values({
        projectId: id,
        title,
        description,
        status: status ?? "pending",
        dueDate: dueDate ? new Date(dueDate) : null,
        sortOrder: sortOrder ?? 0,
      })
      .returning();

    return apiSuccess(request, deliverable);
  } catch (error) {
    console.error("Error creating deliverable:", error);
    return apiInternalError(request, "Failed to create deliverable");
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
    const { deliverableId, ...updates } = body;

    if (!deliverableId) {
      return apiError(request, {
        status: 400,
        code: "BAD_REQUEST",
        message: "Deliverable ID is required",
      });
    }

    const [deliverable] = await db
      .update(projectDeliverables)
      .set({
        ...updates,
        dueDate: updates.dueDate ? new Date(updates.dueDate) : undefined,
        deliveredAt: updates.deliveredAt
          ? new Date(updates.deliveredAt)
          : undefined,
        updatedAt: new Date(),
      })
      .where(
        and(
          eq(projectDeliverables.id, deliverableId),
          eq(projectDeliverables.projectId, id),
        ),
      )
      .returning();

    return apiSuccess(request, deliverable);
  } catch (error) {
    console.error("Error updating deliverable:", error);
    return apiInternalError(request, "Failed to update deliverable");
  }
}

export async function DELETE(
  request: NextRequest,
  { params }: { params: Promise<{ id: string }> },
) {
  const { id } = await params;
  try {
    const guard = await requireProjectAccess(request, id);
    if ("error" in guard) {
      return guard.error;
    }

    const searchParams = request.nextUrl.searchParams;
    const deliverableId = searchParams.get("deliverableId");

    if (!deliverableId) {
      return apiError(request, {
        status: 400,
        code: "BAD_REQUEST",
        message: "Deliverable ID is required",
      });
    }

    await db
      .delete(projectDeliverables)
      .where(
        and(
          eq(projectDeliverables.id, deliverableId),
          eq(projectDeliverables.projectId, id),
        ),
      );

    return apiSuccess(
      request,
      { deleted: true },
      { extra: { success: true, message: "Deliverable deleted successfully" } },
    );
  } catch (error) {
    console.error("Error deleting deliverable:", error);
    return apiInternalError(request, "Failed to delete deliverable");
  }
}
