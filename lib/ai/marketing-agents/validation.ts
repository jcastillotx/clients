import { z } from "zod";

const commonFields = {
  clientId: z.string().uuid(),
  instructions: z.string().trim().max(4000).default(""),
  createDrafts: z.boolean().default(true),
};

export const marketingAgentRunRequestSchema = z.discriminatedUnion(
  "workflowId",
  [
    z.object({
      ...commonFields,
      workflowId: z.literal("campaign_plan"),
      campaignName: z.string().trim().min(1).max(180),
      objective: z.string().trim().min(10).max(2000),
      targetAudience: z.string().trim().min(3).max(1600),
      budget: z.number().nonnegative().max(100_000_000).optional(),
      startDate: z.string().date().optional(),
      endDate: z.string().date().optional(),
      channels: z.array(z.string().trim().min(1).max(80)).max(12).default([]),
    }),
    z.object({
      ...commonFields,
      workflowId: z.literal("content_calendar"),
      objective: z.string().trim().min(10).max(2000),
      targetAudience: z.string().trim().min(3).max(1600),
      startDate: z.string().date(),
      numberOfItems: z.number().int().min(1).max(45).default(12),
      platforms: z
        .array(
          z.enum([
            "facebook",
            "instagram",
            "linkedin",
            "twitter",
            "x",
            "tiktok",
            "pinterest",
            "youtube",
            "other",
          ]),
        )
        .min(1)
        .max(9),
    }),
    z.object({
      ...commonFields,
      workflowId: z.literal("quality_check"),
      content: z.string().trim().min(1).max(50_000),
      evidence: z.string().trim().max(20_000).default(""),
    }),
  ],
);

export type MarketingAgentRunRequest = z.infer<
  typeof marketingAgentRunRequestSchema
>;

export const campaignPlanArtifactSchema = z.object({
  title: z.string().trim().min(1).max(180),
  executiveSummary: z.string().trim().min(1).max(3000),
  objective: z.string().trim().min(1).max(2000),
  targetAudience: z.string().trim().min(1).max(2000),
  keyMessages: z.array(z.string().trim().min(1).max(500)).min(1).max(12),
  channels: z
    .array(
      z.object({
        name: z.string().trim().min(1).max(80),
        role: z.string().trim().min(1).max(500),
        budgetPercentage: z.number().min(0).max(100),
        tactics: z.array(z.string().trim().min(1).max(500)).min(1).max(12),
      }),
    )
    .min(1)
    .max(12),
  timeline: z
    .array(
      z.object({
        phase: z.string().trim().min(1).max(120),
        startWeek: z.number().int().min(1).max(104),
        endWeek: z.number().int().min(1).max(104),
        deliverables: z
          .array(z.string().trim().min(1).max(500))
          .min(1)
          .max(20),
      }),
    )
    .min(1)
    .max(24),
  kpis: z
    .array(
      z.object({
        name: z.string().trim().min(1).max(120),
        target: z.string().trim().min(1).max(240),
        measurement: z.string().trim().min(1).max(500),
      }),
    )
    .min(1)
    .max(20),
  risks: z.array(z.string().trim().min(1).max(500)).max(20),
});

export const contentCalendarArtifactSchema = z.object({
  title: z.string().trim().min(1).max(180),
  strategySummary: z.string().trim().min(1).max(3000),
  items: z
    .array(
      z.object({
        title: z.string().trim().min(1).max(180),
        platform: z.enum([
          "facebook",
          "instagram",
          "linkedin",
          "twitter",
          "x",
          "tiktok",
          "pinterest",
          "youtube",
          "other",
        ]),
        contentType: z.enum([
          "post",
          "story",
          "reel",
          "video",
          "article",
          "blog",
          "tweet",
          "other",
        ]),
        scheduledDate: z.string().date(),
        copy: z.string().trim().min(1).max(10_000),
        cta: z.string().trim().max(500),
        hashtags: z.array(z.string().trim().min(1).max(100)).max(30),
      }),
    )
    .min(1)
    .max(45),
});
