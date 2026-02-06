import { createClient } from "@/lib/supabase/server";
import { NextRequest, NextResponse } from "next/server";

/**
 * GET /api/proposals
 *
 * Fetch all proposals for the authenticated user's client
 */
export async function GET(req: NextRequest) {
  const supabase = await createClient();

  const {
    data: { user },
  } = await supabase.auth.getUser();

  if (!user) {
    return NextResponse.json({ error: "Unauthorized" }, { status: 401 });
  }

  const searchParams = req.nextUrl.searchParams;
  const search = searchParams.get("search");
  const status = searchParams.get("status");
  const sortBy = searchParams.get("sortBy") || "created_at";
  const sortOrder = searchParams.get("sortOrder") === "asc" ? "asc" : "desc";

  let query = supabase
    .from("proposals")
    .select(
      `
      *,
      client:clients(id, company_name),
      creator:users!proposals_created_by_fkey(id, name)
    `,
    )
    .order(sortBy, { ascending: sortOrder === "asc" });

  if (search) {
    query = query.or(`title.ilike.%${search}%,description.ilike.%${search}%`);
  }

  if (status && status !== "all") {
    query = query.eq("status", status);
  }

  const { data, error } = await query;

  if (error) {
    console.error("Error fetching proposals:", error);
    return NextResponse.json({ error: error.message }, { status: 500 });
  }

  return NextResponse.json(data);
}

/**
 * POST /api/proposals
 *
 * Create a new proposal
 */
export async function POST(req: NextRequest) {
  const supabase = await createClient();

  const {
    data: { user },
  } = await supabase.auth.getUser();

  if (!user) {
    return NextResponse.json({ error: "Unauthorized" }, { status: 401 });
  }

  try {
    const body = await req.json();

    const { data, error } = await supabase
      .from("proposals")
      .insert({
        client_id: body.clientId,
        title: body.title,
        description: body.description || null,
        status: "draft",
        total_amount: body.totalAmount,
        currency: body.currency || "USD",
        valid_until: body.validUntil || null,
        terms: body.terms || null,
        line_items: body.lineItems,
        metadata: body.metadata || {},
        created_by: user.id,
      })
      .select()
      .single();

    if (error) throw error;

    return NextResponse.json(data, { status: 201 });
  } catch (error) {
    console.error("Error creating proposal:", error);
    return NextResponse.json(
      { error: error instanceof Error ? error.message : "Failed to create proposal" },
      { status: 500 },
    );
  }
}

/**
 * PATCH /api/proposals/:id
 *
 * Update a proposal
 */
export async function PATCH(req: NextRequest) {
  const supabase = await createClient();

  const {
    data: { user },
  } = await supabase.auth.getUser();

  if (!user) {
    return NextResponse.json({ error: "Unauthorized" }, { status: 401 });
  }

  try {
    const body = await req.json();
    const { id, ...updates } = body;

    const { data, error } = await supabase.from("proposals").update(updates).eq("id", id).select().single();

    if (error) throw error;

    return NextResponse.json(data);
  } catch (error) {
    console.error("Error updating proposal:", error);
    return NextResponse.json(
      { error: error instanceof Error ? error.message : "Failed to update proposal" },
      { status: 500 },
    );
  }
}

/**
 * DELETE /api/proposals/:id
 *
 * Delete a proposal
 */
export async function DELETE(req: NextRequest) {
  const supabase = await createClient();

  const {
    data: { user },
  } = await supabase.auth.getUser();

  if (!user) {
    return NextResponse.json({ error: "Unauthorized" }, { status: 401 });
  }

  try {
    const searchParams = req.nextUrl.searchParams;
    const id = searchParams.get("id");

    if (!id) {
      return NextResponse.json({ error: "Proposal ID is required" }, { status: 400 });
    }

    const { error } = await supabase.from("proposals").delete().eq("id", id);

    if (error) throw error;

    return NextResponse.json({ success: true });
  } catch (error) {
    console.error("Error deleting proposal:", error);
    return NextResponse.json(
      { error: error instanceof Error ? error.message : "Failed to delete proposal" },
      { status: 500 },
    );
  }
}
