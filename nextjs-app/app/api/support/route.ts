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
  const supabase = createClient();

  // Check authentication
  const {
    data: { user },
  } = await supabase.auth.getUser();

  if (!user) {
    return NextResponse.json({ error: "Unauthorized" }, { status: 401 });
  }

  // Get user's client_id
  const clientId = user.user_metadata?.client_id;
  if (!clientId) {
    return NextResponse.json({ error: "User not associated with a client" }, { status: 400 });
  }

  // Parse and validate request body
  const body = await req.json();

  try {
    const validatedData = createSupportTicketSchema.parse(body);

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
  const random = Math.random().toString(36).substring(2, 6).toUpperCase();

  return `${prefix}${date}-${random}`;
}
