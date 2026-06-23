import { createClient } from "@/lib/supabase/server";
import { createMeetingSchema } from "@/lib/validations/meeting";
import { NextRequest } from "next/server";
import {
  apiInternalError,
  apiSuccess,
  apiUnauthorized,
  apiValidationError,
} from "@/lib/api/response";
import { z } from "zod";

/**
 * GET /api/meetings
 */
export async function GET(req: NextRequest) {
  const supabase = await createClient();

  const {
    data: { user },
  } = await supabase.auth.getUser();

  if (!user) {
    return apiUnauthorized(req);
  }

  const searchParams = req.nextUrl.searchParams;
  const search = searchParams.get("search");
  const status = searchParams.get("status");
  const meetingType = searchParams.get("meetingType");
  const startDate = searchParams.get("startDate");
  const endDate = searchParams.get("endDate");
  const sortBy = searchParams.get("sortBy") || "scheduled_at";
  const sortOrder = searchParams.get("sortOrder") === "asc" ? "asc" : "desc";

  let query = supabase
    .from("meetings")
    .select(
      `
      *,
      client:clients(id, company_name),
      creator:users!meetings_created_by_fkey(id, name, email, avatar),
      request:requests(id, title)
    `,
    )
    .order(sortBy, { ascending: sortOrder === "asc" });

  if (search) {
    query = query.or(`title.ilike.%${search}%,description.ilike.%${search}%`);
  }

  if (status) {
    query = query.eq("status", status);
  }

  if (meetingType) {
    query = query.eq("meeting_type", meetingType);
  }

  if (startDate) {
    query = query.gte("scheduled_at", startDate);
  }

  if (endDate) {
    query = query.lte("scheduled_at", endDate);
  }

  const { data, error } = await query;

  if (error) {
    console.error("Error fetching meetings:", error);
    return apiInternalError(req, error.message);
  }

  const meetings = data ?? [];
  return apiSuccess(req, meetings, { extra: { meetings } });
}

/**
 * POST /api/meetings
 */
export async function POST(req: NextRequest) {
  const supabase = await createClient();

  const {
    data: { user },
  } = await supabase.auth.getUser();

  if (!user) {
    return apiUnauthorized(req);
  }

  const body = await req.json();

  try {
    const validatedData = createMeetingSchema.parse(body);

    const { data: meeting, error: createError } = await supabase
      .from("meetings")
      .insert({
        client_id: validatedData.clientId,
        request_id: validatedData.requestId,
        title: validatedData.title,
        description: validatedData.description,
        meeting_type: validatedData.meetingType,
        scheduled_at: validatedData.scheduledAt,
        duration_minutes: validatedData.durationMinutes,
        location: validatedData.location,
        meeting_url: validatedData.meetingUrl,
        attendees: validatedData.attendees,
        agenda: validatedData.agenda,
        created_by: user.id,
        status: "scheduled",
      })
      .select(
        `
        *,
        client:clients(id, company_name),
        creator:users!meetings_created_by_fkey(id, name, email, avatar),
        request:requests(id, title)
      `,
      )
      .single();

    if (createError) {
      console.error("Error creating meeting:", createError);
      return apiInternalError(req, createError.message);
    }

    if (validatedData.attendees && validatedData.attendees.length > 0) {
      const attendeeRecords = validatedData.attendees
        .filter((attendee) => attendee.userId)
        .map((attendee) => ({
          meeting_id: meeting.id,
          user_id: attendee.userId!,
          status: "invited",
        }));

      if (attendeeRecords.length > 0) {
        const { error: attendeeError } = await supabase.from("meeting_attendees").insert(attendeeRecords);

        if (attendeeError) {
          console.error("Error creating attendee records:", attendeeError);
        }
      }
    }

    return apiSuccess(req, meeting, { status: 201, extra: { meeting } });
  } catch (error) {
    if (error instanceof z.ZodError) {
      return apiValidationError(req, error);
    }

    console.error("Unexpected error:", error);
    return apiInternalError(req, "Internal server error");
  }
}
