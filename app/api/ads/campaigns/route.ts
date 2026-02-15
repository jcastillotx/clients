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

    // Generate platform-specific campaign ID (or use provided one)
    const campaignId = body.campaign_id || `camp_${Date.now()}_${Math.random().toString(36).substring(7)}`;

    // Map budget based on budget_type
    const dailyBudget = body.budget_type === 'daily' ? body.budget : null;
    const lifetimeBudget = body.budget_type === 'lifetime' ? body.budget : null;

    const { data, error } = await supabase
      .from("ad_campaigns")
      .insert({
        ad_account_id: body.ad_account_id,
        campaign_id: campaignId,
        name: body.name,
        objective: body.objective,
        status: body.status || "draft",
        daily_budget: dailyBudget,
        lifetime_budget: lifetimeBudget,
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
