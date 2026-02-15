import { NextRequest, NextResponse } from "next/server";
import { createClient } from "@/lib/supabase/server";

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
    const { data, error } = await supabase
      .from("content_calendar_items")
      .select(`
        *,
        client:clients(id, company_name),
        creator:users!content_calendar_items_created_by_fkey(id, name)
      `)
      .order("scheduled_for", { ascending: true });

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

    // Get user's client_id
    const { data: userData } = await supabase.from("users").select("client_id").eq("id", user.id).single();

    if (!userData?.client_id) {
      return NextResponse.json({ error: "User not associated with a client" }, { status: 400 });
    }

    const { data, error } = await supabase
      .from("content_calendar_items")
      .insert({
        client_id: userData.client_id,
        title: body.title,
        content: body.content,
        content_type: body.content_type,
        platform: body.platform,
        status: body.status || "draft",
        scheduled_for: body.scheduled_for,
        created_by: user.id,
      })
      .select()
      .single();

    if (error) throw error;

    return NextResponse.json(data, { status: 201 });
  } catch (error) {
    console.error("Error creating content:", error);
    return NextResponse.json({ error: "Failed to create content" }, { status: 500 });
  }
}
