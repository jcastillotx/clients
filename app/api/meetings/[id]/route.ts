import { createClient } from "@/lib/supabase/server";
import { updateMeetingSchema } from "@/lib/validations/meeting";
import { NextRequest, NextResponse } from "next/server";
import { z } from "zod";

interface RouteParams {
  params: {
    id: string;
  };
}

/**
 * GET /api/meetings/[id]
 *
 * Fetch a single meeting by ID
 */
export async function GET(req: NextRequest, { params }: RouteParams) {
  const supabase = createClient();

  // Check authentication
  const {
    data: { user },
  } = await supabase.auth.getUser();

  if (!user) {
    return NextResponse.json({ error: "Unauthorized" }, { status: 401 });
  }

  const { data: meeting, error } = await supabase
    .from("meetings")
    .select(
      `
      *,
      client:clients(id, company_name),
      creator:users!meetings_created_by_fkey(id, name, email, avatar),
      request:requests(id, title),
      notes:meeting_notes(*, creator:users(id, name, avatar)),
      attendeeRecords:meeting_attendees(*, user:users(id, name, email, avatar))
    `,
    )
    .eq("id", params.id)
    .single();

  if (error) {
    if (error.code === "PGRST116") {
      return NextResponse.json({ error: "Meeting not found" }, { status: 404 });
    }
    console.error("Error fetching meeting:", error);
    return NextResponse.json({ error: error.message }, { status: 500 });
  }

  return NextResponse.json(meeting);
}

/**
 * PATCH /api/meetings/[id]
 *
 * Update a meeting
 */
export async function PATCH(req: NextRequest, { params }: RouteParams) {
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
    const validatedData = updateMeetingSchema.parse(body);

    // Build update object
    const updateData: Record<string, any> = {};

    if (validatedData.title !== undefined) updateData.title = validatedData.title;
    if (validatedData.description !== undefined) updateData.description = validatedData.description;
    if (validatedData.meetingType !== undefined) updateData.meeting_type = validatedData.meetingType;
    if (validatedData.status !== undefined) updateData.status = validatedData.status;
    if (validatedData.scheduledAt !== undefined) updateData.scheduled_at = validatedData.scheduledAt;
    if (validatedData.durationMinutes !== undefined) updateData.duration_minutes = validatedData.durationMinutes;
    if (validatedData.location !== undefined) updateData.location = validatedData.location;
    if (validatedData.meetingUrl !== undefined) updateData.meeting_url = validatedData.meetingUrl;
    if (validatedData.attendees !== undefined) updateData.attendees = validatedData.attendees;
    if (validatedData.agenda !== undefined) updateData.agenda = validatedData.agenda;
    if (validatedData.notes !== undefined) updateData.notes = validatedData.notes;
    if (validatedData.recordingUrl !== undefined) updateData.recording_url = validatedData.recordingUrl;
    if (validatedData.actionItems !== undefined) updateData.action_items = validatedData.actionItems;

    updateData.updated_at = new Date().toISOString();

    // Update meeting
    const { data: meeting, error: updateError } = await supabase
      .from("meetings")
      .update(updateData)
      .eq("id", params.id)
      .select(
        `
        *,
        client:clients(id, company_name),
        creator:users!meetings_created_by_fkey(id, name, email, avatar),
        request:requests(id, title)
      `,
      )
      .single();

    if (updateError) {
      if (updateError.code === "PGRST116") {
        return NextResponse.json({ error: "Meeting not found" }, { status: 404 });
      }
      console.error("Error updating meeting:", updateError);
      return NextResponse.json({ error: updateError.message }, { status: 500 });
    }

    return NextResponse.json(meeting);
  } catch (error) {
    if (error instanceof z.ZodError) {
      return NextResponse.json({ error: "Validation error", details: error.errors }, { status: 400 });
    }

    console.error("Unexpected error:", error);
    return NextResponse.json({ error: "Internal server error" }, { status: 500 });
  }
}

/**
 * DELETE /api/meetings/[id]
 *
 * Delete a meeting (soft delete)
 */
export async function DELETE(req: NextRequest, { params }: RouteParams) {
  const supabase = createClient();

  // Check authentication
  const {
    data: { user },
  } = await supabase.auth.getUser();

  if (!user) {
    return NextResponse.json({ error: "Unauthorized" }, { status: 401 });
  }

  // Soft delete
  const { error } = await supabase
    .from("meetings")
    .update({ deleted_at: new Date().toISOString() })
    .eq("id", params.id);

  if (error) {
    if (error.code === "PGRST116") {
      return NextResponse.json({ error: "Meeting not found" }, { status: 404 });
    }
    console.error("Error deleting meeting:", error);
    return NextResponse.json({ error: error.message }, { status: 500 });
  }

  return NextResponse.json({ success: true });
}
