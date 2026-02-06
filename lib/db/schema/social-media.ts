import { pgTable, uuid, text, timestamp, jsonb, boolean, numeric, date } from "drizzle-orm/pg-core";
import { relations } from "drizzle-orm";
import { clients } from "./clients";
import { users } from "./users";

/**
 * Social Media Platform enum
 */
export const socialPlatformEnum = [
  "facebook",
  "instagram",
  "twitter",
  "linkedin",
  "tiktok",
  "youtube",
  "pinterest",
] as const;
export type SocialPlatform = (typeof socialPlatformEnum)[number];

/**
 * Social Post Status enum
 */
export const postStatusEnum = ["draft", "scheduled", "published", "failed", "deleted"] as const;
export type PostStatus = (typeof postStatusEnum)[number];

/**
 * Ad Status enum
 */
export const adStatusEnum = ["active", "paused", "deleted", "archived"] as const;
export type AdStatus = (typeof adStatusEnum)[number];

/**
 * Social Accounts table
 *
 * Stores connected social media accounts with OAuth tokens.
 * Tokens are encrypted at rest for security.
 */
export const socialAccounts = pgTable("social_accounts", {
  id: uuid("id").primaryKey().defaultRandom(),
  clientId: uuid("client_id")
    .notNull()
    .references(() => clients.id, { onDelete: "cascade" }),
  platform: text("platform", { enum: socialPlatformEnum }).notNull(),
  accountName: text("account_name").notNull(),
  accountId: text("account_id").notNull(), // Platform-specific account ID
  accessTokenEncrypted: text("access_token_encrypted").notNull(),
  refreshTokenEncrypted: text("refresh_token_encrypted"),
  expiresAt: timestamp("expires_at", { withTimezone: true }),
  isActive: boolean("is_active").default(true).notNull(),
  metadata: jsonb("metadata").$type<{
    profileUrl?: string;
    profileImage?: string;
    followerCount?: number;
    verifiedStatus?: boolean;
    businessAccountId?: string;
    permissions?: string[];
  }>(),
  createdAt: timestamp("created_at", { withTimezone: true }).defaultNow().notNull(),
  updatedAt: timestamp("updated_at", { withTimezone: true }).defaultNow().notNull(),
  deletedAt: timestamp("deleted_at", { withTimezone: true }),
});

/**
 * Social Posts table
 *
 * Stores scheduled and published social media posts.
 */
export const socialPosts = pgTable("social_posts", {
  id: uuid("id").primaryKey().defaultRandom(),
  accountId: uuid("account_id")
    .notNull()
    .references(() => socialAccounts.id, { onDelete: "cascade" }),
  content: text("content").notNull(),
  scheduledFor: timestamp("scheduled_for", { withTimezone: true }),
  publishedAt: timestamp("published_at", { withTimezone: true }),
  postUrl: text("post_url"),
  engagementMetrics: jsonb("engagement_metrics").$type<{
    likes?: number;
    comments?: number;
    shares?: number;
    views?: number;
    reach?: number;
    impressions?: number;
    saves?: number;
    clicks?: number;
  }>(),
  status: text("status", { enum: postStatusEnum }).default("draft").notNull(),
  createdBy: uuid("created_by")
    .notNull()
    .references(() => users.id),
  metadata: jsonb("metadata").$type<{
    mediaUrls?: string[];
    hashtags?: string[];
    mentions?: string[];
    location?: string;
    errorMessage?: string;
  }>(),
  createdAt: timestamp("created_at", { withTimezone: true }).defaultNow().notNull(),
  updatedAt: timestamp("updated_at", { withTimezone: true }).defaultNow().notNull(),
  deletedAt: timestamp("deleted_at", { withTimezone: true }),
});

/**
 * Ad Accounts table
 *
 * Stores connected advertising accounts (Facebook Ads, Google Ads, etc.)
 */
export const adAccounts = pgTable("ad_accounts", {
  id: uuid("id").primaryKey().defaultRandom(),
  clientId: uuid("client_id")
    .notNull()
    .references(() => clients.id, { onDelete: "cascade" }),
  platform: text("platform", { enum: socialPlatformEnum }).notNull(),
  accountId: text("account_id").notNull(), // Platform-specific account ID
  accountName: text("account_name").notNull(),
  currency: text("currency").default("USD").notNull(),
  timezone: text("timezone").default("America/New_York").notNull(),
  isActive: boolean("is_active").default(true).notNull(),
  metadata: jsonb("metadata").$type<{
    businessId?: string;
    accountStatus?: string;
    spendCap?: number;
    capabilities?: string[];
  }>(),
  createdAt: timestamp("created_at", { withTimezone: true }).defaultNow().notNull(),
  updatedAt: timestamp("updated_at", { withTimezone: true }).defaultNow().notNull(),
  deletedAt: timestamp("deleted_at", { withTimezone: true }),
});

/**
 * Ad Campaigns table
 *
 * Stores advertising campaigns.
 */
export const adCampaigns = pgTable("ad_campaigns", {
  id: uuid("id").primaryKey().defaultRandom(),
  adAccountId: uuid("ad_account_id")
    .notNull()
    .references(() => adAccounts.id, { onDelete: "cascade" }),
  campaignId: text("campaign_id").notNull(), // Platform-specific campaign ID
  name: text("name").notNull(),
  objective: text("objective").notNull(), // REACH, TRAFFIC, CONVERSIONS, etc.
  status: text("status", { enum: adStatusEnum }).default("active").notNull(),
  dailyBudget: numeric("daily_budget", { precision: 10, scale: 2 }),
  lifetimeBudget: numeric("lifetime_budget", { precision: 10, scale: 2 }),
  startDate: date("start_date"),
  endDate: date("end_date"),
  metadata: jsonb("metadata").$type<{
    bidStrategy?: string;
    optimizationGoal?: string;
    specialAdCategories?: string[];
  }>(),
  createdAt: timestamp("created_at", { withTimezone: true }).defaultNow().notNull(),
  updatedAt: timestamp("updated_at", { withTimezone: true }).defaultNow().notNull(),
  deletedAt: timestamp("deleted_at", { withTimezone: true }),
});

/**
 * Ad Sets table
 *
 * Stores ad sets within campaigns (targeting, budget, schedule).
 */
export const adSets = pgTable("ad_sets", {
  id: uuid("id").primaryKey().defaultRandom(),
  campaignId: uuid("campaign_id")
    .notNull()
    .references(() => adCampaigns.id, { onDelete: "cascade" }),
  adSetId: text("ad_set_id").notNull(), // Platform-specific ad set ID
  name: text("name").notNull(),
  targeting: jsonb("targeting").$type<{
    locations?: { countries?: string[]; cities?: string[]; regions?: string[] };
    ageMin?: number;
    ageMax?: number;
    genders?: string[];
    interests?: string[];
    behaviors?: string[];
    customAudiences?: string[];
    lookalike?: { audienceId: string; ratio: number }[];
    devicePlatforms?: string[];
    placements?: string[];
  }>(),
  bidAmount: numeric("bid_amount", { precision: 10, scale: 4 }),
  optimizationGoal: text("optimization_goal"),
  status: text("status", { enum: adStatusEnum }).default("active").notNull(),
  metadata: jsonb("metadata").$type<{
    billingEvent?: string;
    attributionSetting?: string;
    destinationType?: string;
  }>(),
  createdAt: timestamp("created_at", { withTimezone: true }).defaultNow().notNull(),
  updatedAt: timestamp("updated_at", { withTimezone: true }).defaultNow().notNull(),
  deletedAt: timestamp("deleted_at", { withTimezone: true }),
});

/**
 * Ads table
 *
 * Stores individual ads within ad sets.
 */
export const ads = pgTable("ads", {
  id: uuid("id").primaryKey().defaultRandom(),
  adSetId: uuid("ad_set_id")
    .notNull()
    .references(() => adSets.id, { onDelete: "cascade" }),
  adId: text("ad_id").notNull(), // Platform-specific ad ID
  name: text("name").notNull(),
  creativeId: uuid("creative_id").references(() => adCreatives.id),
  status: text("status", { enum: adStatusEnum }).default("active").notNull(),
  metadata: jsonb("metadata").$type<{
    trackingSpecs?: any[];
    conversionPixelId?: string;
    utmParams?: { source?: string; medium?: string; campaign?: string };
  }>(),
  createdAt: timestamp("created_at", { withTimezone: true }).defaultNow().notNull(),
  updatedAt: timestamp("updated_at", { withTimezone: true }).defaultNow().notNull(),
  deletedAt: timestamp("deleted_at", { withTimezone: true }),
});

/**
 * Ad Creatives table
 *
 * Stores ad creative assets (images, videos, copy).
 */
export const adCreatives = pgTable("ad_creatives", {
  id: uuid("id").primaryKey().defaultRandom(),
  adAccountId: uuid("ad_account_id")
    .notNull()
    .references(() => adAccounts.id, { onDelete: "cascade" }),
  creativeId: text("creative_id"), // Platform-specific creative ID
  name: text("name").notNull(),
  assetUrls: jsonb("asset_urls").$type<{
    image?: string;
    video?: string;
    carousel?: { image: string; link?: string; headline?: string }[];
  }>(),
  headline: text("headline"),
  description: text("description"),
  callToAction: text("call_to_action"),
  metadata: jsonb("metadata").$type<{
    destinationUrl?: string;
    displayLink?: string;
    aspectRatio?: string;
    format?: string; // SINGLE_IMAGE, VIDEO, CAROUSEL, etc.
    thumbnailUrl?: string;
  }>(),
  createdAt: timestamp("created_at", { withTimezone: true }).defaultNow().notNull(),
  updatedAt: timestamp("updated_at", { withTimezone: true }).defaultNow().notNull(),
  deletedAt: timestamp("deleted_at", { withTimezone: true }),
});

/**
 * Ad Metrics table
 *
 * Stores daily ad performance metrics.
 */
export const adMetrics = pgTable("ad_metrics", {
  id: uuid("id").primaryKey().defaultRandom(),
  adId: uuid("ad_id")
    .notNull()
    .references(() => ads.id, { onDelete: "cascade" }),
  metricDate: date("metric_date").notNull(),
  impressions: numeric("impressions", { precision: 12, scale: 0 }).default("0").notNull(),
  clicks: numeric("clicks", { precision: 12, scale: 0 }).default("0").notNull(),
  spend: numeric("spend", { precision: 10, scale: 2 }).default("0").notNull(),
  conversions: numeric("conversions", { precision: 10, scale: 2 }).default("0").notNull(),
  ctr: numeric("ctr", { precision: 6, scale: 4 }).default("0").notNull(), // Click-through rate
  cpc: numeric("cpc", { precision: 10, scale: 4 }).default("0").notNull(), // Cost per click
  cpm: numeric("cpm", { precision: 10, scale: 4 }).default("0").notNull(), // Cost per mille
  roas: numeric("roas", { precision: 10, scale: 4 }).default("0").notNull(), // Return on ad spend
  metadata: jsonb("metadata").$type<{
    videoViews?: number;
    videoViewsP25?: number;
    videoViewsP50?: number;
    videoViewsP75?: number;
    videoViewsP100?: number;
    linkClicks?: number;
    postEngagement?: number;
    reach?: number;
    frequency?: number;
  }>(),
  createdAt: timestamp("created_at", { withTimezone: true }).defaultNow().notNull(),
  updatedAt: timestamp("updated_at", { withTimezone: true }).defaultNow().notNull(),
});

/**
 * Relations
 */
export const socialAccountsRelations = relations(socialAccounts, ({ one, many }) => ({
  client: one(clients, {
    fields: [socialAccounts.clientId],
    references: [clients.id],
  }),
  posts: many(socialPosts),
}));

export const socialPostsRelations = relations(socialPosts, ({ one }) => ({
  account: one(socialAccounts, {
    fields: [socialPosts.accountId],
    references: [socialAccounts.id],
  }),
  createdBy: one(users, {
    fields: [socialPosts.createdBy],
    references: [users.id],
  }),
}));

export const adAccountsRelations = relations(adAccounts, ({ one, many }) => ({
  client: one(clients, {
    fields: [adAccounts.clientId],
    references: [clients.id],
  }),
  campaigns: many(adCampaigns),
  creatives: many(adCreatives),
}));

export const adCampaignsRelations = relations(adCampaigns, ({ one, many }) => ({
  adAccount: one(adAccounts, {
    fields: [adCampaigns.adAccountId],
    references: [adAccounts.id],
  }),
  adSets: many(adSets),
}));

export const adSetsRelations = relations(adSets, ({ one, many }) => ({
  campaign: one(adCampaigns, {
    fields: [adSets.campaignId],
    references: [adCampaigns.id],
  }),
  ads: many(ads),
}));

export const adsRelations = relations(ads, ({ one, many }) => ({
  adSet: one(adSets, {
    fields: [ads.adSetId],
    references: [adSets.id],
  }),
  creative: one(adCreatives, {
    fields: [ads.creativeId],
    references: [adCreatives.id],
  }),
  metrics: many(adMetrics),
}));

export const adCreativesRelations = relations(adCreatives, ({ one, many }) => ({
  adAccount: one(adAccounts, {
    fields: [adCreatives.adAccountId],
    references: [adAccounts.id],
  }),
  ads: many(ads),
}));

export const adMetricsRelations = relations(adMetrics, ({ one }) => ({
  ad: one(ads, {
    fields: [adMetrics.adId],
    references: [ads.id],
  }),
}));

/**
 * TypeScript types
 */
export type SocialAccount = typeof socialAccounts.$inferSelect;
export type NewSocialAccount = typeof socialAccounts.$inferInsert;

export type SocialPost = typeof socialPosts.$inferSelect;
export type NewSocialPost = typeof socialPosts.$inferInsert;

export type AdAccount = typeof adAccounts.$inferSelect;
export type NewAdAccount = typeof adAccounts.$inferInsert;

export type AdCampaign = typeof adCampaigns.$inferSelect;
export type NewAdCampaign = typeof adCampaigns.$inferInsert;

export type AdSet = typeof adSets.$inferSelect;
export type NewAdSet = typeof adSets.$inferInsert;

export type Ad = typeof ads.$inferSelect;
export type NewAd = typeof ads.$inferInsert;

export type AdCreative = typeof adCreatives.$inferSelect;
export type NewAdCreative = typeof adCreatives.$inferInsert;

export type AdMetric = typeof adMetrics.$inferSelect;
export type NewAdMetric = typeof adMetrics.$inferInsert;
