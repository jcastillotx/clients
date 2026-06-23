import { createClient } from "@/lib/supabase/server";
import { createMeetingNoteSchema } from "@/lib/validations/meeting";
import { NextRequest } from "next/server";
import {
  apiError,
  apiInternalError,
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
 * GET /api/meetings/[id]/notes
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

  const { data: notes, error } = await supabase
    .from("meeting_notes")
    .select("*, creator:users(id, name, avatar)")
    .eq("meeting_id", id)
    .order("order_index", { ascending: true });

  if (error) {
    console.error("Error fetching meeting notes:", error);
    return apiInternalError(req, error.message);
  }

  const list = notes ?? [];
  return apiSuccess(req, list, { extra: { notes: list } });
}

/**
 * POST /api/meetings/[id]/notes
 */
export async function POST(req: NextRequest, { params }: RouteParams) {
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
    const validatedData = createMeetingNoteSchema.parse({
      ...body,
      meetingId: id,
    });

    const { data: note, error: createError } = await supabase
      .from("meeting_notes")
      .insert({
        meeting_id: validatedData.meetingId,
        section: validatedData.section,
        content: validatedData.content,
        order_index: validatedData.orderIndex || 0,
        created_by: user.id,
      })
      .select("*, creator:users(id, name, avatar)")
      .single();

    if (createError) {
      console.error("Error creating meeting note:", createError);
      return apiInternalError(req, createError.message);
    }

    return apiSuccess(req, note, { status: 201, extra: { note } });
  } catch (error) {
    if (error instanceof z.ZodError) {
      return apiValidationError(req, error);
    }

    console.error("Unexpected error:", error);
    return apiInternalError(req, "Internal server error");
  }
}

/**
 * PUT /api/meetings/[id]/notes
 */
export async function PUT(req: NextRequest, { params }: RouteParams) {
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
    const { notes } = body as {
      notes: Array<{
        id?: string;
        section: string;
        content: string;
        orderIndex?: number;
      }>;
    };

    if (!Array.isArray(notes)) {
      return apiError(req, {
        status: 400,
        code: "BAD_REQUEST",
        message: "Notes must be an array",
      });
    }

    await supabase.from("meeting_notes").delete().eq("meeting_id", id);

    if (notes.length > 0) {
      const notesToInsert = notes.map((note, index) => ({
        meeting_id: id,
        section: note.section,
        content: note.content,
        order_index: note.orderIndex ?? index,
        created_by: user.id,
      }));

      const { data: insertedNotes, error: insertError } = await supabase
        .from("meeting_notes")
        .insert(notesToInsert)
        .select("*, creator:users(id, name, avatar)");

      if (insertError) {
        console.error("Error inserting meeting notes:", insertError);
        return apiInternalError(req, insertError.message);
      }

      return apiSuccess(req, insertedNotes ?? [], { extra: { notes: insertedNotes ?? [] } });
    }

    return apiSuccess(req, [], { extra: { notes: [] } });
  } catch (error) {
    console.error("Unexpected error:", error);
    return apiInternalError(req, "Internal server error");
  }
}
