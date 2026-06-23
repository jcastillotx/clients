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
import { projectRequestFeedbackSchema } from "@/lib/validations/project-request";

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
  };
}

const parseFeedback = (content: string) => {
  const match = content.match(/^\[rating:(\d)\]\s*/i);
  if (!match) {
    return {
      rating: null as number | null,
      message: content,
    };
  }

  const parsedRating = Number(match[1]);
  return {
    rating: Number.isFinite(parsedRating) ? parsedRating : null,
    message: content.replace(/^\[rating:(\d)\]\s*/i, "").trim(),
  };
};

/**
 * GET /api/projects/requests/[id]/feedback
 *
 * Returns feedback/comments for project request.
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
    const { data: requestRow, error: requestError } = await supabase
      .from("requests")
      .select("id, client_id")
      .eq("id", id)
      .contains("custom_fields", { type: "project" })
      .single();

    if (requestError || !requestRow) {
      return apiNotFound(request, "Project request not found");
    }

    if (!access.isAdmin && access.clientId !== requestRow.client_id) {
      return apiForbidden(request);
    }

    const { data, error } = await supabase
      .from("request_comments")
      .select(
        `
        id,
        content,
        created_at,
        updated_at,
        user:users(id, name, avatar)
      `,
      )
      .eq("request_id", id)
      .order("created_at", { ascending: true });

    if (error) {
      return apiInternalError(request, error.message);
    }

    const payload = (data || []).map((comment) => {
      const parsed = parseFeedback(comment.content || "");
      const userRelation = comment.user as { id: string; name: string; avatar?: string | null } | Array<{ id: string; name: string; avatar?: string | null }>;
      const normalizedUser = Array.isArray(userRelation) ? userRelation[0] : userRelation;
      return {
        id: comment.id,
        createdAt: comment.created_at,
        updatedAt: comment.updated_at,
        rating: parsed.rating,
        message: parsed.message,
        user: normalizedUser || null,
      };
    });

    return apiSuccess(request, payload);
  } catch (error) {
    console.error("Error fetching project feedback:", error);
    return apiInternalError(request, "Failed to fetch project feedback");
  }
}

/**
 * POST /api/projects/requests/[id]/feedback
 *
 * Adds feedback/comment (optionally with rating) to project request.
 */
export async function POST(request: NextRequest, { params }: { params: Promise<{ id: string }> }) {
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
    const { data: requestRow, error: requestError } = await supabase
      .from("requests")
      .select("id, client_id")
      .eq("id", id)
      .contains("custom_fields", { type: "project" })
      .single();

    if (requestError || !requestRow) {
      return apiNotFound(request, "Project request not found");
    }

    if (!access.isAdmin && access.clientId !== requestRow.client_id) {
      return apiForbidden(request);
    }

    const body = await request.json();
    const validated = projectRequestFeedbackSchema.parse(body);
    const content = validated.rating ? `[rating:${validated.rating}] ${validated.message}` : validated.message;

    const { data, error } = await supabase
      .from("request_comments")
      .insert({
        request_id: id,
        user_id: user.id,
        content,
      })
      .select(
        `
        id,
        content,
        created_at,
        updated_at,
        user:users(id, name, avatar)
      `,
      )
      .single();

    if (error) {
      return apiInternalError(request, error.message);
    }

    const parsed = parseFeedback(data.content || "");
    const userRelation = data.user as { id: string; name: string; avatar?: string | null } | Array<{ id: string; name: string; avatar?: string | null }>;
    const normalizedUser = Array.isArray(userRelation) ? userRelation[0] : userRelation;

    return apiSuccess(
      request,
      {
        id: data.id,
        createdAt: data.created_at,
        updatedAt: data.updated_at,
        rating: parsed.rating,
        message: parsed.message,
        user: normalizedUser || null,
      },
      { status: 201 },
    );
  } catch (error) {
    if (error instanceof z.ZodError) {
      return apiValidationError(request, error);
    }
    console.error("Error creating project feedback:", error);
    return apiInternalError(request, "Failed to submit feedback");
  }
}
