import { z } from "zod";

/**
 * Ad Campaign Creation Schema
 */
export const createAdCampaignSchema = z.object({
  ad_account_id: z.string().uuid("Invalid ad account ID"),
  name: z.string().min(1, "Campaign name is required").max(255),
  objective: z.string().min(1, "Objective is required"),
  status: z.enum(["draft", "active", "paused", "completed"]).default("draft"),
  budget: z.number().positive("Budget must be positive").optional().nullable(),
  budget_type: z.enum(["daily", "lifetime"]).default("daily"),
  start_date: z.string().datetime().optional().nullable(),
  end_date: z.string().datetime().optional().nullable(),
  metadata: z.object({
    targeting_description: z.string().optional(),
    bid_strategy: z.string().optional(),
    optimization_goal: z.string().optional(),
  }).optional(),
  campaign_id: z.string().optional(), // Platform-specific ID (auto-generated if not provided)
});

export type CreateAdCampaignInput = z.infer<typeof createAdCampaignSchema>;
