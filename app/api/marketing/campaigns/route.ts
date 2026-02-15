import { NextRequest, NextResponse } from "next/server";
import { createClient } from "@/lib/supabase/server";

/**
 * GET /api/marketing/campaigns
 * 
 * Fetch all marketing campaigns for the authenticated user's client
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
      .from("campaigns")
      .select(`
        *,
        client:clients(id, company_name),
        created_by_user:users!campaigns_created_by_fkey(id, name)
      `)
      .order("created_at", { ascending: false });

    if (error) throw error;

    return NextResponse.json(data);
  } catch (error) {
    console.error("Error fetching campaigns:", error);
    return NextResponse.json({ error: "Failed to fetch campaigns" }, { status: 500 });
  }
}

/**
 * POST /api/marketing/campaigns
 * 
 * Create a new marketing campaign
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
      .from("campaigns")
      .insert({
        client_id: userData.client_id,
        name: body.name,
        description: body.description,
        campaign_type: body.type,
        status: body.status || "draft",
        start_date: body.start_date,
        end_date: body.end_date,
        budget: body.budget,
        currency: body.currency || "USD",
        created_by: user.id,
        metadata: body.metadata,
      })
      .select()
      .single();

    if (error) throw error;

    return NextResponse.json(data, { status: 201 });
  } catch (error) {
    console.error("Error creating campaign:", error);
    return NextResponse.json({ error: "Failed to create campaign" }, { status: 500 });
  }
}
