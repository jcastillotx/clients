import {
  buildPaginationMeta,
  parsePaginationSearchParams,
} from "@/lib/api/pagination";
import { apiError, apiSuccess } from "@/lib/api/response";
import { createAdminClientIfAvailable, createClient } from "@/lib/supabase/server";
import { getAuthBaseUrl } from "@/lib/supabase/redirect-url";
import { createRequestSchema } from "@/lib/validations/request";
import { dispatchNotification } from "@/lib/notifications/service";
import { withPlatformNotificationEmails } from "@/lib/notifications/platform-email";
import { isAdminUser } from "@/lib/rbac/check";
import { isUserAssignableToClient } from "@/lib/users/assignable-users";
import { NextRequest } from "next/server";
import { z } from "zod";

const ALLOWED_SORT_COLUMNS = new Set([
  "created_at",
  "updated_at",
  "title",
  "status",
  "priority",
  "due_date",
]);

/**
 * GET /api/requests
 *
 * Fetch all requests for the authenticated user's client
 */
export async function GET(req: NextRequest) {
  const supabase = await createClient();

  const {
    data: { user },
  } = await supabase.auth.getUser();

  if (!user) {
    return apiError(req, {
      status: 401,
      code: "UNAUTHORIZED",
      message: "Unauthorized",
    });
  }

  const searchParams = req.nextUrl.searchParams;
  const search = searchParams.get("search");
  const status = searchParams.get("status");
  const sortBy = searchParams.get("sortBy") || "created_at";
  const sortOrder = searchParams.get("sortOrder") === "asc" ? "asc" : "desc";
  const pagination = parsePaginationSearchParams(searchParams);

  if (!ALLOWED_SORT_COLUMNS.has(sortBy)) {
    return apiError(req, {
      status: 400,
      code: "VALIDATION_ERROR",
      message: "Invalid sort column",
    });
  }

  let query = supabase
    .from("requests")
    .select(
      "*, client:clients(company_name), assigned_user:users!requests_assigned_to_fkey(name, avatar)",
      { count: "exact" },
    )
    .is("deleted_at", null)
    .order(sortBy, { ascending: sortOrder === "asc" })
    .range(
      pagination.offset,
      pagination.offset + pagination.limit - 1,
    );

  if (search) {
    query = query.textSearch("title", search);
  }

  if (status) {
    query = query.eq("status", status);
  }

  const { data, error, count } = await query;

  if (error) {
    console.error("[GET /api/requests] DB error:", error);
    return apiError(req, {
      status: 500,
      code: "INTERNAL_ERROR",
      message: "Failed to fetch requests",
    });
  }

  const rows = data ?? [];

  return apiSuccess(req, rows, {
    pagination: buildPaginationMeta(pagination, count, rows.length),
  });
}

/**
 * POST /api/requests
 *
 * Create a new request
 */
export async function POST(req: NextRequest) {
  const supabase = await createClient();

  const {
    data: { user },
  } = await supabase.auth.getUser();

  if (!user) {
    return apiError(req, {
      status: 401,
      code: "UNAUTHORIZED",
      message: "Unauthorized",
    });
  }

  const body = await req.json();

  try {
    const validatedData = createRequestSchema.parse(body);

    const [{ data: dbUser }, { data: roleRows }] = await Promise.all([
      supabase
        .from("users")
        .select("id, client_id, is_super_admin, name, email")
        .eq("id", user.id)
        .maybeSingle(),
      supabase.from("user_roles").select("role:roles(name)").eq("user_id", user.id),
    ]);

    if (!dbUser) {
      return apiError(req, {
        status: 404,
        code: "NOT_FOUND",
        message: "User profile not found",
      });
    }

    const isAdmin = isAdminUser(user, dbUser, roleRows);

    if (isAdmin && !validatedData.clientId) {
      return apiError(req, {
        status: 400,
        code: "VALIDATION_ERROR",
        message: "Please select a client",
      });
    }

    const effectiveClientId = isAdmin ? validatedData.clientId : dbUser.client_id;
    if (!effectiveClientId) {
      return apiError(req, {
        status: 400,
        code: "BAD_REQUEST",
        message: "No client is assigned to this user",
      });
    }

    if (!isAdmin && validatedData.clientId && validatedData.clientId !== dbUser.client_id) {
      return apiError(req, {
        status: 403,
        code: "FORBIDDEN",
        message: "Forbidden",
      });
    }

    if (validatedData.assignedTo && isAdmin) {
      const assignmentClient = createAdminClientIfAvailable() ?? supabase;
      const canAssignToRequest = await isUserAssignableToClient(
        assignmentClient,
        validatedData.assignedTo,
        effectiveClientId,
      );
      if (!canAssignToRequest) {
        return apiError(req, {
          status: 400,
          code: "BAD_REQUEST",
          message:
            "Assignee must belong to this request's client or be platform staff/admin",
        });
      }
    }

    const customFields = {
      ...(validatedData.customFields || {}),
      type: validatedData.type,
    };

    const insertPayload: Record<string, unknown> = {
      title: validatedData.title,
      description: validatedData.description,
      priority: validatedData.priority,
      status: isAdmin ? validatedData.status || "pending" : "pending",
      due_date: validatedData.dueDate || null,
      created_by: user.id,
      client_id: effectiveClientId,
      custom_fields: customFields,
    };

    if (validatedData.assignedTo && isAdmin) {
      insertPayload.assigned_to = validatedData.assignedTo;
    }

    const { data, error } = await supabase
      .from("requests")
      .insert(insertPayload)
      .select("*, client:clients(company_name), assigned_user:users!requests_assigned_to_fkey(name, avatar)")
      .single();

    if (error) {
      console.error("[POST /api/requests] DB error:", error);
      return apiError(req, {
        status: 500,
        code: "INTERNAL_ERROR",
        message: "Failed to create request",
      });
    }

    try {
      const base = getAuthBaseUrl();
      const row = data as {
        id: string;
        title: string;
        priority: string;
        client?: { company_name?: string } | { company_name?: string }[];
      };
      const companyName = Array.isArray(row.client)
        ? row.client[0]?.company_name
        : row.client?.company_name;

      await dispatchNotification({
        eventType: "service_request_created",
        clientId: effectiveClientId,
        subjectType: "request",
        subjectId: row.id,
        actorUserId: user.id,
        recipientUserIds: validatedData.assignedTo ? [validatedData.assignedTo] : undefined,
        extraEmails: isAdmin ? [] : await withPlatformNotificationEmails(),
        data: {
          request_title: row.title,
          request_priority: row.priority,
          request_url: `${base}/requests/${row.id}`,
          company_name: companyName ?? "",
          created_by_name: dbUser?.name || dbUser?.email || user.email || "",
        },
      });
    } catch (notifyErr) {
      console.error("[POST /api/requests] notification dispatch:", notifyErr);
    }

    return apiSuccess(req, data, { status: 201 });
  } catch (error) {
    if (error instanceof z.ZodError) {
      return apiError(req, {
        status: 400,
        code: "VALIDATION_ERROR",
        message: "Validation error",
        details: error.errors,
      });
    }

    console.error("[POST /api/requests] Unexpected error:", error);
    return apiError(req, {
      status: 500,
      code: "INTERNAL_ERROR",
      message: "Internal server error",
    });
  }
}
