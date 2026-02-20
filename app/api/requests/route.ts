import { createClient } from "@/lib/supabase/server";
import { createRequestSchema } from "@/lib/validations/request";
import { NextRequest, NextResponse } from "next/server";
import { z } from "zod";

/**
 * GET /api/requests
 *
 * Fetch all requests for the authenticated user's client
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
  const sortBy = searchParams.get("sortBy") || "created_at";
  const sortOrder = searchParams.get("sortOrder") === "asc" ? "asc" : "desc";

  // Build query
  let query = supabase
    .from("requests")
    .select("*, client:clients(company_name), assigned_user:users!requests_assigned_to_fkey(name, avatar)")
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
    const validatedData = createRequestSchema.parse(body);

    const { data: dbUser } = await supabase.from("users").select("id, client_id, is_super_admin").eq("id", user.id).maybeSingle();
    if (!dbUser) {
      return NextResponse.json({ error: "User profile not found" }, { status: 404 });
    }

    const metadataRole = String(user.user_metadata?.role || user.user_metadata?.app_role || "").toLowerCase();
    let isAdmin = Boolean(
      dbUser.is_super_admin ||
        user.user_metadata?.is_super_admin === true ||
        metadataRole === "admin" ||
        metadataRole === "super_admin",
    );

    if (!isAdmin) {
      const { data: roleRows } = await supabase
        .from("user_roles")
        .select("role:roles(name)")
        .eq("user_id", user.id);
      isAdmin = (roleRows || []).some((row: any) => {
        const roleName = String(row?.role?.name || row?.role?.[0]?.name || "").toLowerCase();
        return roleName === "admin" || roleName === "super_admin";
      });
    }

    const effectiveClientId = isAdmin ? validatedData.clientId : dbUser.client_id;
    if (!effectiveClientId) {
      return NextResponse.json({ error: "No client is assigned to this user" }, { status: 400 });
    }

    if (!isAdmin && validatedData.clientId !== dbUser.client_id) {
      return NextResponse.json({ error: "You can only create requests for your assigned client" }, { status: 403 });
    }

    const customFields = {
      ...(validatedData.customFields || {}),
      type: validatedData.type,
    };

    const insertPayload: Record<string, any> = {
      title: validatedData.title,
      description: validatedData.description,
      priority: validatedData.priority,
      status: validatedData.status || "pending",
      due_date: validatedData.dueDate || null,
      created_by: user.id,
      client_id: effectiveClientId,
      custom_fields: customFields,
    };

    if (validatedData.assignedTo && isAdmin) {
      insertPayload.assigned_to = validatedData.assignedTo;
    }

    // Create request
    const { data, error } = await supabase
      .from("requests")
      .insert(insertPayload)
      .select("*, client:clients(company_name), assigned_user:users!requests_assigned_to_fkey(name, avatar)")
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
