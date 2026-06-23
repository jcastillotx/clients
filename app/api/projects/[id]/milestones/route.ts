import { NextRequest } from "next/server";
import { and, eq } from "drizzle-orm";
import { canAccessClient, resolveRouteAccess } from "@/lib/auth/route-access";
import { db } from "@/lib/db";
import {
  projectDeliverables,
  projectMilestones,
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

    const milestones = await db
      .select()
      .from(projectMilestones)
      .where(eq(projectMilestones.projectId, id))
      .orderBy(projectMilestones.sortOrder);

    const milestonesWithDeliverables = await Promise.all(
      milestones.map(async (milestone: (typeof milestones)[number]) => {
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

    return apiSuccess(request, milestonesWithDeliverables);
  } catch (error) {
    console.error("Error fetching milestones:", error);
    return apiInternalError(request, "Failed to fetch milestones");
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
    const { title, description, dueDate, sortOrder } = body;

    if (!title) {
      return apiError(request, {
        status: 400,
        code: "BAD_REQUEST",
        message: "Title is required",
      });
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

    return apiSuccess(request, milestone);
  } catch (error) {
    console.error("Error creating milestone:", error);
    return apiInternalError(request, "Failed to create milestone");
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
    const { milestoneId, ...updates } = body;

    if (!milestoneId) {
      return apiError(request, {
        status: 400,
        code: "BAD_REQUEST",
        message: "Milestone ID is required",
      });
    }

    const [milestone] = await db
      .update(projectMilestones)
      .set({
        ...updates,
        dueDate: updates.dueDate ? new Date(updates.dueDate) : undefined,
        completedAt: updates.completedAt
          ? new Date(updates.completedAt)
          : undefined,
        updatedAt: new Date(),
      })
      .where(
        and(
          eq(projectMilestones.id, milestoneId),
          eq(projectMilestones.projectId, id),
        ),
      )
      .returning();

    return apiSuccess(request, milestone);
  } catch (error) {
    console.error("Error updating milestone:", error);
    return apiInternalError(request, "Failed to update milestone");
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
    const milestoneId = searchParams.get("milestoneId");

    if (!milestoneId) {
      return apiError(request, {
        status: 400,
        code: "BAD_REQUEST",
        message: "Milestone ID is required",
      });
    }

    await db
      .delete(projectMilestones)
      .where(
        and(
          eq(projectMilestones.id, milestoneId),
          eq(projectMilestones.projectId, id),
        ),
      );

    return apiSuccess(
      request,
      { deleted: true },
      { extra: { success: true, message: "Milestone deleted successfully" } },
    );
  } catch (error) {
    console.error("Error deleting milestone:", error);
    return apiInternalError(request, "Failed to delete milestone");
  }
}
