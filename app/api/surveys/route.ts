import { NextRequest } from "next/server";
import { z } from "zod";
import { createClient } from "@/lib/supabase/server";
import { createSurveySchema } from "@/lib/validations/survey";
import {
  apiError,
  apiForbidden,
  apiInternalError,
  apiSuccess,
  apiUnauthorized,
  apiValidationError,
} from "@/lib/api/response";

type UserAccess = {
  clientId: string | null;
  isAdmin: boolean;
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

  return {
    clientId: dbUser?.client_id || null,
    isAdmin,
  } satisfies UserAccess;
}

export async function GET(request: Request) {
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

    let query = supabase
      .from("surveys")
      .select("id, title, description, is_active, response_count, created_at, client:clients(company_name)")
      .order("created_at", { ascending: false });

    if (!access.isAdmin && access.clientId) {
      query = query.eq("client_id", access.clientId);
    }

    const { data, error } = await query;
    if (error) {
      return apiInternalError(request, error.message);
    }

    const rows = data || [];
    return apiSuccess(request, rows);
  } catch (error) {
    console.error("Error fetching surveys:", error);
    return apiInternalError(request, "Failed to fetch surveys");
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
    const validated = createSurveySchema.parse(body);
    const access = await resolveAccess(supabase, user);

    const effectiveClientId = access.isAdmin ? validated.clientId || null : access.clientId;
    if (!access.isAdmin && !effectiveClientId) {
      return apiError(request, {
        status: 400,
        code: "BAD_REQUEST",
        message: "No client is assigned to this user",
      });
    }

    if (!access.isAdmin && validated.clientId && validated.clientId !== access.clientId) {
      return apiForbidden(request);
    }

    const { data: survey, error: surveyError } = await supabase
      .from("surveys")
      .insert({
        client_id: effectiveClientId,
        title: validated.title,
        description: validated.description || null,
        is_active: validated.isActive,
        anonymous_allowed: validated.anonymousAllowed,
        created_by: user.id,
      })
      .select("id, title")
      .single();

    if (surveyError || !survey) {
      return apiInternalError(request, surveyError?.message || "Failed to create survey");
    }

    const questionRows = validated.questions.map((question, index) => ({
      survey_id: survey.id,
      type: question.type,
      prompt: question.prompt,
      options: question.options && question.options.length > 0 ? question.options : null,
      is_required: question.isRequired,
      sort_order: index,
    }));

    const { error: questionsError } = await supabase.from("survey_questions").insert(questionRows);
    if (questionsError) {
      return apiInternalError(request, questionsError.message);
    }

    return apiSuccess(request, survey, { status: 201 });
  } catch (error) {
    if (error instanceof z.ZodError) {
      return apiValidationError(request, error);
    }
    console.error("Error creating survey:", error);
    return apiInternalError(request, "Failed to create survey");
  }
}
