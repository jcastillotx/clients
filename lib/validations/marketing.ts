import { z } from "zod";

/**
 * Marketing Campaign Validation
 */
export const createCampaignSchema = z.object({
  name: z.string().min(1, "Campaign name is required").max(255),
  description: z.string().optional(),
  type: z.enum(["email", "social", "content", "paid_ads", "seo", "multi_channel"]),
  status: z.enum(["draft", "scheduled", "active", "paused", "completed", "cancelled"]).default("draft"),
  budget: z.number().positive().optional().nullable(),
  currency: z.enum(["USD", "EUR", "GBP", "CAD"]).default("USD"),
  start_date: z.string().datetime().optional().nullable(),
  end_date: z.string().datetime().optional().nullable(),
  metadata: z.object({
    target_audience: z.string().optional(),
    goals: z.string().optional(),
  }).optional(),
});

export type CreateCampaignInput = z.infer<typeof createCampaignSchema>;

/**
 * Lead Validation
 */
export const createLeadSchema = z.object({
  name: z.string().min(1, "Name is required").max(255),
  email: z.string().email("Valid email is required"),
  phone: z.string().max(50).optional().nullable(),
  company: z.string().max(255).optional().nullable(),
  source: z.enum(["website", "social", "email", "referral", "paid_ad", "organic", "event", "cold_outreach", "other"]),
  status: z.enum(["new", "contacted", "qualified", "proposal", "negotiation", "converted", "lost", "nurturing"]).default("new"),
  metadata: z.object({
    position: z.string().optional(),
    website: z.string().url().optional().or(z.literal("")),
    industry: z.string().optional(),
    notes: z.string().optional(),
  }).optional(),
});

export type CreateLeadInput = z.infer<typeof createLeadSchema>;

/**
 * Content Calendar Validation
 */
export const createContentSchema = z.object({
  title: z.string().min(1, "Title is required").max(255),
  content: z.string().min(1, "Content is required"),
  content_type: z.enum(["post", "story", "reel", "video", "article", "blog", "tweet", "other"]),
  platform: z.enum(["facebook", "instagram", "linkedin", "twitter", "x", "tiktok", "pinterest", "youtube", "other"]),
  status: z.enum(["draft", "pending_approval", "approved", "needs_revision", "scheduled", "published", "failed"]).default("draft"),
  scheduled_for: z.string().datetime().optional().nullable(),
});

export type CreateContentInput = z.infer<typeof createContentSchema>;
