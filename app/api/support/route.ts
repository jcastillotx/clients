import { createClient } from "@/lib/supabase/server";
import { createSupportTicketSchema } from "@/lib/validations/support-ticket";
import { calculateSlaDueDates } from "@/lib/utils/sla";
import { NextRequest, NextResponse } from "next/server";
import { z } from "zod";

/**
 * GET /api/support
 *
 * Fetch all support tickets for the authenticated user's client
 */
export async function GET(req: NextRequest) {
  const supabase = await createClient();

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
  const priority = searchParams.get("priority");
  const category = searchParams.get("category");
  const sortBy = searchParams.get("sortBy") || "created_at";
  const sortOrder = searchParams.get("sortOrder") === "asc" ? "asc" : "desc";

  // Build query
  let query = supabase
    .from("support_tickets")
    .select(
      `
      *,
      client:clients(company_name),
      creator:users!support_tickets_created_by_fkey(name, avatar),
      assigned_user:users!support_tickets_assigned_to_fkey(name, avatar)
    `,
    )
    .is("deleted_at", null)
    .order(sortBy, { ascending: sortOrder === "asc" });

  // Apply filters
  if (search) {
    query = query.or(`subject.ilike.%${search}%,ticket_number.ilike.%${search}%`);
  }

  if (status) {
    query = query.eq("status", status);
  }

  if (priority) {
    query = query.eq("priority", priority);
  }

  if (category) {
    query = query.eq("category", category);
  }

  const { data, error } = await query;

  if (error) {
    console.error("Error fetching tickets:", error);
    return NextResponse.json({ error: error.message }, { status: 500 });
  }

  return NextResponse.json(data);
}

/**
 * POST /api/support
 *
 * Create a new support ticket
 */
export async function POST(req: NextRequest) {
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
    const validatedData = createSupportTicketSchema.parse(body);

    const { data: dbUser, error: dbUserError } = await supabase
      .from("users")
      .select("id, client_id")
      .eq("id", user.id)
      .maybeSingle();

    if (dbUserError) {
      console.error("Error loading user profile for support ticket creation:", dbUserError);
      return NextResponse.json({ error: "Failed to load user profile" }, { status: 500 });
    }

    const clientId = dbUser?.client_id;
    if (!clientId) {
      return NextResponse.json({ error: "User not associated with a client" }, { status: 400 });
    }

    // Generate ticket number
    const ticketNumber = await generateTicketNumber();

    // Calculate SLA due dates based on priority
    const now = new Date();
    const slaDates = calculateSlaDueDates(validatedData.priority, now);

    // Create ticket
    const { data, error } = await supabase
      .from("support_tickets")
      .insert({
        client_id: clientId,
        created_by: user.id,
        assigned_to: validatedData.assignedTo || null,
        ticket_number: ticketNumber,
        subject: validatedData.subject,
        description: validatedData.description,
        category: validatedData.category,
        priority: validatedData.priority,
        status: "open",
        metadata: validatedData.metadata,
        sla_response_due_at: slaDates.slaResponseDueAt.toISOString(),
        sla_resolution_due_at: slaDates.slaResolutionDueAt.toISOString(),
      })
      .select(
        `
        *,
        client:clients(company_name),
        creator:users!support_tickets_created_by_fkey(name, avatar),
        assigned_user:users!support_tickets_assigned_to_fkey(name, avatar)
      `,
      )
      .single();

    if (error) {
      console.error("Error creating ticket:", error);
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

/**
 * Generate a unique ticket number
 */
async function generateTicketNumber(): Promise<string> {
  const prefix = "TKT-";
  const date = new Date().toISOString().slice(0, 10).replace(/-/g, "");
  // Use crypto.randomUUID() for better uniqueness guarantee
  const uniquePart = crypto.randomUUID().substring(0, 8).toUpperCase();

  return `${prefix}${date}-${uniquePart}`;
}
