import { z } from "zod";
import { checkDateOrder, cleanTextSchema } from "./fields";

/**
 * Ad Campaign Creation Schema
 */
export const createAdCampaignSchema = z.object({
  ad_account_id: cleanTextSchema.uuid("Invalid ad account ID"),
  name: cleanTextSchema.min(1, "Campaign name is required").max(255),
  objective: cleanTextSchema.min(1, "Objective is required"),
  status: z.enum(["draft", "active", "paused", "completed"]).default("draft"),
  budget: z.number().finite().positive("Budget must be positive").optional().nullable(),
  budget_type: z.enum(["daily", "lifetime"]).default("daily"),
  start_date: cleanTextSchema.datetime().optional().nullable(),
  end_date: cleanTextSchema.datetime().optional().nullable(),
  metadata: z.object({
    targeting_description: cleanTextSchema.max(100000).optional(),
    bid_strategy: cleanTextSchema.max(100000).optional(),
    optimization_goal: cleanTextSchema.max(100000).optional(),
  }).optional(),
  campaign_id: cleanTextSchema.max(100000).optional(), // Platform-specific ID (auto-generated if not provided)
}).superRefine((data, context) => checkDateOrder(data.start_date, data.end_date, "end_date", context));

export type CreateAdCampaignInput = z.infer<typeof createAdCampaignSchema>;
