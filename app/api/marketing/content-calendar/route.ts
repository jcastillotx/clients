import { NextRequest, NextResponse } from "next/server";
import { createClient } from "@/lib/supabase/server";
import { z } from "zod";
import { createContentSchema } from "@/lib/validations/marketing";

/**
 * GET /api/marketing/content-calendar
 * 
 * Fetch all content calendar items
 */
export async function GET(req: NextRequest) {
  const supabase = await createClient();

  const {
    data: { user },
  } = await supabase.auth.getUser();

  if (!user) {
    return NextResponse.json({ error: "Unauthorized" }, { status: 401 });
  }

  try {
    // Get user's client and role info
    const { data: userData } = await supabase
      .from("users")
      .select("client_id, is_super_admin")
      .eq("id", user.id)
      .single();

    const { data: roleRows } = await supabase
      .from("user_roles")
      .select("role:roles(name)")
      .eq("user_id", user.id);

    const roleNames = new Set<string>();
    for (const row of roleRows || []) {
      const roleName = String((row as any)?.role?.name || (row as any)?.role?.[0]?.name || "").toLowerCase();
      if (roleName) roleNames.add(roleName);
    }

    const isAdmin = Boolean(userData?.is_super_admin) || roleNames.has("admin") || roleNames.has("super_admin");
    const isAccountManager = roleNames.has("account_manager");
    const isStaff = isAdmin || isAccountManager || roleNames.has("staff");

    if (!isStaff) {
      return NextResponse.json({ error: "Forbidden" }, { status: 403 });
    }

    // Staff can optionally filter by client_id
    const clientIdFilter = req.nextUrl.searchParams.get("client_id");

    let query = supabase
      .from("content_calendar_items")
      .select(`
        *,
        client:clients(id, company_name),
        creator:users!content_calendar_items_created_by_fkey(id, name)
      `)
      .order("scheduled_for", { ascending: true });

    if (clientIdFilter) {
      query = query.eq("client_id", clientIdFilter);
    } else if (!isAdmin) {
      query = query.eq("client_id", userData!.client_id!);
    }

    const { data, error } = await query;

    if (error) throw error;

    return NextResponse.json(data);
  } catch (error) {
    console.error("Error fetching content calendar:", error);
    return NextResponse.json({ error: "Failed to fetch content calendar" }, { status: 500 });
  }
}

/**
 * POST /api/marketing/content-calendar
 * 
 * Create a new content calendar item
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
    
    // Validate input
    const validatedData = createContentSchema.parse(body);

    // Get user's client_id
    const { data: userData } = await supabase.from("users").select("client_id").eq("id", user.id).single();

    if (!userData?.client_id) {
      return NextResponse.json({ error: "User not associated with a client" }, { status: 400 });
    }

    const { data, error } = await supabase
      .from("content_calendar_items")
      .insert({
        client_id: userData.client_id,
        title: validatedData.title,
        content: validatedData.content,
        content_type: validatedData.content_type,
        platform: validatedData.platform,
        status: validatedData.status,
        scheduled_for: validatedData.scheduled_for || null,
        created_by: user.id,
      })
      .select()
      .single();

    if (error) throw error;

    return NextResponse.json(data, { status: 201 });
  } catch (error) {
    if (error instanceof z.ZodError) {
      return NextResponse.json({ error: "Validation error", details: error.errors }, { status: 400 });
    }
    console.error("Error creating content:", error);
    return NextResponse.json({ error: "Failed to create content" }, { status: 500 });
  }
}
