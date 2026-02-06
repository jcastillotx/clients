import { createClient } from "@/lib/supabase/server";
import { createMeetingNoteSchema } from "@/lib/validations/meeting";
import { NextRequest, NextResponse } from "next/server";
import { z } from "zod";

interface RouteParams {
  params: Promise<{
    id: string;
  }>;
}

/**
 * GET /api/meetings/[id]/notes
 *
 * Fetch all notes for a meeting
 */
export async function GET(req: NextRequest, { params }: RouteParams) {
  const { id } = await params;
  const supabase = createClient();

  // Check authentication
  const {
    data: { user },
  } = await supabase.auth.getUser();

  if (!user) {
    return NextResponse.json({ error: "Unauthorized" }, { status: 401 });
  }

  const { data: notes, error } = await supabase
    .from("meeting_notes")
    .select("*, creator:users(id, name, avatar)")
    .eq("meeting_id", id)
    .order("order_index", { ascending: true });

  if (error) {
    console.error("Error fetching meeting notes:", error);
    return NextResponse.json({ error: error.message }, { status: 500 });
  }

  return NextResponse.json(notes);
}

/**
 * POST /api/meetings/[id]/notes
 *
 * Create a new meeting note
 */
export async function POST(req: NextRequest, { params }: RouteParams) {
  const { id } = await params;
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
    const validatedData = createMeetingNoteSchema.parse({
      ...body,
      meetingId: id,
    });

    // Create note
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
      return NextResponse.json({ error: createError.message }, { status: 500 });
    }

    return NextResponse.json(note, { status: 201 });
  } catch (error) {
    if (error instanceof z.ZodError) {
      return NextResponse.json({ error: "Validation error", details: error.errors }, { status: 400 });
    }

    console.error("Unexpected error:", error);
    return NextResponse.json({ error: "Internal server error" }, { status: 500 });
  }
}

/**
 * PUT /api/meetings/[id]/notes
 *
 * Bulk update meeting notes (save all at once)
 */
export async function PUT(req: NextRequest, { params }: RouteParams) {
  const { id } = await params;
  const supabase = createClient();

  // Check authentication
  const {
    data: { user },
  } = await supabase.auth.getUser();

  if (!user) {
    return NextResponse.json({ error: "Unauthorized" }, { status: 401 });
  }

  // Parse request body
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
      return NextResponse.json({ error: "Notes must be an array" }, { status: 400 });
    }

    // Delete existing notes
    await supabase.from("meeting_notes").delete().eq("meeting_id", id);

    // Insert new notes
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
        return NextResponse.json({ error: insertError.message }, { status: 500 });
      }

      return NextResponse.json(insertedNotes);
    }

    return NextResponse.json([]);
  } catch (error) {
    console.error("Unexpected error:", error);
    return NextResponse.json({ error: "Internal server error" }, { status: 500 });
  }
}
