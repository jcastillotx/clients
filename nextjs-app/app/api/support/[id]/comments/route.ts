import { createClient } from "@/lib/supabase/server";
import { createTicketCommentSchema } from "@/lib/validations/support-ticket";
import { NextRequest, NextResponse } from "next/server";
import { z } from "zod";

/**
 * GET /api/support/[id]/comments
 *
 * Fetch all comments for a support ticket
 */
export async function GET(req: NextRequest, { params }: { params: { id: string } }) {
  const supabase = createClient();

  // Check authentication
  const {
    data: { user },
  } = await supabase.auth.getUser();

  if (!user) {
    return NextResponse.json({ error: "Unauthorized" }, { status: 401 });
  }

  const { data, error } = await supabase
    .from("support_ticket_comments")
    .select(
      `
      *,
      user:users(id, name, email, avatar)
    `,
    )
    .eq("support_ticket_id", params.id)
    .order("created_at", { ascending: true });

  if (error) {
    console.error("Error fetching comments:", error);
    return NextResponse.json({ error: error.message }, { status: 500 });
  }

  return NextResponse.json(data);
}

/**
 * POST /api/support/[id]/comments
 *
 * Add a comment to a support ticket
 */
export async function POST(req: NextRequest, { params }: { params: { id: string } }) {
  const supabase = createClient();

  // Check authentication
  const {
    data: { user },
  } = await supabase.auth.getUser();

  if (!user) {
    return NextResponse.json({ error: "Unauthorized" }, { status: 401 });
  }

  // Parse and validate request body
  const body = await req.json();

  try {
    const validatedData = createTicketCommentSchema.parse(body);

    // Verify ticket exists and user has access
    const { data: ticket, error: ticketError } = await supabase
      .from("support_tickets")
      .select("id, first_response_at, status")
      .eq("id", params.id)
      .single();

    if (ticketError || !ticket) {
      return NextResponse.json({ error: "Ticket not found" }, { status: 404 });
    }

    // Create comment
    const { data, error } = await supabase
      .from("support_ticket_comments")
      .insert({
        support_ticket_id: params.id,
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
      return NextResponse.json({ error: error.message }, { status: 500 });
    }

    // Update ticket's first_response_at if this is the first response from staff
    if (!ticket.first_response_at && !validatedData.isInternal) {
      await supabase
        .from("support_tickets")
        .update({
          first_response_at: new Date().toISOString(),
        })
        .eq("id", params.id);
    }

    // If ticket was waiting_on_client, move it back to in_progress
    if (ticket.status === "waiting_on_client" && user.user_metadata?.role === "staff") {
      await supabase
        .from("support_tickets")
        .update({
          status: "in_progress",
          sla_paused: false,
        })
        .eq("id", params.id);
    }

    return NextResponse.json(data, { status: 201 });
  } catch (error) {
    if (error instanceof z.ZodError) {
      return NextResponse.json({ error: "Validation error", details: error.errors }, { status: 400 });
    }

    console.error("Unexpected error:", error);
    return NextResponse.json({ error: "Internal server error" }, { status: 500 });
  }
}
