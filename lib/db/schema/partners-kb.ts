import {
  pgTable,
  uuid,
  text,
  timestamp,
  jsonb,
  boolean,
  integer,
  decimal,
  index,
  uniqueIndex,
} from "drizzle-orm/pg-core";
import { relations } from "drizzle-orm";
import { clients } from "./clients";
import { users } from "./users";

/**
 * Partner types enum
 */
export const partnerTypeEnum = ["agency", "affiliate", "reseller", "strategic"] as const;
export type PartnerType = (typeof partnerTypeEnum)[number];

/**
 * Partner status enum
 */
export const partnerStatusEnum = ["active", "inactive", "pending", "suspended"] as const;
export type PartnerStatus = (typeof partnerStatusEnum)[number];

/**
 * Referral status enum
 */
export const referralStatusEnum = ["pending", "contacted", "qualified", "converted", "rejected", "lost"] as const;
export type ReferralStatus = (typeof referralStatusEnum)[number];

/**
 * Partners table
 *
 * Stores partner organizations who refer clients
 */
export const partners = pgTable(
  "partners",
  {
    id: uuid("id").primaryKey().defaultRandom(),
    companyName: text("company_name").notNull(),
    contactName: text("contact_name").notNull(),
    email: text("email").notNull().unique(),
    phone: text("phone"),
    website: text("website"),
    partnerType: text("partner_type", { enum: partnerTypeEnum }).default("affiliate").notNull(),
    status: text("status", { enum: partnerStatusEnum }).default("active").notNull(),
    commissionRate: decimal("commission_rate", { precision: 5, scale: 2 }).default("0").notNull(), // percentage
    totalReferrals: integer("total_referrals").default(0).notNull(),
    totalRevenue: decimal("total_revenue", { precision: 12, scale: 2 }).default("0").notNull(),
    code: text("code").notNull().unique(), // unique referral code
    metadata: jsonb("metadata").$type<{
      address?: string;
      city?: string;
      state?: string;
      country?: string;
      notes?: string;
      contractUrl?: string;
      paymentInfo?: Record<string, unknown>;
    }>(),
    createdAt: timestamp("created_at", { withTimezone: true }).defaultNow().notNull(),
    updatedAt: timestamp("updated_at", { withTimezone: true }).defaultNow().notNull(),
  },
  (table) => ({
    emailIdx: index("partners_email_idx").on(table.email),
    statusIdx: index("partners_status_idx").on(table.status),
    codeIdx: uniqueIndex("partners_code_idx").on(table.code),
  }),
);

/**
 * Referrals table
 *
 * Tracks referrals from partners to potential clients
 */
export const referrals = pgTable(
  "referrals",
  {
    id: uuid("id").primaryKey().defaultRandom(),
    partnerId: uuid("partner_id")
      .notNull()
      .references(() => partners.id, { onDelete: "cascade" }),
    clientId: uuid("client_id").references(() => clients.id, { onDelete: "set null" }), // original referring client (optional)
    referralCode: text("referral_code").notNull(),
    referredName: text("referred_name"),
    referredEmail: text("referred_email"),
    referredPhone: text("referred_phone"),
    status: text("status", { enum: referralStatusEnum }).default("pending").notNull(),
    referredAt: timestamp("referred_at", { withTimezone: true }).defaultNow().notNull(),
    convertedAt: timestamp("converted_at", { withTimezone: true }),
    convertedClientId: uuid("converted_client_id").references(() => clients.id, { onDelete: "set null" }), // newly created client
    commissionAmount: decimal("commission_amount", { precision: 10, scale: 2 }),
    paidAt: timestamp("paid_at", { withTimezone: true }),
    metadata: jsonb("metadata").$type<{
      source?: string;
      campaign?: string;
      notes?: string;
      contactHistory?: Array<{
        date: string;
        note: string;
        userId: string;
      }>;
    }>(),
    createdAt: timestamp("created_at", { withTimezone: true }).defaultNow().notNull(),
    updatedAt: timestamp("updated_at", { withTimezone: true }).defaultNow().notNull(),
  },
  (table) => ({
    partnerIdx: index("referrals_partner_idx").on(table.partnerId),
    statusIdx: index("referrals_status_idx").on(table.status),
    referralCodeIdx: index("referrals_code_idx").on(table.referralCode),
    convertedClientIdx: index("referrals_converted_client_idx").on(table.convertedClientId),
  }),
);

/**
 * Knowledge Base Categories table
 *
 * Organizes KB articles into categories with hierarchy support
 */
export const knowledgeBaseCategories = pgTable(
  "knowledge_base_categories",
  {
    id: uuid("id").primaryKey().defaultRandom(),
    name: text("name").notNull(),
    slug: text("slug").notNull().unique(),
    description: text("description"),
    parentId: uuid("parent_id").references((): any => knowledgeBaseCategories.id, { onDelete: "set null" }),
    position: integer("position").default(0).notNull(),
    icon: text("icon").default("book"),
    createdAt: timestamp("created_at", { withTimezone: true }).defaultNow().notNull(),
    updatedAt: timestamp("updated_at", { withTimezone: true }).defaultNow().notNull(),
  },
  (table) => ({
    slugIdx: uniqueIndex("kb_categories_slug_idx").on(table.slug),
    parentIdx: index("kb_categories_parent_idx").on(table.parentId),
  }),
);

/**
 * Knowledge Base Articles table
 *
 * Public-facing help articles for clients
 */
export const knowledgeBaseArticles = pgTable(
  "knowledge_base_articles",
  {
    id: uuid("id").primaryKey().defaultRandom(),
    categoryId: uuid("category_id")
      .notNull()
      .references(() => knowledgeBaseCategories.id, { onDelete: "cascade" }),
    title: text("title").notNull(),
    slug: text("slug").notNull().unique(),
    excerpt: text("excerpt"),
    content: text("content").notNull(), // Rich text/markdown
    videoUrl: text("video_url"),
    isPublished: boolean("is_published").default(true).notNull(),
    publishedAt: timestamp("published_at", { withTimezone: true }),
    viewCount: integer("view_count").default(0).notNull(),
    helpfulCount: integer("helpful_count").default(0).notNull(),
    createdBy: uuid("created_by").references(() => users.id, { onDelete: "set null" }),
    createdAt: timestamp("created_at", { withTimezone: true }).defaultNow().notNull(),
    updatedAt: timestamp("updated_at", { withTimezone: true }).defaultNow().notNull(),
  },
  (table) => ({
    slugIdx: uniqueIndex("kb_articles_slug_idx").on(table.slug),
    categoryIdx: index("kb_articles_category_idx").on(table.categoryId),
    publishedIdx: index("kb_articles_published_idx").on(table.isPublished),
  }),
);

/**
 * Knowledge Base Feedback table
 *
 * Tracks whether articles were helpful
 */
export const knowledgeBaseFeedback = pgTable(
  "knowledge_base_feedback",
  {
    id: uuid("id").primaryKey().defaultRandom(),
    articleId: uuid("article_id")
      .notNull()
      .references(() => knowledgeBaseArticles.id, { onDelete: "cascade" }),
    userId: uuid("user_id").references(() => users.id, { onDelete: "set null" }),
    wasHelpful: boolean("was_helpful").notNull(),
    comment: text("comment"),
    createdAt: timestamp("created_at", { withTimezone: true }).defaultNow().notNull(),
  },
  (table) => ({
    articleIdx: index("kb_feedback_article_idx").on(table.articleId),
  }),
);

/**
 * Staff Guide Categories table
 *
 * Organizes internal staff guides
 */
export const staffGuideCategories = pgTable(
  "staff_guide_categories",
  {
    id: uuid("id").primaryKey().defaultRandom(),
    name: text("name").notNull(),
    slug: text("slug").notNull().unique(),
    description: text("description"),
    icon: text("icon").default("book"),
    position: integer("position").default(0).notNull(),
    createdAt: timestamp("created_at", { withTimezone: true }).defaultNow().notNull(),
    updatedAt: timestamp("updated_at", { withTimezone: true }).defaultNow().notNull(),
  },
  (table) => ({
    slugIdx: uniqueIndex("staff_guide_categories_slug_idx").on(table.slug),
  }),
);

/**
 * Staff Guides table
 *
 * Internal guides for staff (SOPs, training materials)
 */
export const staffGuides = pgTable(
  "staff_guides",
  {
    id: uuid("id").primaryKey().defaultRandom(),
    categoryId: uuid("category_id")
      .notNull()
      .references(() => staffGuideCategories.id, { onDelete: "cascade" }),
    title: text("title").notNull(),
    slug: text("slug").notNull().unique(),
    summary: text("summary"),
    content: text("content").notNull(), // Rich text/markdown
    checklist: jsonb("checklist").$type<
      Array<{
        id: string;
        text: string;
        completed: boolean;
      }>
    >(),
    serviceTier: text("service_tier"), // e.g., 'local_seo', 'growth_seo'
    price: decimal("price", { precision: 10, scale: 2 }),
    commitment: text("commitment"), // e.g., '3-month minimum'
    tags: jsonb("tags").$type<string[]>().default([]),
    isInternal: boolean("is_internal").default(true).notNull(), // Staff-only vs client-facing
    isPublished: boolean("is_published").default(true).notNull(),
    viewCount: integer("view_count").default(0).notNull(),
    authorId: uuid("author_id").references(() => users.id, { onDelete: "set null" }),
    publishedAt: timestamp("published_at", { withTimezone: true }),
    createdAt: timestamp("created_at", { withTimezone: true }).defaultNow().notNull(),
    updatedAt: timestamp("updated_at", { withTimezone: true }).defaultNow().notNull(),
  },
  (table) => ({
    slugIdx: uniqueIndex("staff_guides_slug_idx").on(table.slug),
    categoryIdx: index("staff_guides_category_idx").on(table.categoryId),
    publishedIdx: index("staff_guides_published_idx").on(table.isPublished),
    serviceTierIdx: index("staff_guides_service_tier_idx").on(table.serviceTier),
  }),
);

/**
 * Staff Guide Views table
 *
 * Tracks staff guide views
 */
export const staffGuideViews = pgTable(
  "staff_guide_views",
  {
    id: uuid("id").primaryKey().defaultRandom(),
    guideId: uuid("guide_id")
      .notNull()
      .references(() => staffGuides.id, { onDelete: "cascade" }),
    userId: uuid("user_id").references(() => users.id, { onDelete: "set null" }),
    createdAt: timestamp("created_at", { withTimezone: true }).defaultNow().notNull(),
  },
  (table) => ({
    guideUserIdx: index("staff_guide_views_guide_user_idx").on(table.guideId, table.userId),
  }),
);

/**
 * Surveys table
 *
 * Client satisfaction and feedback surveys
 */
export const surveys = pgTable(
  "surveys",
  {
    id: uuid("id").primaryKey().defaultRandom(),
    clientId: uuid("client_id").references(() => clients.id, { onDelete: "cascade" }),
    title: text("title").notNull(),
    description: text("description"),
    isActive: boolean("is_active").default(true).notNull(),
    anonymousAllowed: boolean("anonymous_allowed").default(true).notNull(),
    responseCount: integer("response_count").default(0).notNull(),
    createdBy: uuid("created_by").references(() => users.id, { onDelete: "set null" }),
    createdAt: timestamp("created_at", { withTimezone: true }).defaultNow().notNull(),
    updatedAt: timestamp("updated_at", { withTimezone: true }).defaultNow().notNull(),
  },
  (table) => ({
    clientIdx: index("surveys_client_idx").on(table.clientId),
    activeIdx: index("surveys_active_idx").on(table.isActive),
  }),
);

/**
 * Survey Questions table
 *
 * Questions within surveys
 */
export const surveyQuestions = pgTable(
  "survey_questions",
  {
    id: uuid("id").primaryKey().defaultRandom(),
    surveyId: uuid("survey_id")
      .notNull()
      .references(() => surveys.id, { onDelete: "cascade" }),
    type: text("type", { enum: ["text", "rating", "nps", "multiple_choice", "checkbox"] })
      .default("text")
      .notNull(),
    prompt: text("prompt").notNull(),
    options: jsonb("options").$type<string[]>(), // for multiple choice/checkbox
    isRequired: boolean("is_required").default(true).notNull(),
    sortOrder: integer("sort_order").default(0).notNull(),
    createdAt: timestamp("created_at", { withTimezone: true }).defaultNow().notNull(),
    updatedAt: timestamp("updated_at", { withTimezone: true }).defaultNow().notNull(),
  },
  (table) => ({
    surveyIdx: index("survey_questions_survey_idx").on(table.surveyId),
  }),
);

/**
 * Survey Responses table
 *
 * Individual survey submissions
 */
export const surveyResponses = pgTable(
  "survey_responses",
  {
    id: uuid("id").primaryKey().defaultRandom(),
    surveyId: uuid("survey_id")
      .notNull()
      .references(() => surveys.id, { onDelete: "cascade" }),
    respondentId: uuid("respondent_id").references(() => users.id, { onDelete: "set null" }), // Can be null for anonymous
    clientId: uuid("client_id").references(() => clients.id, { onDelete: "set null" }),
    anonymousToken: text("anonymous_token").unique(),
    metadata: jsonb("metadata").$type<{
      ipAddress?: string;
      userAgent?: string;
      source?: string;
    }>(),
    submittedAt: timestamp("submitted_at", { withTimezone: true }),
    createdAt: timestamp("created_at", { withTimezone: true }).defaultNow().notNull(),
    updatedAt: timestamp("updated_at", { withTimezone: true }).defaultNow().notNull(),
  },
  (table) => ({
    surveyIdx: index("survey_responses_survey_idx").on(table.surveyId),
    respondentIdx: index("survey_responses_respondent_idx").on(table.respondentId),
  }),
);

/**
 * Survey Answers table
 *
 * Individual answers to survey questions
 */
export const surveyAnswers = pgTable(
  "survey_answers",
  {
    id: uuid("id").primaryKey().defaultRandom(),
    responseId: uuid("response_id")
      .notNull()
      .references(() => surveyResponses.id, { onDelete: "cascade" }),
    questionId: uuid("question_id")
      .notNull()
      .references(() => surveyQuestions.id, { onDelete: "cascade" }),
    value: text("value"), // Can store JSON for complex answers
    createdAt: timestamp("created_at", { withTimezone: true }).defaultNow().notNull(),
  },
  (table) => ({
    responseQuestionIdx: index("survey_answers_response_question_idx").on(table.responseId, table.questionId),
  }),
);

/**
 * Relations
 */
export const partnersRelations = relations(partners, ({ many }) => ({
  referrals: many(referrals),
}));

export const referralsRelations = relations(referrals, ({ one }) => ({
  partner: one(partners, {
    fields: [referrals.partnerId],
    references: [partners.id],
  }),
  client: one(clients, {
    fields: [referrals.clientId],
    references: [clients.id],
  }),
  convertedClient: one(clients, {
    fields: [referrals.convertedClientId],
    references: [clients.id],
  }),
}));

export const knowledgeBaseCategoriesRelations = relations(knowledgeBaseCategories, ({ one, many }) => ({
  parent: one(knowledgeBaseCategories, {
    fields: [knowledgeBaseCategories.parentId],
    references: [knowledgeBaseCategories.id],
  }),
  children: many(knowledgeBaseCategories),
  articles: many(knowledgeBaseArticles),
}));

export const knowledgeBaseArticlesRelations = relations(knowledgeBaseArticles, ({ one, many }) => ({
  category: one(knowledgeBaseCategories, {
    fields: [knowledgeBaseArticles.categoryId],
    references: [knowledgeBaseCategories.id],
  }),
  author: one(users, {
    fields: [knowledgeBaseArticles.createdBy],
    references: [users.id],
  }),
  feedback: many(knowledgeBaseFeedback),
}));

export const knowledgeBaseFeedbackRelations = relations(knowledgeBaseFeedback, ({ one }) => ({
  article: one(knowledgeBaseArticles, {
    fields: [knowledgeBaseFeedback.articleId],
    references: [knowledgeBaseArticles.id],
  }),
  user: one(users, {
    fields: [knowledgeBaseFeedback.userId],
    references: [users.id],
  }),
}));

export const staffGuideCategoriesRelations = relations(staffGuideCategories, ({ many }) => ({
  guides: many(staffGuides),
}));

export const staffGuidesRelations = relations(staffGuides, ({ one, many }) => ({
  category: one(staffGuideCategories, {
    fields: [staffGuides.categoryId],
    references: [staffGuideCategories.id],
  }),
  author: one(users, {
    fields: [staffGuides.authorId],
    references: [users.id],
  }),
  views: many(staffGuideViews),
}));

export const staffGuideViewsRelations = relations(staffGuideViews, ({ one }) => ({
  guide: one(staffGuides, {
    fields: [staffGuideViews.guideId],
    references: [staffGuides.id],
  }),
  user: one(users, {
    fields: [staffGuideViews.userId],
    references: [users.id],
  }),
}));

export const surveysRelations = relations(surveys, ({ one, many }) => ({
  client: one(clients, {
    fields: [surveys.clientId],
    references: [clients.id],
  }),
  creator: one(users, {
    fields: [surveys.createdBy],
    references: [users.id],
  }),
  questions: many(surveyQuestions),
  responses: many(surveyResponses),
}));

export const surveyQuestionsRelations = relations(surveyQuestions, ({ one, many }) => ({
  survey: one(surveys, {
    fields: [surveyQuestions.surveyId],
    references: [surveys.id],
  }),
  answers: many(surveyAnswers),
}));

export const surveyResponsesRelations = relations(surveyResponses, ({ one, many }) => ({
  survey: one(surveys, {
    fields: [surveyResponses.surveyId],
    references: [surveys.id],
  }),
  respondent: one(users, {
    fields: [surveyResponses.respondentId],
    references: [users.id],
  }),
  client: one(clients, {
    fields: [surveyResponses.clientId],
    references: [clients.id],
  }),
  answers: many(surveyAnswers),
}));

export const surveyAnswersRelations = relations(surveyAnswers, ({ one }) => ({
  response: one(surveyResponses, {
    fields: [surveyAnswers.responseId],
    references: [surveyResponses.id],
  }),
  question: one(surveyQuestions, {
    fields: [surveyAnswers.questionId],
    references: [surveyQuestions.id],
  }),
}));

/**
 * TypeScript types
 */
export type Partner = typeof partners.$inferSelect;
export type NewPartner = typeof partners.$inferInsert;

export type Referral = typeof referrals.$inferSelect;
export type NewReferral = typeof referrals.$inferInsert;

export type KnowledgeBaseCategory = typeof knowledgeBaseCategories.$inferSelect;
export type NewKnowledgeBaseCategory = typeof knowledgeBaseCategories.$inferInsert;

export type KnowledgeBaseArticle = typeof knowledgeBaseArticles.$inferSelect;
export type NewKnowledgeBaseArticle = typeof knowledgeBaseArticles.$inferInsert;

export type KnowledgeBaseFeedback = typeof knowledgeBaseFeedback.$inferSelect;
export type NewKnowledgeBaseFeedback = typeof knowledgeBaseFeedback.$inferInsert;

export type StaffGuideCategory = typeof staffGuideCategories.$inferSelect;
export type NewStaffGuideCategory = typeof staffGuideCategories.$inferInsert;

export type StaffGuide = typeof staffGuides.$inferSelect;
export type NewStaffGuide = typeof staffGuides.$inferInsert;

export type StaffGuideView = typeof staffGuideViews.$inferSelect;
export type NewStaffGuideView = typeof staffGuideViews.$inferInsert;

export type Survey = typeof surveys.$inferSelect;
export type NewSurvey = typeof surveys.$inferInsert;

export type SurveyQuestion = typeof surveyQuestions.$inferSelect;
export type NewSurveyQuestion = typeof surveyQuestions.$inferInsert;

export type SurveyResponse = typeof surveyResponses.$inferSelect;
export type NewSurveyResponse = typeof surveyResponses.$inferInsert;

export type SurveyAnswer = typeof surveyAnswers.$inferSelect;
export type NewSurveyAnswer = typeof surveyAnswers.$inferInsert;
