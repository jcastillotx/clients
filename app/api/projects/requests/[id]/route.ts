import { NextRequest } from "next/server";
import {
  apiForbidden,
  apiInternalError,
  apiNotFound,
  apiSuccess,
  apiUnauthorized,
  apiValidationError,
} from "@/lib/api/response";

import { z } from "zod";
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
};

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
      .select("id, client_id, custom_fields")
      .eq("id", id)
      .contains("custom_fields", { type: "project" })
      .single();

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
