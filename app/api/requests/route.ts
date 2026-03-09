import { createClient } from "@/lib/supabase/server";
import { createRequestSchema } from "@/lib/validations/request";
import { isAdminUser } from "@/lib/rbac/check";
import { NextRequest, NextResponse } from "next/server";
import { z } from "zod";

const ALLOWED_SORT_COLUMNS = new Set([
  "created_at",
  "updated_at",
  "title",
  "status",
  "priority",
  "due_date",
]);

/**
 * GET /api/requests
 *
 * Fetch all requests for the authenticated user's client
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

  if (!ALLOWED_SORT_COLUMNS.has(sortBy)) {
    return NextResponse.json({ error: "Invalid sort column" }, { status: 400 });
  }

  let query = supabase
    .from("requests")
    .select("*, client:clients(company_name), assigned_user:users!requests_assigned_to_fkey(name, avatar)")
    .order(sortBy, { ascending: sortOrder === "asc" });

  if (search) {
    query = query.textSearch("title", search);
  }

  if (status) {
    query = query.eq("status", status);
  }

  const { data, error } = await query;

  if (error) {
    console.error("[GET /api/requests] DB error:", error);
    return NextResponse.json({ error: "Failed to fetch requests" }, { status: 500 });
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

  const {
    data: { user },
  } = await supabase.auth.getUser();

  if (!user) {
    return NextResponse.json({ error: "Unauthorized" }, { status: 401 });
  }

  const body = await req.json();

  try {
    const validatedData = createRequestSchema.parse(body);

    const [{ data: dbUser }, { data: roleRows }] = await Promise.all([
      supabase.from("users").select("id, client_id, is_super_admin").eq("id", user.id).maybeSingle(),
      supabase.from("user_roles").select("role:roles(name)").eq("user_id", user.id),
    ]);

    if (!dbUser) {
      return NextResponse.json({ error: "User profile not found" }, { status: 404 });
    }

    const isAdmin = isAdminUser(user, dbUser, roleRows);

    const effectiveClientId = isAdmin ? validatedData.clientId : dbUser.client_id;
    if (!effectiveClientId) {
      return NextResponse.json({ error: "No client is assigned to this user" }, { status: 400 });
    }

    if (!isAdmin && validatedData.clientId !== dbUser.client_id) {
      return NextResponse.json({ error: "Forbidden" }, { status: 403 });
    }

    const customFields = {
      ...(validatedData.customFields || {}),
      type: validatedData.type,
    };

    const insertPayload: Record<string, unknown> = {
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

    const { data, error } = await supabase
      .from("requests")
      .insert(insertPayload)
      .select("*, client:clients(company_name), assigned_user:users!requests_assigned_to_fkey(name, avatar)")
      .single();

    if (error) {
      console.error("[POST /api/requests] DB error:", error);
      return NextResponse.json({ error: "Failed to create request" }, { status: 500 });
    }

    return NextResponse.json(data, { status: 201 });
  } catch (error) {
    if (error instanceof z.ZodError) {
      return NextResponse.json({ error: "Validation error", details: error.errors }, { status: 400 });
    }

    console.error("[POST /api/requests] Unexpected error:", error);
    return NextResponse.json({ error: "Internal server error" }, { status: 500 });
  }
}
