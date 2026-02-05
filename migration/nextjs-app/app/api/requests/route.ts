import { createClient } from "@/lib/supabase/server";
import { createRequestSchema } from "@/lib/validations/request";
import { NextRequest, NextResponse } from "next/server";

/**
 * GET /api/requests
 *
 * Fetch all requests for the authenticated user's client
 */
export async function GET(req: NextRequest) {
  const supabase = createClient();

  // Check authentication
  const {
    data: { user },
  } = await supabase.auth.getUser();

  if (!user) {
    return NextResponse.json({ error: "Unauthorized" }, { status: 401 });
  }

  // Get search params
  const searchParams = req.nextUrl.searchParams;
  const search = searchParams.get("search");
  const status = searchParams.get("status");
  const sortBy = searchParams.get("sortBy") || "created_at";
  const sortOrder = searchParams.get("sortOrder") === "asc" ? "asc" : "desc";

  // Build query
  let query = supabase
    .from("requests")
    .select("*, client:clients(company_name), assigned_user:users(name, avatar)")
    .order(sortBy, { ascending: sortOrder === "asc" });

  // Apply filters
  if (search) {
    query = query.textSearch("title", search);
  }

  if (status) {
    query = query.eq("status", status);
  }

  const { data, error } = await query;

  if (error) {
    console.error("Error fetching requests:", error);
    return NextResponse.json({ error: error.message }, { status: 500 });
  }

  return NextResponse.json(data);
}

/**
 * POST /api/requests
 *
 * Create a new request
 */
export async function POST(req: NextRequest) {
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
    const validatedData = createRequestSchema.parse(body);

    // Create request (RLS automatically filters by client_id)
    const { data, error } = await supabase
      .from("requests")
      .insert({
        ...validatedData,
        created_by: user.id,
        client_id: user.user_metadata.client_id,
        status: "pending",
      })
      .select("*, client:clients(company_name), assigned_user:users(name, avatar)")
      .single();

    if (error) {
      console.error("Error creating request:", error);
      return NextResponse.json({ error: error.message }, { status: 500 });
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
