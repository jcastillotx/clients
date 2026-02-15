import { NextRequest, NextResponse } from "next/server";
import { createClient } from "@/lib/supabase/server";

/**
 * GET /api/ads/campaigns
 * 
 * Fetch all ad campaigns
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
      .from("ad_campaigns")
      .select(`
        *,
        account:ad_accounts(id, platform, account_name, currency)
      `)
      .order("created_at", { ascending: false });

    if (error) throw error;

    return NextResponse.json(data);
  } catch (error) {
    console.error("Error fetching ad campaigns:", error);
    return NextResponse.json({ error: "Failed to fetch ad campaigns" }, { status: 500 });
  }
}

/**
 * POST /api/ads/campaigns
 * 
 * Create a new ad campaign
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
      .from("ad_campaigns")
      .insert({
        ad_account_id: body.ad_account_id,
        name: body.name,
        objective: body.objective,
        status: body.status || "draft",
        budget: body.budget,
        budget_type: body.budget_type || "daily",
        start_date: body.start_date,
        end_date: body.end_date,
        metadata: body.metadata,
      })
      .select()
      .single();

    if (error) throw error;

    return NextResponse.json(data, { status: 201 });
  } catch (error) {
    console.error("Error creating ad campaign:", error);
    return NextResponse.json({ error: "Failed to create ad campaign" }, { status: 500 });
  }
}
