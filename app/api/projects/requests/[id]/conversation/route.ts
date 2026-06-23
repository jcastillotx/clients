import { NextRequest } from "next/server";
import {
  apiForbidden,
  apiInternalError,
  apiNotFound,
  apiSuccess,
  apiUnauthorized,
} from "@/lib/api/response";

import { createClient } from "@/lib/supabase/server";

type ParticipantRole = "client" | "staff";

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

/**
 * GET /api/projects/requests/[id]/conversation
 *
 * Finds or creates a conversation linked to the request for real-time messaging.
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
      .select("id, title, client_id, created_by, assigned_to")
      .eq("id", id)
      .contains("custom_fields", { type: "project" })
      .single();

    if (requestError || !requestRow) {
      return apiNotFound(request, "Project request not found");
    }

    if (!access.isAdmin && access.clientId !== requestRow.client_id) {
      return apiForbidden(request);
    }

    const conversationLookup = await supabase
      .from("conversations")
      .select("id, title, context_type, context_id, client_id, is_closed, last_message_at")
      .eq("client_id", requestRow.client_id)
      .eq("context_type", "request")
      .eq("context_id", id)
      .limit(1)
      .maybeSingle();

    let conversation = conversationLookup.data;
    const conversationError = conversationLookup.error;

    if (conversationError) {
      return apiInternalError(request, conversationError.message);
    }

    if (!conversation) {
      const { data: createdConversation, error: createError } = await supabase
        .from("conversations")
        .insert({
          client_id: requestRow.client_id,
          title: `Project Request: ${requestRow.title}`,
          context_type: "request",
          context_id: id,
          last_message_at: new Date().toISOString(),
        })
        .select("id, title, context_type, context_id, client_id, is_closed, last_message_at")
        .single();

      if (createError || !createdConversation) {
        return apiInternalError(request, createError?.message || "Failed to create conversation");
      }
      conversation = createdConversation;
    }

    const participantIds = Array.from(
      new Set([user.id, requestRow.created_by, requestRow.assigned_to].filter((value): value is string => Boolean(value))),
    );

    const { data: existingParticipants, error: participantsError } = await supabase
      .from("conversation_participants")
      .select("user_id")
      .eq("conversation_id", conversation.id)
      .in("user_id", participantIds);

    if (participantsError) {
      return apiInternalError(request, participantsError.message);
    }

    const existingIds = new Set((existingParticipants || []).map((participant) => participant.user_id));
    const missingParticipantIds = participantIds.filter((participantId) => !existingIds.has(participantId));

    if (missingParticipantIds.length > 0) {
      const records = missingParticipantIds.map((participantId) => ({
        conversation_id: conversation.id,
        user_id: participantId,
        role: (participantId === requestRow.created_by ? "client" : "staff") as ParticipantRole,
      }));
      const { error: insertParticipantsError } = await supabase.from("conversation_participants").insert(records);
      if (insertParticipantsError) {
        return apiInternalError(request, insertParticipantsError.message);
      }
    }

    return apiSuccess(request, {
      id: conversation.id,
      title: conversation.title,
    });
  } catch (error) {
    console.error("Error getting project request conversation:", error);
    return apiInternalError(request, "Failed to resolve project conversation");
  }
}
