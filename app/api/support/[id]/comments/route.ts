import { createClient } from "@/lib/supabase/server";
import {
  apiInternalError,
  apiNotFound,
  apiSuccess,
  apiUnauthorized,
  apiValidationError,
} from "@/lib/api/response";
import { createTicketCommentSchema } from "@/lib/validations/support-ticket";
import { NextRequest } from "next/server";
import { z } from "zod";

/**
 * GET /api/support/[id]/comments
 */
export async function GET(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;
  const supabase = await createClient();

  const {
    data: { user },
  } = await supabase.auth.getUser();

  if (!user) {
    return apiUnauthorized(req);
  }

  const { data, error } = await supabase
    .from("support_ticket_comments")
    .select(
      `
      *,
      user:users(id, name, email, avatar)
    `,
    )
    .eq("support_ticket_id", id)
    .order("created_at", { ascending: true });

  if (error) {
    console.error("Error fetching comments:", error);
    return apiInternalError(req, error.message);
  }

  return apiSuccess(req, data ?? [], { extra: { comments: data ?? [] } });
}

/**
 * POST /api/support/[id]/comments
 */
export async function POST(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;
  const supabase = await createClient();

  const {
    data: { user },
  } = await supabase.auth.getUser();

  if (!user) {
    return apiUnauthorized(req);
  }

  const body = await req.json();

  try {
    const validatedData = createTicketCommentSchema.parse(body);

    const { data: ticket, error: ticketError } = await supabase
      .from("support_tickets")
      .select("id, first_response_at, status")
      .eq("id", id)
      .single();

    if (ticketError || !ticket) {
      return apiNotFound(req, "Ticket not found");
    }

    const { data, error } = await supabase
      .from("support_ticket_comments")
      .insert({
        support_ticket_id: id,
        user_id: user.id,
        comment: validatedData.comment,
        is_internal: validatedData.isInternal,
        attachments: validatedData.attachments || null,
      })
      .select(
        `
        *,
        user:users(id, name, email, avatar)
      `,
      )
      .single();

    if (error) {
      console.error("Error creating comment:", error);
      return apiInternalError(req, error.message);
    }

    if (!ticket.first_response_at && !validatedData.isInternal) {
      await supabase
        .from("support_tickets")
        .update({
          first_response_at: new Date().toISOString(),
        })
        .eq("id", id);
    }

    if (ticket.status === "waiting_on_client" && user.user_metadata?.role === "staff") {
      await supabase
        .from("support_tickets")
        .update({
          status: "in_progress",
          sla_paused: false,
        })
        .eq("id", id);
    }

    return apiSuccess(req, data, { status: 201, extra: { comment: data } });
  } catch (error) {
    if (error instanceof z.ZodError) {
      return apiValidationError(req, error);
    }

    console.error("Unexpected error:", error);
    return apiInternalError(req, "Internal server error");
  }
}
