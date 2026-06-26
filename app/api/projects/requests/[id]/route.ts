import { NextRequest } from "next/server";
import { eq, sql } from "drizzle-orm";
import {
  apiForbidden,
  apiInternalError,
  apiNotFound,
  apiSuccess,
  apiUnauthorized,
  apiValidationError,
} from "@/lib/api/response";

import { z } from "zod";
import { db } from "@/lib/db";
import { projectBudgets, projects, type Currency } from "@/lib/db/schema/projects";
import { staffTaskBoards, staffTaskColumns } from "@/lib/db/schema/staff-tasks";
import { createClient } from "@/lib/supabase/server";
import { updateProjectRequestSchema } from "@/lib/validations/project-request";

type UserAccess = {
  clientId: string | null;
  isAdmin: boolean;
  isStaff: boolean;
};

type ProjectCustomFields = Record<string, unknown> & {
  type?: string;
  executiveSummary?: string;
  desiredOutcome?: string | null;
  budgetRange?: string | null;
  requestedStartDate?: string | null;
  requestedLaunchDate?: string | null;
  review?: Record<string, unknown>;
  clientDecision?: string;
  projectId?: string;
  convertedProjectId?: string;
  projectConvertedAt?: string;
  taskBoardId?: string;
};

type ExistingProjectRequest = {
  id: string;
  client_id: string;
  title: string;
  description: string | null;
  priority: "low" | "medium" | "high" | "urgent" | null;
  status: string | null;
  due_date: string | null;
  assigned_to: string | null;
  custom_fields: ProjectCustomFields | null;
};

type ProjectMetadata = NonNullable<typeof projects.$inferSelect.metadata>;

const normalizeDate = (value?: string | null) => {
  if (!value) {
    return null;
  }
  const parsed = new Date(value);
  if (Number.isNaN(parsed.getTime())) {
    return null;
  }
  return parsed.toISOString();
};

const parseDate = (value?: string | null) => {
  const normalized = normalizeDate(value);
  return normalized ? new Date(normalized) : null;
};

const normalizeCurrency = (value: unknown): Currency => {
  const currency = String(value || "USD").toUpperCase();
  return ["USD", "EUR", "GBP", "CAD", "AUD"].includes(currency) ? (currency as Currency) : "USD";
};

const numberString = (value: unknown) => {
  if (value === null || value === undefined || value === "") {
    return null;
  }
  const parsed = Number(value);
  return Number.isFinite(parsed) ? parsed.toFixed(2) : null;
};

const asProjectMetadata = (value: unknown): ProjectMetadata => {
  return value && typeof value === "object" && !Array.isArray(value) ? (value as ProjectMetadata) : {};
};

async function createDefaultTaskBoard(projectId: string, projectName: string, userId: string) {
  const [board] = await db
    .insert(staffTaskBoards)
    .values({
      name: `${projectName} Board`,
      description: "Default task board created when the project request was approved.",
      createdBy: userId,
      isDefault: false,
      color: "#5b5a88",
      columnOrder: [],
    })
    .returning();

  const columns = await db
    .insert(staffTaskColumns)
    .values([
      { boardId: board.id, name: "To Do", position: 0, color: "#94a3b8", icon: "list" },
      { boardId: board.id, name: "In Progress", position: 1, color: "#3b82f6", icon: "loader" },
      { boardId: board.id, name: "Review", position: 2, color: "#f59e0b", icon: "eye" },
      { boardId: board.id, name: "Done", position: 3, color: "#10b981", icon: "check", isDoneColumn: true },
    ])
    .returning({ id: staffTaskColumns.id });

  await db
    .update(staffTaskBoards)
    .set({ columnOrder: columns.map((column) => column.id) })
    .where(eq(staffTaskBoards.id, board.id));

  const [project] = await db
    .select({ metadata: projects.metadata })
    .from(projects)
    .where(eq(projects.id, projectId))
    .limit(1);

  await db
    .update(projects)
    .set({
      metadata: {
        ...asProjectMetadata(project?.metadata),
        taskBoardId: board.id,
      },
      updatedAt: new Date(),
    })
    .where(eq(projects.id, projectId));

  return board.id;
}

async function ensureApprovedProject(
  requestRow: ExistingProjectRequest,
  customFields: ProjectCustomFields,
  userId: string,
) {
  const existingLinkedProjectId =
    typeof customFields.projectId === "string"
      ? customFields.projectId
      : typeof customFields.convertedProjectId === "string"
        ? customFields.convertedProjectId
        : null;

  const [linkedProject] = await db
    .select({
      id: projects.id,
      name: projects.name,
      metadata: projects.metadata,
    })
    .from(projects)
    .where(
      existingLinkedProjectId
        ? eq(projects.id, existingLinkedProjectId)
        : sql`${projects.metadata}->>'sourceProjectRequestId' = ${requestRow.id}`,
    )
    .limit(1);

  if (linkedProject) {
    const taskBoardId = linkedProject.metadata?.taskBoardId
      ? linkedProject.metadata.taskBoardId
      : await createDefaultTaskBoard(linkedProject.id, linkedProject.name, userId);

    return {
      projectId: linkedProject.id,
      taskBoardId,
      convertedAt: customFields.projectConvertedAt || new Date().toISOString(),
    };
  }

  const review = (customFields.review || {}) as Record<string, unknown>;
  const startDate = parseDate(
    typeof review.estimatedStartDate === "string" ? review.estimatedStartDate : customFields.requestedStartDate,
  );
  const endDate = parseDate(
    typeof review.estimatedEndDate === "string"
      ? review.estimatedEndDate
      : customFields.requestedLaunchDate || requestRow.due_date,
  );
  const estimateAmount = numberString(review.estimateAmount);
  const estimatedHours = numberString(review.estimatedHours);
  const currency = normalizeCurrency(review.estimateCurrency);
  const convertedAt = new Date().toISOString();

  const [project] = await db
    .insert(projects)
    .values({
      clientId: requestRow.client_id,
      name: requestRow.title,
      description:
        typeof review.responseSummary === "string"
          ? review.responseSummary
          : requestRow.description || customFields.executiveSummary || null,
      status: "planning",
      startDate,
      endDate,
      estimatedHours,
      budgetAmount: estimateAmount,
      currency,
      projectManagerId: requestRow.assigned_to,
      metadata: {
        source: "project_request",
        sourceProjectRequestId: requestRow.id,
        projectRequestId: requestRow.id,
        projectConvertedAt: convertedAt,
        priority: requestRow.priority === "urgent" ? "critical" : requestRow.priority ?? undefined,
        reviewStatus: typeof review.status === "string" ? review.status : undefined,
      },
    })
    .returning();

  if (estimateAmount) {
    await db.insert(projectBudgets).values({
      projectId: project.id,
      category: "other",
      allocatedAmount: estimateAmount,
      currency,
      notes: "Initial budget created from the approved project request estimate.",
    });
  }

  const taskBoardId = await createDefaultTaskBoard(project.id, project.name, userId);

  return {
    projectId: project.id,
    taskBoardId,
    convertedAt,
  };
}

async function resolveAccess(supabase: Awaited<ReturnType<typeof createClient>>, user: { id: string; user_metadata?: Record<string, unknown> }) {
  const [{ data: dbUser }, { data: roleRows }] = await Promise.all([
    supabase.from("users").select("id, client_id, is_super_admin").eq("id", user.id).maybeSingle(),
    supabase.from("user_roles").select("role:roles(name)").eq("user_id", user.id),
  ]);

  const metadataRole = String(user.user_metadata?.role || user.user_metadata?.app_role || "").toLowerCase();
  const roleNames = (roleRows || []).map((row: unknown) => {
    const roleRow = row as { role?: { name?: string } | Array<{ name?: string }> };
    if (Array.isArray(roleRow.role)) {
      return String(roleRow.role[0]?.name || "").toLowerCase();
    }
    return String(roleRow.role?.name || "").toLowerCase();
  });

  const isAdmin = Boolean(
    dbUser?.is_super_admin ||
      user.user_metadata?.is_super_admin === true ||
      metadataRole === "admin" ||
      metadataRole === "super_admin" ||
      roleNames.includes("admin") ||
      roleNames.includes("super_admin"),
  );

  const isStaff = Boolean(
    isAdmin ||
      metadataRole === "staff" ||
      metadataRole === "account_manager" ||
      roleNames.includes("staff") ||
      roleNames.includes("account_manager"),
  );

  return {
    clientId: dbUser?.client_id || null,
    isAdmin,
    isStaff,
  } satisfies UserAccess;
}

async function getProjectRequest(supabase: Awaited<ReturnType<typeof createClient>>, id: string) {
  return supabase
    .from("requests")
    .select(
      `
      *,
      client:clients(id, company_name),
      creator:users!requests_created_by_fkey(id, name, email, avatar),
      assigned_user:users!requests_assigned_to_fkey(id, name, email, avatar)
    `,
    )
    .eq("id", id)
    .contains("custom_fields", { type: "project" })
    .single();
}

/**
 * GET /api/projects/requests/[id]
 *
 * Returns a project request with attachments and related summary metrics.
 */
export async function GET(request: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;
  try {
    const supabase = await createClient();
    const {
      data: { user },
      error: authError,
    } = await supabase.auth.getUser();

    if (authError || !user) {
      return apiUnauthorized(request);
    }

    const access = await resolveAccess(supabase, user);
    const { data: requestRow, error } = await getProjectRequest(supabase, id);

    if (error || !requestRow) {
      return apiNotFound(request, "Project request not found");
    }

    if (!access.isAdmin && access.clientId !== requestRow.client_id) {
      return apiForbidden(request);
    }

    const [documentsResult, tasksCountResult, meetingsCountResult, feedbackCountResult] = await Promise.all([
      supabase
        .from("documents")
        .select("id, name, file_name, file_size, mime_type, storage_url, storage_path, created_at, uploaded_by")
        .eq("request_id", id)
        .is("deleted_at", null)
        .order("created_at", { ascending: false }),
      supabase.from("staff_tasks").select("id", { count: "exact", head: true }).eq("request_id", id),
      supabase.from("meetings").select("id", { count: "exact", head: true }).eq("request_id", id),
      supabase.from("request_comments").select("id", { count: "exact", head: true }).eq("request_id", id),
    ]);

    const payload = {
      ...requestRow,
      attachments: documentsResult.data || [],
      metrics: {
        tasksCount: tasksCountResult.count || 0,
        meetingsCount: meetingsCountResult.count || 0,
        feedbackCount: feedbackCountResult.count || 0,
      },
    };

    return apiSuccess(request, payload, {
      extra: {
        access: {
          ...access,
          canReview: access.isStaff,
        },
      },
    });
  } catch (error) {
    console.error("Error fetching project request detail:", error);
    return apiInternalError(request, "Failed to fetch project request");
  }
}

/**
 * PATCH /api/projects/requests/[id]
 *
 * Updates project request details, review metadata and estimate response.
 */
export async function PATCH(request: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;
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
    const validated = updateProjectRequestSchema.parse(body);

    const access = await resolveAccess(supabase, user);
    const { data: existingRequest, error: existingError } = await supabase
      .from("requests")
      .select("id, client_id, title, description, priority, status, due_date, assigned_to, custom_fields")
      .eq("id", id)
      .contains("custom_fields", { type: "project" })
      .single<ExistingProjectRequest>();

    if (existingError || !existingRequest) {
      return apiNotFound(request, "Project request not found");
    }

    if (!access.isAdmin && access.clientId !== existingRequest.client_id) {
      return apiForbidden(request);
    }

    const updatePayload: Record<string, unknown> = {};
    const currentCustomFields = ((existingRequest.custom_fields || {}) as ProjectCustomFields) || {};
    const updatedCustomFields: ProjectCustomFields = { ...currentCustomFields, type: "project" };

    if (validated.title !== undefined) {
      updatePayload.title = validated.title;
    }
    if (validated.description !== undefined) {
      updatePayload.description = validated.description;
    }
    if (validated.priority !== undefined) {
      updatePayload.priority = validated.priority;
    }
    if (validated.status !== undefined) {
      updatePayload.status = validated.status;
    }
    if (validated.dueDate !== undefined) {
      updatePayload.due_date = normalizeDate(validated.dueDate);
    }

    if (validated.executiveSummary !== undefined) {
      updatedCustomFields.executiveSummary = validated.executiveSummary;
    }
    if (validated.desiredOutcome !== undefined) {
      updatedCustomFields.desiredOutcome = validated.desiredOutcome;
    }
    if (validated.budgetRange !== undefined) {
      updatedCustomFields.budgetRange = validated.budgetRange;
    }
    if (validated.requestedStartDate !== undefined) {
      updatedCustomFields.requestedStartDate = normalizeDate(validated.requestedStartDate);
    }
    if (validated.requestedLaunchDate !== undefined) {
      updatedCustomFields.requestedLaunchDate = normalizeDate(validated.requestedLaunchDate);
    }

    if (validated.assignedTo !== undefined) {
      if (!access.isStaff) {
        return apiForbidden(request, "Only staff can change assignment");
      }
      updatePayload.assigned_to = validated.assignedTo;
    }

    if (validated.review) {
      if (!access.isStaff) {
        return apiForbidden(request, "Only staff can submit a project estimate");
      }
      const currentReview = (currentCustomFields.review as Record<string, unknown>) || {};
      updatedCustomFields.review = {
        ...currentReview,
        ...validated.review,
        estimatedStartDate:
          validated.review.estimatedStartDate !== undefined
            ? normalizeDate(validated.review.estimatedStartDate)
            : currentReview.estimatedStartDate,
        estimatedEndDate:
          validated.review.estimatedEndDate !== undefined
            ? normalizeDate(validated.review.estimatedEndDate)
            : currentReview.estimatedEndDate,
      };

      if (!validated.status && validated.review.status) {
        if (validated.review.status === "estimated") {
          updatePayload.status = "awaiting_approval";
        }
        if (validated.review.status === "approved") {
          updatePayload.status = "approved";
        }
        if (validated.review.status === "declined") {
          updatePayload.status = "rejected";
        }
      }
    }

    if (validated.clientDecision) {
      updatedCustomFields.clientDecision = validated.clientDecision;
      if (!validated.status) {
        if (validated.clientDecision === "approved") {
          updatePayload.status = "approved";
        } else if (validated.clientDecision === "declined") {
          updatePayload.status = "rejected";
        } else if (validated.clientDecision === "needs_changes") {
          updatePayload.status = "in_progress";
        }
      }
    }

    if (validated.metadata) {
      Object.assign(updatedCustomFields, validated.metadata);
    }

    if (updatePayload.status === "approved" || (!updatePayload.status && existingRequest.status === "approved")) {
      const projectLink = await ensureApprovedProject(existingRequest, updatedCustomFields, user.id);
      updatedCustomFields.projectId = projectLink.projectId;
      updatedCustomFields.convertedProjectId = projectLink.projectId;
      updatedCustomFields.projectConvertedAt = projectLink.convertedAt;
      updatedCustomFields.taskBoardId = projectLink.taskBoardId;
    }

    updatePayload.custom_fields = updatedCustomFields;

    const { data, error } = await supabase
      .from("requests")
      .update(updatePayload)
      .eq("id", id)
      .select(
        `
        *,
        client:clients(id, company_name),
        creator:users!requests_created_by_fkey(id, name, email, avatar),
        assigned_user:users!requests_assigned_to_fkey(id, name, email, avatar)
      `,
      )
      .single();

    if (error) {
      return apiInternalError(request, error.message);
    }

    return apiSuccess(request, data);
  } catch (error) {
    if (error instanceof z.ZodError) {
      return apiValidationError(request, error);
    }
    console.error("Error updating project request:", error);
    return apiInternalError(request, "Failed to update project request");
  }
}

export async function DELETE(request: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;
  const supabase = await createClient();

  const {
    data: { user },
  } = await supabase.auth.getUser();
  if (!user) return apiUnauthorized(request);

  const { isAdmin } = await resolveAccess(supabase, user);
  if (!isAdmin) return apiForbidden(request);

  const { error } = await supabase
    .from("requests")
    .update({ deleted_at: new Date().toISOString() })
    .eq("id", id);

  if (error) {
    console.error("[DELETE /api/projects/requests/:id]", error);
    return apiInternalError(request, "Failed to delete project request");
  }

  return apiSuccess(request, { deleted: true }, { extra: { success: true } });
}
