import { NextRequest, NextResponse } from "next/server";
import { createClient } from "@/lib/supabase/server";
import { z } from "zod";
import { createAdCampaignSchema } from "@/lib/validations/ad-campaign";

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
    
    // Validate input
    const validatedData = createAdCampaignSchema.parse(body);

    // Generate platform-specific campaign ID (or use provided one)
    const campaignId = validatedData.campaign_id || `camp_${crypto.randomUUID()}`;

    // Map budget based on budget_type
    const dailyBudget = validatedData.budget_type === 'daily' ? validatedData.budget : null;
    const lifetimeBudget = validatedData.budget_type === 'lifetime' ? validatedData.budget : null;

    const { data, error } = await supabase
      .from("ad_campaigns")
      .insert({
        ad_account_id: validatedData.ad_account_id,
        campaign_id: campaignId,
        name: validatedData.name,
        objective: validatedData.objective,
        status: validatedData.status,
        daily_budget: dailyBudget,
        lifetime_budget: lifetimeBudget,
        start_date: validatedData.start_date || null,
        end_date: validatedData.end_date || null,
        metadata: validatedData.metadata || null,
      })
      .select()
      .single();

    if (error) throw error;

    return NextResponse.json(data, { status: 201 });
  } catch (error) {
    if (error instanceof z.ZodError) {
      return NextResponse.json({ error: "Validation error", details: error.errors }, { status: 400 });
    }
    console.error("Error creating ad campaign:", error);
    return NextResponse.json({ error: "Failed to create ad campaign" }, { status: 500 });
  }
}
