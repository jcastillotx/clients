import { pgTable, uuid, text, decimal, timestamp, integer, jsonb, date } from "drizzle-orm/pg-core";
import { relations } from "drizzle-orm";
import { clients } from "./clients";
import { users } from "./users";

/**
 * Campaign type enum
 */
export const campaignTypeEnum = ["email", "social", "ppc", "content", "seo", "display", "video", "other"] as const;
export type CampaignType = (typeof campaignTypeEnum)[number];

/**
 * Campaign status enum
 */
export const campaignStatusEnum = ["draft", "scheduled", "active", "paused", "completed", "cancelled"] as const;
export type CampaignStatus = (typeof campaignStatusEnum)[number];

/**
 * Asset type enum
 */
export const assetTypeEnum = [
  "image",
  "video",
  "document",
  "creative",
  "landing_page",
  "email_template",
  "other",
] as const;
export type AssetType = (typeof assetTypeEnum)[number];

/**
 * Content type enum
 */
export const contentTypeEnum = ["post", "story", "reel", "video", "article", "blog", "tweet", "other"] as const;
export type ContentType = (typeof contentTypeEnum)[number];

/**
 * Platform enum
 */
export const platformEnum = [
  "facebook",
  "instagram",
  "linkedin",
  "twitter",
  "x",
  "tiktok",
  "pinterest",
  "youtube",
  "other",
] as const;
export type Platform = (typeof platformEnum)[number];

/**
 * Content status enum
 */
export const contentStatusEnum = [
  "draft",
  "pending_approval",
  "approved",
  "needs_revision",
  "scheduled",
  "published",
  "failed",
] as const;
export type ContentStatus = (typeof contentStatusEnum)[number];

/**
 * Template type enum
 */
export const templateTypeEnum = ["email", "social", "ad", "landing_page", "blog", "other"] as const;
export type TemplateType = (typeof templateTypeEnum)[number];

/**
 * Lead source enum
 */
export const leadSourceEnum = [
  "website",
  "social",
  "email",
  "referral",
  "paid_ad",
  "organic",
  "event",
  "cold_outreach",
  "other",
] as const;
export type LeadSource = (typeof leadSourceEnum)[number];

/**
 * Lead status enum
 */
export const leadStatusEnum = [
  "new",
  "contacted",
  "qualified",
  "proposal",
  "negotiation",
  "converted",
  "lost",
  "nurturing",
] as const;
export type LeadStatus = (typeof leadStatusEnum)[number];

/**
 * Activity type enum
 */
export const activityTypeEnum = [
  "email_sent",
  "email_opened",
  "link_clicked",
  "form_submitted",
  "call_made",
  "meeting_scheduled",
  "note_added",
  "status_changed",
  "other",
] as const;
export type ActivityType = (typeof activityTypeEnum)[number];

/**
 * Campaigns table
 *
 * Marketing campaigns with budget tracking and performance metrics.
 */
export const campaigns = pgTable("campaigns", {
  id: uuid("id").primaryKey().defaultRandom(),
  clientId: uuid("client_id")
    .references(() => clients.id, { onDelete: "cascade" })
    .notNull(),
  name: text("name").notNull(),
  description: text("description"),
  campaignType: text("campaign_type", { enum: campaignTypeEnum }).notNull(),
  status: text("status", { enum: campaignStatusEnum }).default("draft").notNull(),
  startDate: date("start_date"),
  endDate: date("end_date"),
  budget: decimal("budget", { precision: 12, scale: 2 }),
  spent: decimal("spent", { precision: 12, scale: 2 }).default("0"),
  // Campaign goals stored as JSONB
  goals: jsonb("goals").$type<{
    impressions?: number;
    clicks?: number;
    conversions?: number;
    revenue?: number;
    roi?: number;
  }>(),
  // Channels array
  channels: jsonb("channels").$type<string[]>(),
  createdBy: uuid("created_by").references(() => users.id),
  // Additional metadata
  metadata: jsonb("metadata").$type<{
    targetAudience?: string;
    tags?: string[];
    notes?: string;
    externalId?: string;
    aiTaskId?: string;
    agentWorkflow?: string;
    approvalStatus?: "pending_approval";
  }>(),
  createdAt: timestamp("created_at", { withTimezone: true }).defaultNow().notNull(),
  updatedAt: timestamp("updated_at", { withTimezone: true }).defaultNow().notNull(),
  deletedAt: timestamp("deleted_at", { withTimezone: true }),
});

/**
 * Campaign assets table
 *
 * Assets associated with campaigns (images, videos, documents).
 */
export const campaignAssets = pgTable("campaign_assets", {
  id: uuid("id").primaryKey().defaultRandom(),
  campaignId: uuid("campaign_id")
    .references(() => campaigns.id, { onDelete: "cascade" })
    .notNull(),
  assetType: text("asset_type", { enum: assetTypeEnum }).notNull(),
  name: text("name").notNull(),
  fileUrl: text("file_url").notNull(),
  thumbnailUrl: text("thumbnail_url"),
  isPrimary: integer("is_primary").default(0), // Using integer as boolean (0 or 1)
  // Asset metadata
  metadata: jsonb("metadata").$type<{
    fileSize?: number;
    mimeType?: string;
    dimensions?: { width: number; height: number };
    duration?: number; // for videos
    altText?: string;
  }>(),
  createdAt: timestamp("created_at", { withTimezone: true }).defaultNow().notNull(),
  updatedAt: timestamp("updated_at", { withTimezone: true }).defaultNow().notNull(),
});

/**
 * Campaign metrics table
 *
 * Daily performance metrics for campaigns.
 */
export const campaignMetrics = pgTable("campaign_metrics", {
  id: uuid("id").primaryKey().defaultRandom(),
  campaignId: uuid("campaign_id")
    .references(() => campaigns.id, { onDelete: "cascade" })
    .notNull(),
  metricDate: date("metric_date").notNull(),
  channel: text("channel"),
  impressions: integer("impressions").default(0),
  clicks: integer("clicks").default(0),
  conversions: integer("conversions").default(0),
  cost: decimal("cost", { precision: 12, scale: 2 }).default("0"),
  revenue: decimal("revenue", { precision: 12, scale: 2 }).default("0"),
  // Additional metrics
  metadata: jsonb("metadata").$type<{
    ctr?: number; // click-through rate
    cpc?: number; // cost per click
    cpa?: number; // cost per acquisition
    roi?: number; // return on investment
    engagement?: number;
  }>(),
  createdAt: timestamp("created_at", { withTimezone: true }).defaultNow().notNull(),
  updatedAt: timestamp("updated_at", { withTimezone: true }).defaultNow().notNull(),
});

/**
 * Content calendar items table
 *
 * Scheduled content for social media and marketing channels.
 */
export const contentCalendarItems = pgTable("content_calendar_items", {
  id: uuid("id").primaryKey().defaultRandom(),
  clientId: uuid("client_id")
    .references(() => clients.id, { onDelete: "cascade" })
    .notNull(),
  title: text("title").notNull(),
  contentType: text("content_type", { enum: contentTypeEnum }).notNull(),
  platform: text("platform", { enum: platformEnum }).notNull(),
  scheduledFor: timestamp("scheduled_for", { withTimezone: true }),
  publishedAt: timestamp("published_at", { withTimezone: true }),
  status: text("status", { enum: contentStatusEnum }).default("draft").notNull(),
  content: text("content"),
  campaignTag: text("campaign_tag"),
  approvedBy: uuid("approved_by").references(() => users.id),
  createdBy: uuid("created_by").references(() => users.id),
  // Content metadata
  metadata: jsonb("metadata").$type<{
    mediaUrls?: string[];
    hashtags?: string[];
    mentions?: string[];
    characterLimit?: number;
    failureReason?: string;
    aiTaskId?: string;
    agentWorkflow?: string;
    qualityDecision?: "PASS" | "WARN" | "BLOCKED";
  }>(),
  createdAt: timestamp("created_at", { withTimezone: true }).defaultNow().notNull(),
  updatedAt: timestamp("updated_at", { withTimezone: true }).defaultNow().notNull(),
  deletedAt: timestamp("deleted_at", { withTimezone: true }),
});

/**
 * Content templates table
 *
 * Reusable templates for marketing content.
 */
export const contentTemplates = pgTable("content_templates", {
  id: uuid("id").primaryKey().defaultRandom(),
  clientId: uuid("client_id")
    .references(() => clients.id, { onDelete: "cascade" })
    .notNull(),
  name: text("name").notNull(),
  templateType: text("template_type", { enum: templateTypeEnum }).notNull(),
  content: text("content").notNull(),
  // Template variables
  variables: jsonb("variables").$type<
    {
      name: string;
      type: "text" | "number" | "date" | "url";
      defaultValue?: string;
      required?: boolean;
    }[]
  >(),
  usageCount: integer("usage_count").default(0),
  createdBy: uuid("created_by").references(() => users.id),
  // Additional metadata
  metadata: jsonb("metadata").$type<{
    tags?: string[];
    category?: string;
    thumbnail?: string;
  }>(),
  createdAt: timestamp("created_at", { withTimezone: true }).defaultNow().notNull(),
  updatedAt: timestamp("updated_at", { withTimezone: true }).defaultNow().notNull(),
  deletedAt: timestamp("deleted_at", { withTimezone: true }),
});

/**
 * Leads table
 *
 * Marketing leads and prospects management.
 */
export const leads = pgTable("leads", {
  id: uuid("id").primaryKey().defaultRandom(),
  clientId: uuid("client_id")
    .references(() => clients.id, { onDelete: "cascade" })
    .notNull(),
  name: text("name").notNull(),
  email: text("email").notNull(),
  phone: text("phone"),
  company: text("company"),
  source: text("source", { enum: leadSourceEnum }).notNull(),
  status: text("status", { enum: leadStatusEnum }).default("new").notNull(),
  score: integer("score").default(0),
  assignedTo: uuid("assigned_to").references(() => users.id),
  convertedAt: timestamp("converted_at", { withTimezone: true }),
  // Lead metadata
  metadata: jsonb("metadata").$type<{
    position?: string;
    website?: string;
    industry?: string;
    employeeCount?: string;
    revenue?: string;
    interests?: string[];
    tags?: string[];
    notes?: string;
  }>(),
  createdAt: timestamp("created_at", { withTimezone: true }).defaultNow().notNull(),
  updatedAt: timestamp("updated_at", { withTimezone: true }).defaultNow().notNull(),
  deletedAt: timestamp("deleted_at", { withTimezone: true }),
});

/**
 * Lead activities table
 *
 * Activity tracking for leads (emails, calls, meetings, etc.).
 */
export const leadActivities = pgTable("lead_activities", {
  id: uuid("id").primaryKey().defaultRandom(),
  leadId: uuid("lead_id")
    .references(() => leads.id, { onDelete: "cascade" })
    .notNull(),
  activityType: text("activity_type", { enum: activityTypeEnum }).notNull(),
  description: text("description").notNull(),
  occurredAt: timestamp("occurred_at", { withTimezone: true }).defaultNow().notNull(),
  userId: uuid("user_id").references(() => users.id),
  // Activity metadata
  metadata: jsonb("metadata").$type<{
    emailSubject?: string;
    linkUrl?: string;
    formName?: string;
    callDuration?: number;
    meetingDate?: string;
    oldStatus?: string;
    newStatus?: string;
  }>(),
  createdAt: timestamp("created_at", { withTimezone: true }).defaultNow().notNull(),
});

/**
 * Relations
 */
export const campaignsRelations = relations(campaigns, ({ one, many }) => ({
  client: one(clients, {
    fields: [campaigns.clientId],
    references: [clients.id],
  }),
  creator: one(users, {
    fields: [campaigns.createdBy],
    references: [users.id],
  }),
  assets: many(campaignAssets),
  metrics: many(campaignMetrics),
}));

export const campaignAssetsRelations = relations(campaignAssets, ({ one }) => ({
  campaign: one(campaigns, {
    fields: [campaignAssets.campaignId],
    references: [campaigns.id],
  }),
}));

export const campaignMetricsRelations = relations(campaignMetrics, ({ one }) => ({
  campaign: one(campaigns, {
    fields: [campaignMetrics.campaignId],
    references: [campaigns.id],
  }),
}));

export const contentCalendarItemsRelations = relations(contentCalendarItems, ({ one }) => ({
  client: one(clients, {
    fields: [contentCalendarItems.clientId],
    references: [clients.id],
  }),
  approver: one(users, {
    fields: [contentCalendarItems.approvedBy],
    references: [users.id],
  }),
  creator: one(users, {
    fields: [contentCalendarItems.createdBy],
    references: [users.id],
  }),
}));

export const contentTemplatesRelations = relations(contentTemplates, ({ one }) => ({
  client: one(clients, {
    fields: [contentTemplates.clientId],
    references: [clients.id],
  }),
  creator: one(users, {
    fields: [contentTemplates.createdBy],
    references: [users.id],
  }),
}));

export const leadsRelations = relations(leads, ({ one, many }) => ({
  client: one(clients, {
    fields: [leads.clientId],
    references: [clients.id],
  }),
  assignee: one(users, {
    fields: [leads.assignedTo],
    references: [users.id],
  }),
  activities: many(leadActivities),
}));

export const leadActivitiesRelations = relations(leadActivities, ({ one }) => ({
  lead: one(leads, {
    fields: [leadActivities.leadId],
    references: [leads.id],
  }),
  user: one(users, {
    fields: [leadActivities.userId],
    references: [users.id],
  }),
}));

/**
 * TypeScript types
 */
export type Campaign = typeof campaigns.$inferSelect;
export type NewCampaign = typeof campaigns.$inferInsert;
export type CampaignAsset = typeof campaignAssets.$inferSelect;
export type NewCampaignAsset = typeof campaignAssets.$inferInsert;
export type CampaignMetric = typeof campaignMetrics.$inferSelect;
export type NewCampaignMetric = typeof campaignMetrics.$inferInsert;
export type ContentCalendarItem = typeof contentCalendarItems.$inferSelect;
export type NewContentCalendarItem = typeof contentCalendarItems.$inferInsert;
export type ContentTemplate = typeof contentTemplates.$inferSelect;
export type NewContentTemplate = typeof contentTemplates.$inferInsert;
export type Lead = typeof leads.$inferSelect;
export type NewLead = typeof leads.$inferInsert;
export type LeadActivity = typeof leadActivities.$inferSelect;
export type NewLeadActivity = typeof leadActivities.$inferInsert;
