import { createClient } from "@/lib/supabase/server";
import { updateMeetingSchema } from "@/lib/validations/meeting";
import { NextRequest } from "next/server";
import {
  apiInternalError,
  apiNotFound,
  apiSuccess,
  apiUnauthorized,
  apiValidationError,
} from "@/lib/api/response";
import { z } from "zod";

interface RouteParams {
  params: Promise<{
    id: string;
  }>;
}

/**
 * GET /api/meetings/[id]
 */
export async function GET(req: NextRequest, { params }: RouteParams) {
  const { id } = await params;
  const supabase = await createClient();

  const {
    data: { user },
  } = await supabase.auth.getUser();

  if (!user) {
    return apiUnauthorized(req);
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
    .eq("id", id)
    .single();

  if (error) {
    if (error.code === "PGRST116") {
      return apiNotFound(req, "Meeting not found");
    }
    console.error("Error fetching meeting:", error);
    return apiInternalError(req, error.message);
  }

  return apiSuccess(req, meeting, { extra: { meeting } });
}

/**
 * PATCH /api/meetings/[id]
 */
export async function PATCH(req: NextRequest, { params }: RouteParams) {
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
    const validatedData = updateMeetingSchema.parse(body);

    const updateData: Record<string, unknown> = {};

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

    const { data: meeting, error: updateError } = await supabase
      .from("meetings")
      .update(updateData)
      .eq("id", id)
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
        return apiNotFound(req, "Meeting not found");
      }
      console.error("Error updating meeting:", updateError);
      return apiInternalError(req, updateError.message);
    }

    return apiSuccess(req, meeting, { extra: { meeting } });
  } catch (error) {
    if (error instanceof z.ZodError) {
      return apiValidationError(req, error);
    }

    console.error("Unexpected error:", error);
    return apiInternalError(req, "Internal server error");
  }
}

/**
 * DELETE /api/meetings/[id]
 */
export async function DELETE(req: NextRequest, { params }: RouteParams) {
  const { id } = await params;
  const supabase = await createClient();

  const {
    data: { user },
  } = await supabase.auth.getUser();

  if (!user) {
    return apiUnauthorized(req);
  }

  const { error } = await supabase
    .from("meetings")
    .update({ deleted_at: new Date().toISOString() })
    .eq("id", id);

  if (error) {
    if (error.code === "PGRST116") {
      return apiNotFound(req, "Meeting not found");
    }
    console.error("Error deleting meeting:", error);
    return apiInternalError(req, error.message);
  }

  return apiSuccess(req, { deleted: true });
}
