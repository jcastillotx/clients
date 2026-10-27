import { z } from "zod";
import { checkDateOrder, cleanTextSchema } from "./fields";
import { phoneSchema } from "./fields";

/**
 * Marketing Campaign Validation
 */
export const createCampaignSchema = z.object({
  name: cleanTextSchema.min(1, "Campaign name is required").max(255),
  description: cleanTextSchema.max(100000).optional(),
  type: z.enum(["email", "social", "content", "paid_ads", "seo", "multi_channel"]),
  status: z.enum(["draft", "scheduled", "active", "paused", "completed", "cancelled"]).default("draft"),
  budget: z.number().finite().positive().optional().nullable(),
  currency: z.enum(["USD", "EUR", "GBP", "CAD"]).default("USD"),
  start_date: cleanTextSchema.datetime().optional().nullable(),
  end_date: cleanTextSchema.datetime().optional().nullable(),
  metadata: z.object({
    target_audience: cleanTextSchema.max(100000).optional(),
    goals: cleanTextSchema.max(100000).optional(),
  }).optional(),
}).superRefine((data, context) => checkDateOrder(data.start_date, data.end_date, "end_date", context));

export type CreateCampaignInput = z.infer<typeof createCampaignSchema>;

/**
 * Lead Validation
 */
export const createLeadSchema = z.object({
  name: cleanTextSchema.min(1, "Name is required").max(255),
  email: cleanTextSchema.max(254).email("Valid email is required"),
  phone: phoneSchema.optional().nullable(),
  company: cleanTextSchema.max(255).optional().nullable(),
  source: z.enum(["website", "social", "email", "referral", "paid_ad", "organic", "event", "cold_outreach", "other"]),
  status: z.enum(["new", "contacted", "qualified", "proposal", "negotiation", "converted", "lost", "nurturing"]).default("new"),
  metadata: z.object({
    position: cleanTextSchema.max(100000).optional(),
    website: cleanTextSchema.url().regex(/^https?:\/\/[^\s\/@]+(?:[\/?#][^\s]*)?$/i, "Use an HTTP or HTTPS URL without login credentials.").optional().or(z.literal("")),
    industry: cleanTextSchema.max(100000).optional(),
    notes: cleanTextSchema.max(100000).optional(),
  }).optional(),
});

export type CreateLeadInput = z.infer<typeof createLeadSchema>;

/**
 * Content Calendar Validation
 */
export const createContentSchema = z.object({
  title: cleanTextSchema.min(1, "Title is required").max(255),
  content: cleanTextSchema.min(1, "Content is required"),
  content_type: z.enum(["post", "story", "reel", "video", "article", "blog", "tweet", "other"]),
  platform: z.enum(["facebook", "instagram", "linkedin", "twitter", "x", "tiktok", "pinterest", "youtube", "other"]),
  status: z.enum(["draft", "pending_approval", "approved", "needs_revision", "scheduled", "published", "failed"]).default("draft"),
  scheduled_for: cleanTextSchema.datetime().optional().nullable(),
});

export type CreateContentInput = z.infer<typeof createContentSchema>;
