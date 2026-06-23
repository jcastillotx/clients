import { NextRequest } from "next/server";
import { z } from "zod";
import { createClient } from "@/lib/supabase/server";
import { createProjectRequestSchema } from "@/lib/validations/project-request";
import {
  apiError,
  apiInternalError,
  apiSuccess,
  apiUnauthorized,
  apiValidationError,
} from "@/lib/api/response";

type UserAccess = {
  clientId: string | null;
  isAdmin: boolean;
  isStaff: boolean;
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

    const access = await resolveAccess(supabase, user);
    const searchParams = request.nextUrl.searchParams;
    const status = searchParams.get("status");
    const search = searchParams.get("search");

    let query = supabase
      .from("requests")
      .select(
        `
        *,
        client:clients(id, company_name),
        creator:users!requests_created_by_fkey(id, name, email, avatar),
        assigned_user:users!requests_assigned_to_fkey(id, name, email, avatar)
      `,
      )
      .contains("custom_fields", { type: "project" })
      .order("created_at", { ascending: false });

    if (!access.isStaff && access.clientId) {
      query = query.eq("client_id", access.clientId);
    }

    if (status && status !== "all") {
      query = query.eq("status", status);
    }

    if (search) {
      query = query.or(`title.ilike.%${search}%,description.ilike.%${search}%`);
    }

    const { data, error } = await query;
    if (error) {
      return apiInternalError(request, error.message);
    }

    const rows = data || [];
    return apiSuccess(request, rows, { extra: { access } });
  } catch (error) {
    console.error("Error listing project requests:", error);
    return apiInternalError(request, "Failed to list project requests");
  }
}

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
    const validated = createProjectRequestSchema.parse(body);
    const access = await resolveAccess(supabase, user);

    const effectiveClientId = access.isStaff && validated.clientId ? validated.clientId : access.clientId;
    if (!effectiveClientId) {
      return apiError(request, {
        status: 400,
        code: "BAD_REQUEST",
        message: "No client is assigned to this user",
      });
    }

    const dueDate = normalizeDate(validated.dueDate) ?? normalizeDate(validated.requestedLaunchDate);

    const customFields = {
      ...(validated.metadata || {}),
      type: "project",
      executiveSummary: validated.executiveSummary,
      desiredOutcome: validated.desiredOutcome || null,
      budgetRange: validated.budgetRange || null,
      requestedStartDate: normalizeDate(validated.requestedStartDate),
      requestedLaunchDate: normalizeDate(validated.requestedLaunchDate),
      review: {
        status: "awaiting_review",
        estimateAmount: null,
        estimateCurrency: "USD",
        estimatedHours: null,
        estimatedStartDate: null,
        estimatedEndDate: null,
        responseSummary: null,
        reviewNotes: null,
      },
    };

    const { data, error } = await supabase
      .from("requests")
      .insert({
        client_id: effectiveClientId,
        title: validated.title,
        description: validated.description || null,
        priority: validated.priority,
        status: "pending",
        due_date: dueDate,
        created_by: user.id,
        custom_fields: customFields,
      })
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

    return apiSuccess(request, data, { status: 201 });
  } catch (error) {
    if (error instanceof z.ZodError) {
      return apiValidationError(request, error);
    }
    console.error("Error creating project request:", error);
    return apiInternalError(request, "Failed to create project request");
  }
}
