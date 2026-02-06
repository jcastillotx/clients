import { pgTable, uuid, text, timestamp, jsonb, integer, boolean, date } from "drizzle-orm/pg-core";
import { relations } from "drizzle-orm";
import { clients } from "./clients";
import { users } from "./users";

/**
 * Brand Guide Status enum
 */
export const brandGuideStatusEnum = ["draft", "published"] as const;
export type BrandGuideStatus = (typeof brandGuideStatusEnum)[number];

/**
 * Brand Mention Sentiment enum
 */
export const brandMentionSentimentEnum = ["positive", "neutral", "negative"] as const;
export type BrandMentionSentiment = (typeof brandMentionSentimentEnum)[number];

/**
 * Brand Asset Type enum
 */
export const brandAssetTypeEnum = ["logo", "color", "font", "image", "video", "document"] as const;
export type BrandAssetType = (typeof brandAssetTypeEnum)[number];

/**
 * Brand Inconsistency Category enum
 */
export const brandInconsistencyCategoryEnum = ["visual", "messaging", "tone"] as const;
export type BrandInconsistencyCategory = (typeof brandInconsistencyCategoryEnum)[number];

/**
 * Brand Inconsistency Severity enum
 */
export const brandInconsistencySeverityEnum = ["critical", "error", "warning", "info"] as const;
export type BrandInconsistencySeverity = (typeof brandInconsistencySeverityEnum)[number];

/**
 * Brand Audit Status enum
 */
export const brandAuditStatusEnum = ["pending", "running", "completed", "failed"] as const;
export type BrandAuditStatus = (typeof brandAuditStatusEnum)[number];

/**
 * Brand Color Type enum
 */
export const brandColorTypeEnum = ["primary", "secondary", "accent"] as const;
export type BrandColorType = (typeof brandColorTypeEnum)[number];

/**
 * Brand Font Category enum
 */
export const brandFontCategoryEnum = ["primary", "secondary"] as const;
export type BrandFontCategory = (typeof brandFontCategoryEnum)[number];

/**
 * Brand Guide Sections table
 *
 * Stores brand guide sections with structured content
 */
export const brandGuideSections = pgTable("brand_guide_sections", {
  id: uuid("id").primaryKey().defaultRandom(),
  brandGuideId: uuid("brand_guide_id")
    .notNull()
    .references(() => brandGuides.id, { onDelete: "cascade" }),
  sectionType: text("section_type").notNull(), // story, logo, colors, typography, imagery, voice, digital, print, social, elements
  sectionOrder: integer("section_order").default(0).notNull(),
  title: text("title"),
  content: jsonb("content").$type<Record<string, any>>(),
  isVisible: boolean("is_visible").default(true).notNull(),
  createdAt: timestamp("created_at", { withTimezone: true }).defaultNow().notNull(),
  updatedAt: timestamp("updated_at", { withTimezone: true }).defaultNow().notNull(),
});

/**
 * Brand Guides table
 *
 * Stores brand guidelines with version control
 */
export const brandGuides = pgTable("brand_guides", {
  id: uuid("id").primaryKey().defaultRandom(),
  clientId: uuid("client_id").references(() => clients.id, { onDelete: "set null" }),
  version: integer("version").default(1).notNull(),
  status: text("status", { enum: brandGuideStatusEnum }).default("draft").notNull(),
  coverImage: text("cover_image"),
  slug: text("slug").notNull().unique(),
  isPublic: boolean("is_public").default(false).notNull(),
  passwordProtected: boolean("password_protected").default(false).notNull(),
  password: text("password"),
  createdBy: uuid("created_by").references(() => users.id, { onDelete: "set null" }),
  publishedAt: timestamp("published_at", { withTimezone: true }),
  meta: jsonb("meta").$type<Record<string, any>>(),
  createdAt: timestamp("created_at", { withTimezone: true }).defaultNow().notNull(),
  updatedAt: timestamp("updated_at", { withTimezone: true }).defaultNow().notNull(),
});

/**
 * Brand Colors table
 *
 * Stores brand color palettes with accessibility notes
 */
export const brandColors = pgTable("brand_colors", {
  id: uuid("id").primaryKey().defaultRandom(),
  brandGuideId: uuid("brand_guide_id")
    .notNull()
    .references(() => brandGuides.id, { onDelete: "cascade" }),
  colorName: text("color_name"),
  colorType: text("color_type", { enum: brandColorTypeEnum }).default("primary").notNull(),
  hexValue: text("hex_value"),
  rgbValue: text("rgb_value"),
  cmykValue: text("cmyk_value"),
  pantoneValue: text("pantone_value"),
  usageContext: text("usage_context"),
  accessibilityNotes: text("accessibility_notes"),
  createdAt: timestamp("created_at", { withTimezone: true }).defaultNow().notNull(),
  updatedAt: timestamp("updated_at", { withTimezone: true }).defaultNow().notNull(),
});

/**
 * Brand Fonts table
 *
 * Stores brand typography with licensing info
 */
export const brandFonts = pgTable("brand_fonts", {
  id: uuid("id").primaryKey().defaultRandom(),
  brandGuideId: uuid("brand_guide_id")
    .notNull()
    .references(() => brandGuides.id, { onDelete: "cascade" }),
  fontName: text("font_name").notNull(),
  fontCategory: text("font_category", { enum: brandFontCategoryEnum }).default("primary").notNull(),
  fontWeights: jsonb("font_weights").$type<string[]>(),
  fontFilePath: text("font_file_path"),
  webFontUrl: text("web_font_url"),
  usageContext: text("usage_context"),
  licensingInfo: text("licensing_info"),
  createdAt: timestamp("created_at", { withTimezone: true }).defaultNow().notNull(),
  updatedAt: timestamp("updated_at", { withTimezone: true }).defaultNow().notNull(),
});

/**
 * Brand Templates table
 *
 * Stores brand templates with download tracking
 */
export const brandTemplates = pgTable("brand_templates", {
  id: uuid("id").primaryKey().defaultRandom(),
  brandGuideId: uuid("brand_guide_id")
    .notNull()
    .references(() => brandGuides.id, { onDelete: "cascade" }),
  templateName: text("template_name").notNull(),
  templateType: text("template_type").notNull(), // email, social, print, presentation, ad
  filePath: text("file_path").notNull(),
  thumbnail: text("thumbnail"),
  downloadCount: integer("download_count").default(0).notNull(),
  isPublic: boolean("is_public").default(false).notNull(),
  meta: jsonb("meta").$type<Record<string, any>>(),
  createdAt: timestamp("created_at", { withTimezone: true }).defaultNow().notNull(),
  updatedAt: timestamp("updated_at", { withTimezone: true }).defaultNow().notNull(),
});

/**
 * Brand Assets table
 *
 * Stores brand assets with approval workflow
 */
export const brandAssets = pgTable("brand_assets", {
  id: uuid("id").primaryKey().defaultRandom(),
  clientId: uuid("client_id").references(() => clients.id, { onDelete: "set null" }),
  assetType: text("asset_type", { enum: brandAssetTypeEnum }).notNull(),
  assetName: text("asset_name"),
  assetValue: text("asset_value"), // hex, font name, file path, etc.
  usageContext: text("usage_context"),
  isApproved: boolean("is_approved").default(false).notNull(),
  meta: jsonb("meta").$type<Record<string, any>>(),
  createdAt: timestamp("created_at", { withTimezone: true }).defaultNow().notNull(),
  updatedAt: timestamp("updated_at", { withTimezone: true }).defaultNow().notNull(),
});

/**
 * Brand Mentions table
 *
 * Stores brand mentions from various platforms with sentiment analysis
 */
export const brandMentions = pgTable("brand_mentions", {
  id: uuid("id").primaryKey().defaultRandom(),
  clientId: uuid("client_id").references(() => clients.id, { onDelete: "set null" }),
  platform: text("platform").notNull(), // google, yelp, facebook, x, linkedin, etc.
  mentionText: text("mention_text").notNull(),
  sentiment: text("sentiment", { enum: brandMentionSentimentEnum }),
  author: text("author"),
  url: text("url"),
  postedAt: timestamp("posted_at", { withTimezone: true }),
  respondedAt: timestamp("responded_at", { withTimezone: true }),
  respondedBy: uuid("responded_by").references(() => users.id, { onDelete: "set null" }),
  responseNotes: text("response_notes"),
  meta: jsonb("meta").$type<{
    reach?: number;
    engagement?: number;
    followers?: number;
    likes?: number;
    shares?: number;
    comments?: number;
  }>(),
  createdAt: timestamp("created_at", { withTimezone: true }).defaultNow().notNull(),
  updatedAt: timestamp("updated_at", { withTimezone: true }).defaultNow().notNull(),
});

/**
 * Brand Competitors table
 *
 * Stores competitor information for competitive analysis
 */
export const brandCompetitors = pgTable("brand_competitors", {
  id: uuid("id").primaryKey().defaultRandom(),
  clientId: uuid("client_id").references(() => clients.id, { onDelete: "set null" }),
  competitorName: text("competitor_name").notNull(),
  websiteUrl: text("website_url"),
  positioning: text("positioning"),
  targetAudience: text("target_audience"),
  keyDifferentiators: jsonb("key_differentiators").$type<string[]>(),
  isActive: boolean("is_active").default(true).notNull(),
  meta: jsonb("meta").$type<{
    socialLinks?: {
      facebook?: string;
      twitter?: string;
      linkedin?: string;
      instagram?: string;
    };
    strengths?: string[];
    weaknesses?: string[];
    marketShare?: number;
  }>(),
  createdAt: timestamp("created_at", { withTimezone: true }).defaultNow().notNull(),
  updatedAt: timestamp("updated_at", { withTimezone: true }).defaultNow().notNull(),
});

/**
 * Brand Audits table
 *
 * Stores brand audit results with scoring metrics
 */
export const brandAudits = pgTable("brand_audits", {
  id: uuid("id").primaryKey().defaultRandom(),
  clientId: uuid("client_id").references(() => clients.id, { onDelete: "set null" }),
  auditDate: date("audit_date").notNull(),
  status: text("status", { enum: brandAuditStatusEnum }).default("pending").notNull(),
  overallScore: integer("overall_score"),
  visualScore: integer("visual_score"),
  messagingScore: integer("messaging_score"),
  consistencyScore: integer("consistency_score"),
  perceptionScore: integer("perception_score"),
  report: jsonb("report").$type<{
    summary?: string;
    recommendations?: Array<{
      category: string;
      priority: string;
      description: string;
      impact: string;
    }>;
    metrics?: Record<string, any>;
  }>(),
  failureReason: text("failure_reason"),
  createdAt: timestamp("created_at", { withTimezone: true }).defaultNow().notNull(),
  updatedAt: timestamp("updated_at", { withTimezone: true }).defaultNow().notNull(),
});

/**
 * Brand Inconsistencies table
 *
 * Stores detected brand inconsistencies from audits
 */
export const brandInconsistencies = pgTable("brand_inconsistencies", {
  id: uuid("id").primaryKey().defaultRandom(),
  brandAuditId: uuid("brand_audit_id")
    .notNull()
    .references(() => brandAudits.id, { onDelete: "cascade" }),
  category: text("category", { enum: brandInconsistencyCategoryEnum }).default("visual").notNull(),
  severity: text("severity", { enum: brandInconsistencySeverityEnum }).default("warning").notNull(),
  location: text("location"), // URL or platform
  description: text("description").notNull(),
  recommendation: text("recommendation"),
  status: text("status").default("open").notNull(), // open, resolved
  meta: jsonb("meta").$type<Record<string, any>>(),
  createdAt: timestamp("created_at", { withTimezone: true }).defaultNow().notNull(),
  updatedAt: timestamp("updated_at", { withTimezone: true }).defaultNow().notNull(),
});

/**
 * Relations
 */
export const brandGuidesRelations = relations(brandGuides, ({ one, many }) => ({
  client: one(clients, {
    fields: [brandGuides.clientId],
    references: [clients.id],
  }),
  creator: one(users, {
    fields: [brandGuides.createdBy],
    references: [users.id],
  }),
  sections: many(brandGuideSections),
  colors: many(brandColors),
  fonts: many(brandFonts),
  templates: many(brandTemplates),
}));

export const brandGuideSectionsRelations = relations(brandGuideSections, ({ one }) => ({
  guide: one(brandGuides, {
    fields: [brandGuideSections.brandGuideId],
    references: [brandGuides.id],
  }),
}));

export const brandColorsRelations = relations(brandColors, ({ one }) => ({
  guide: one(brandGuides, {
    fields: [brandColors.brandGuideId],
    references: [brandGuides.id],
  }),
}));

export const brandFontsRelations = relations(brandFonts, ({ one }) => ({
  guide: one(brandGuides, {
    fields: [brandFonts.brandGuideId],
    references: [brandGuides.id],
  }),
}));

export const brandTemplatesRelations = relations(brandTemplates, ({ one }) => ({
  guide: one(brandGuides, {
    fields: [brandTemplates.brandGuideId],
    references: [brandGuides.id],
  }),
}));

export const brandAssetsRelations = relations(brandAssets, ({ one }) => ({
  client: one(clients, {
    fields: [brandAssets.clientId],
    references: [clients.id],
  }),
}));

export const brandMentionsRelations = relations(brandMentions, ({ one }) => ({
  client: one(clients, {
    fields: [brandMentions.clientId],
    references: [clients.id],
  }),
  respondedByUser: one(users, {
    fields: [brandMentions.respondedBy],
    references: [users.id],
  }),
}));

export const brandCompetitorsRelations = relations(brandCompetitors, ({ one }) => ({
  client: one(clients, {
    fields: [brandCompetitors.clientId],
    references: [clients.id],
  }),
}));

export const brandAuditsRelations = relations(brandAudits, ({ one, many }) => ({
  client: one(clients, {
    fields: [brandAudits.clientId],
    references: [clients.id],
  }),
  inconsistencies: many(brandInconsistencies),
}));

export const brandInconsistenciesRelations = relations(brandInconsistencies, ({ one }) => ({
  audit: one(brandAudits, {
    fields: [brandInconsistencies.brandAuditId],
    references: [brandAudits.id],
  }),
}));

/**
 * TypeScript types
 */
export type BrandGuide = typeof brandGuides.$inferSelect;
export type NewBrandGuide = typeof brandGuides.$inferInsert;

export type BrandGuideSection = typeof brandGuideSections.$inferSelect;
export type NewBrandGuideSection = typeof brandGuideSections.$inferInsert;

export type BrandColor = typeof brandColors.$inferSelect;
export type NewBrandColor = typeof brandColors.$inferInsert;

export type BrandFont = typeof brandFonts.$inferSelect;
export type NewBrandFont = typeof brandFonts.$inferInsert;

export type BrandTemplate = typeof brandTemplates.$inferSelect;
export type NewBrandTemplate = typeof brandTemplates.$inferInsert;

export type BrandAsset = typeof brandAssets.$inferSelect;
export type NewBrandAsset = typeof brandAssets.$inferInsert;

export type BrandMention = typeof brandMentions.$inferSelect;
export type NewBrandMention = typeof brandMentions.$inferInsert;

export type BrandCompetitor = typeof brandCompetitors.$inferSelect;
export type NewBrandCompetitor = typeof brandCompetitors.$inferInsert;

export type BrandAudit = typeof brandAudits.$inferSelect;
export type NewBrandAudit = typeof brandAudits.$inferInsert;

export type BrandInconsistency = typeof brandInconsistencies.$inferSelect;
export type NewBrandInconsistency = typeof brandInconsistencies.$inferInsert;
