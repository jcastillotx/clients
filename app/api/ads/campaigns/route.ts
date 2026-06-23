import { NextRequest } from "next/server";
import {
  apiInternalError,
  apiSuccess,
  apiUnauthorized,
  apiValidationError,
} from "@/lib/api/response";
import { createClient } from "@/lib/supabase/server";
import { z } from "zod";
import { createAdCampaignSchema } from "@/lib/validations/ad-campaign";

export async function GET(request: NextRequest) {
  const supabase = await createClient();

  const {
    data: { user },
  } = await supabase.auth.getUser();

  if (!user) {
    return apiUnauthorized(request);
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

    return apiSuccess(request, data ?? []);
  } catch (error) {
    console.error("Error fetching ad campaigns:", error);
    return apiInternalError(request, "Failed to fetch ad campaigns");
  }
}

export async function POST(request: NextRequest) {
  const supabase = await createClient();

  const {
    data: { user },
  } = await supabase.auth.getUser();

  if (!user) {
    return apiUnauthorized(request);
  }

  try {
    const body = await request.json();
    const validatedData = createAdCampaignSchema.parse(body);

    const campaignId = validatedData.campaign_id || `camp_${crypto.randomUUID()}`;

    const dailyBudget = validatedData.budget_type === "daily" ? validatedData.budget : null;
    const lifetimeBudget = validatedData.budget_type === "lifetime" ? validatedData.budget : null;

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

    return apiSuccess(request, data, { status: 201 });
  } catch (error) {
    if (error instanceof z.ZodError) {
      return apiValidationError(request, error);
    }
    console.error("Error creating ad campaign:", error);
    return apiInternalError(request, "Failed to create ad campaign");
  }
}
