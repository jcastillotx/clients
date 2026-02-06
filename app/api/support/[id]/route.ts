import { createClient } from "@/lib/supabase/server";
import { updateSupportTicketSchema } from "@/lib/validations/support-ticket";
import { NextRequest, NextResponse } from "next/server";
import { z } from "zod";

/**
 * GET /api/support/[id]
 *
 * Fetch a specific support ticket
 */
export async function GET(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;
  const supabase = await createClient();

  // Check authentication
  const {
    data: { user },
  } = await supabase.auth.getUser();

  if (!user) {
    return NextResponse.json({ error: "Unauthorized" }, { status: 401 });
  }

  const { data, error } = await supabase
    .from("support_tickets")
    .select(
      `
      *,
      client:clients(id, company_name, email),
      creator:users!support_tickets_created_by_fkey(id, name, email, avatar),
      assigned_user:users!support_tickets_assigned_to_fkey(id, name, email, avatar)
    `,
    )
    .eq("id", id)
    .single();

  if (error) {
    console.error("Error fetching ticket:", error);
    return NextResponse.json({ error: error.message }, { status: 500 });
  }

  if (!data) {
    return NextResponse.json({ error: "Ticket not found" }, { status: 404 });
  }

  return NextResponse.json(data);
}

/**
 * PUT /api/support/[id]
 *
 * Update a support ticket
 */
export async function PUT(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;
  const supabase = await createClient();

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
    const validatedData = updateSupportTicketSchema.parse(body);

    // Prepare update data
    const updateData: any = {
      ...validatedData,
      updated_at: new Date().toISOString(),
    };

    // Handle status changes
    if (validatedData.status) {
      // Set first_response_at if transitioning to in_progress for the first time
      if (validatedData.status === "in_progress") {
        const { data: ticket } = await supabase
          .from("support_tickets")
          .select("first_response_at")
          .eq("id", id)
          .single();

        if (ticket && !ticket.first_response_at) {
          updateData.first_response_at = new Date().toISOString();
        }
      }

      // Set resolved_at when marking as resolved
      if (validatedData.status === "resolved") {
        updateData.resolved_at = new Date().toISOString();
      }

      // Set closed_at when marking as closed
      if (validatedData.status === "closed") {
        updateData.closed_at = new Date().toISOString();
      }

      // Handle SLA pausing for waiting_on_client status
      if (validatedData.status === "waiting_on_client") {
        updateData.sla_paused = true;
      }
    }

    // Update ticket
    const { data, error } = await supabase
      .from("support_tickets")
      .update(updateData)
      .eq("id", id)
      .select(
        `
        *,
        client:clients(id, company_name, email),
        creator:users!support_tickets_created_by_fkey(id, name, email, avatar),
        assigned_user:users!support_tickets_assigned_to_fkey(id, name, email, avatar)
      `,
      )
      .single();

    if (error) {
      console.error("Error updating ticket:", error);
      return NextResponse.json({ error: error.message }, { status: 500 });
    }

    return NextResponse.json(data);
  } catch (error) {
    if (error instanceof z.ZodError) {
      return NextResponse.json({ error: "Validation error", details: error.errors }, { status: 400 });
    }

    console.error("Unexpected error:", error);
    return NextResponse.json({ error: "Internal server error" }, { status: 500 });
  }
}

/**
 * DELETE /api/support/[id]
 *
 * Soft delete a support ticket
 */
export async function DELETE(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;
  const supabase = await createClient();

  // Check authentication
  const {
    data: { user },
  } = await supabase.auth.getUser();

  if (!user) {
    return NextResponse.json({ error: "Unauthorized" }, { status: 401 });
  }

  // Soft delete (set deleted_at timestamp)
  const { error } = await supabase
    .from("support_tickets")
    .update({ deleted_at: new Date().toISOString() })
    .eq("id", id);

  if (error) {
    console.error("Error deleting ticket:", error);
    return NextResponse.json({ error: error.message }, { status: 500 });
  }

  return NextResponse.json({ success: true });
}
