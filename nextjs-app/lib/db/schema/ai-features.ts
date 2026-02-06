import { pgTable, uuid, text, timestamp, jsonb, integer, decimal, boolean, pgEnum } from "drizzle-orm/pg-core";
import { relations } from "drizzle-orm";
import { users } from "./users";
import { clients } from "./clients";

/**
 * AI Features Schema
 *
 * Complete AI functionality including conversations, tasks, workflows,
 * providers, usage tracking, and prompt templates.
 */

// Enums
export const aiMessageRoleEnum = pgEnum("ai_message_role", ["user", "assistant", "system", "tool"]);
export const aiTaskStatusEnum = pgEnum("ai_task_status", ["pending", "processing", "completed", "failed", "cancelled"]);
export const aiTaskTypeEnum = pgEnum("ai_task_type", [
  "text_generation",
  "code_generation",
  "analysis",
  "summarization",
  "translation",
  "qa",
  "custom",
]);
export const aiTriggerTypeEnum = pgEnum("ai_trigger_type", ["manual", "scheduled", "event", "webhook", "api"]);
export const aiProviderTypeEnum = pgEnum("ai_provider_type", [
  "openai",
  "anthropic",
  "google",
  "azure",
  "local",
  "custom",
]);
export const aiPromptCategoryEnum = pgEnum("ai_prompt_category", [
  "general",
  "customer_service",
  "content_creation",
  "code_generation",
  "data_analysis",
  "custom",
]);

/**
 * AI Conversations
 *
 * Stores conversation threads between users and AI assistants.
 */
export const aiConversations = pgTable("ai_conversations", {
  id: uuid("id").primaryKey().defaultRandom(),
  userId: uuid("user_id")
    .notNull()
    .references(() => users.id, { onDelete: "cascade" }),
  clientId: uuid("client_id").references(() => clients.id, { onDelete: "cascade" }),
  title: text("title").notNull(),
  context: jsonb("context").$type<{
    systemPrompt?: string;
    temperature?: number;
    maxTokens?: number;
    topP?: number;
    frequencyPenalty?: number;
    presencePenalty?: number;
    customSettings?: Record<string, any>;
  }>(),
  metadata: jsonb("metadata").$type<{
    tags?: string[];
    category?: string;
    isArchived?: boolean;
    isPinned?: boolean;
    lastModelUsed?: string;
    totalTokensUsed?: number;
    totalCost?: number;
  }>(),
  createdAt: timestamp("created_at", { withTimezone: true }).defaultNow().notNull(),
  updatedAt: timestamp("updated_at", { withTimezone: true }).defaultNow().notNull(),
});

/**
 * AI Messages
 *
 * Individual messages within conversations.
 */
export const aiMessages = pgTable("ai_messages", {
  id: uuid("id").primaryKey().defaultRandom(),
  conversationId: uuid("conversation_id")
    .notNull()
    .references(() => aiConversations.id, { onDelete: "cascade" }),
  role: aiMessageRoleEnum("role").notNull(),
  content: text("content").notNull(),
  tokensUsed: integer("tokens_used").default(0),
  model: text("model"), // e.g., "gpt-4", "claude-3-opus"
  cost: decimal("cost", { precision: 10, scale: 6 }), // Cost in USD
  metadata: jsonb("metadata").$type<{
    functionCall?: {
      name: string;
      arguments: Record<string, any>;
    };
    toolCalls?: Array<{
      id: string;
      type: string;
      function: {
        name: string;
        arguments: string;
      };
    }>;
    responseTime?: number; // milliseconds
    error?: string;
    attachments?: Array<{
      type: string;
      url: string;
      name: string;
    }>;
  }>(),
  createdAt: timestamp("created_at", { withTimezone: true }).defaultNow().notNull(),
  updatedAt: timestamp("updated_at", { withTimezone: true }).defaultNow().notNull(),
});

/**
 * AI Message Feedback
 *
 * User feedback on AI-generated messages for quality improvement.
 */
export const aiMessageFeedback = pgTable("ai_message_feedback", {
  id: uuid("id").primaryKey().defaultRandom(),
  messageId: uuid("message_id")
    .notNull()
    .references(() => aiMessages.id, { onDelete: "cascade" }),
  userId: uuid("user_id")
    .notNull()
    .references(() => users.id, { onDelete: "cascade" }),
  rating: integer("rating"), // 1-5 star rating
  feedbackType: text("feedback_type"), // "helpful", "not_helpful", "incorrect", "offensive"
  comment: text("comment"),
  metadata: jsonb("metadata").$type<{
    improvedResponse?: string;
    tags?: string[];
  }>(),
  createdAt: timestamp("created_at", { withTimezone: true }).defaultNow().notNull(),
});

/**
 * AI Tasks
 *
 * Background AI tasks and their execution status.
 */
export const aiTasks = pgTable("ai_tasks", {
  id: uuid("id").primaryKey().defaultRandom(),
  clientId: uuid("client_id").references(() => clients.id, { onDelete: "cascade" }),
  userId: uuid("user_id").references(() => users.id, { onDelete: "set null" }),
  taskType: aiTaskTypeEnum("task_type").notNull(),
  input: jsonb("input")
    .$type<{
      prompt?: string;
      parameters?: Record<string, any>;
      files?: string[];
    }>()
    .notNull(),
  output: jsonb("output").$type<{
    result?: any;
    confidence?: number;
    alternatives?: any[];
  }>(),
  status: aiTaskStatusEnum("status").default("pending").notNull(),
  startedAt: timestamp("started_at", { withTimezone: true }),
  completedAt: timestamp("completed_at", { withTimezone: true }),
  error: text("error"),
  metadata: jsonb("metadata").$type<{
    model?: string;
    tokensUsed?: number;
    cost?: number;
    retryCount?: number;
    priority?: number;
    webhookUrl?: string;
  }>(),
  createdAt: timestamp("created_at", { withTimezone: true }).defaultNow().notNull(),
  updatedAt: timestamp("updated_at", { withTimezone: true }).defaultNow().notNull(),
});

/**
 * AI Workflows
 *
 * Automated multi-step AI workflows.
 */
export const aiWorkflows = pgTable("ai_workflows", {
  id: uuid("id").primaryKey().defaultRandom(),
  clientId: uuid("client_id").references(() => clients.id, { onDelete: "cascade" }),
  name: text("name").notNull(),
  description: text("description"),
  triggerType: aiTriggerTypeEnum("trigger_type").notNull(),
  steps: jsonb("steps")
    .$type<
      Array<{
        id: string;
        type: "ai_task" | "condition" | "action" | "delay";
        config: {
          taskType?: string;
          prompt?: string;
          model?: string;
          condition?: string;
          action?: string;
          delaySeconds?: number;
          [key: string]: any;
        };
        nextStepId?: string;
        onError?: string;
      }>
    >()
    .notNull(),
  isActive: boolean("is_active").default(true).notNull(),
  runCount: integer("run_count").default(0),
  createdBy: uuid("created_by").references(() => users.id, { onDelete: "set null" }),
  metadata: jsonb("metadata").$type<{
    schedule?: {
      cron?: string;
      timezone?: string;
    };
    eventTrigger?: {
      event: string;
      filters?: Record<string, any>;
    };
    lastRun?: {
      timestamp: string;
      status: string;
      duration: number;
    };
    tags?: string[];
  }>(),
  createdAt: timestamp("created_at", { withTimezone: true }).defaultNow().notNull(),
  updatedAt: timestamp("updated_at", { withTimezone: true }).defaultNow().notNull(),
});

/**
 * AI Providers
 *
 * Configuration for different AI service providers.
 */
export const aiProviders = pgTable("ai_providers", {
  id: uuid("id").primaryKey().defaultRandom(),
  name: text("name").notNull().unique(),
  providerType: aiProviderTypeEnum("provider_type").notNull(),
  apiKeyEncrypted: text("api_key_encrypted"), // Encrypted API key
  config: jsonb("config").$type<{
    baseUrl?: string;
    defaultModel?: string;
    maxTokens?: number;
    temperature?: number;
    timeout?: number;
    retryAttempts?: number;
    rateLimits?: {
      requestsPerMinute?: number;
      tokensPerMinute?: number;
    };
    customHeaders?: Record<string, string>;
  }>(),
  isActive: boolean("is_active").default(true).notNull(),
  metadata: jsonb("metadata").$type<{
    description?: string;
    supportedModels?: string[];
    features?: string[];
    pricingTier?: string;
  }>(),
  createdAt: timestamp("created_at", { withTimezone: true }).defaultNow().notNull(),
  updatedAt: timestamp("updated_at", { withTimezone: true }).defaultNow().notNull(),
});

/**
 * AI Usage Tracking
 *
 * Track token usage and costs per user/client.
 */
export const aiUsageTracking = pgTable("ai_usage_tracking", {
  id: uuid("id").primaryKey().defaultRandom(),
  userId: uuid("user_id").references(() => users.id, { onDelete: "cascade" }),
  clientId: uuid("client_id").references(() => clients.id, { onDelete: "cascade" }),
  provider: text("provider").notNull(),
  model: text("model").notNull(),
  tokensUsed: integer("tokens_used").notNull(),
  cost: decimal("cost", { precision: 10, scale: 6 }).notNull(),
  requestType: text("request_type"), // "chat", "completion", "embedding", "function_call"
  metadata: jsonb("metadata").$type<{
    conversationId?: string;
    taskId?: string;
    workflowId?: string;
    duration?: number;
    promptTokens?: number;
    completionTokens?: number;
    cached?: boolean;
  }>(),
  createdAt: timestamp("created_at", { withTimezone: true }).defaultNow().notNull(),
});

/**
 * AI Insight Reports
 *
 * Generated insights and analytics reports.
 */
export const aiInsightReports = pgTable("ai_insight_reports", {
  id: uuid("id").primaryKey().defaultRandom(),
  clientId: uuid("client_id").references(() => clients.id, { onDelete: "cascade" }),
  reportType: text("report_type").notNull(), // "usage_summary", "cost_analysis", "performance_metrics"
  generatedAt: timestamp("generated_at", { withTimezone: true }).defaultNow().notNull(),
  data: jsonb("data")
    .$type<{
      summary?: Record<string, any>;
      charts?: Array<{
        type: string;
        data: any[];
        config: Record<string, any>;
      }>;
      recommendations?: string[];
      metrics?: Record<string, number>;
    }>()
    .notNull(),
  metadata: jsonb("metadata").$type<{
    periodStart?: string;
    periodEnd?: string;
    filters?: Record<string, any>;
  }>(),
});

/**
 * Prompt Templates
 *
 * Reusable prompt templates for common tasks.
 */
export const promptTemplates = pgTable("prompt_templates", {
  id: uuid("id").primaryKey().defaultRandom(),
  name: text("name").notNull(),
  description: text("description"),
  templateContent: text("template_content").notNull(),
  variables: jsonb("variables").$type<
    Array<{
      name: string;
      type: "string" | "number" | "boolean" | "array" | "object";
      description?: string;
      required?: boolean;
      defaultValue?: any;
      validation?: {
        pattern?: string;
        min?: number;
        max?: number;
        enum?: any[];
      };
    }>
  >(),
  category: aiPromptCategoryEnum("category").default("general").notNull(),
  createdBy: uuid("created_by").references(() => users.id, { onDelete: "set null" }),
  metadata: jsonb("metadata").$type<{
    tags?: string[];
    isPublic?: boolean;
    usageCount?: number;
    averageRating?: number;
    version?: string;
    exampleOutput?: string;
  }>(),
  createdAt: timestamp("created_at", { withTimezone: true }).defaultNow().notNull(),
  updatedAt: timestamp("updated_at", { withTimezone: true }).defaultNow().notNull(),
});

// Relations
export const aiConversationsRelations = relations(aiConversations, ({ one, many }) => ({
  user: one(users, { fields: [aiConversations.userId], references: [users.id] }),
  client: one(clients, { fields: [aiConversations.clientId], references: [clients.id] }),
  messages: many(aiMessages),
}));

export const aiMessagesRelations = relations(aiMessages, ({ one, many }) => ({
  conversation: one(aiConversations, { fields: [aiMessages.conversationId], references: [aiConversations.id] }),
  feedback: many(aiMessageFeedback),
}));

export const aiMessageFeedbackRelations = relations(aiMessageFeedback, ({ one }) => ({
  message: one(aiMessages, { fields: [aiMessageFeedback.messageId], references: [aiMessages.id] }),
  user: one(users, { fields: [aiMessageFeedback.userId], references: [users.id] }),
}));

export const aiTasksRelations = relations(aiTasks, ({ one }) => ({
  client: one(clients, { fields: [aiTasks.clientId], references: [clients.id] }),
  user: one(users, { fields: [aiTasks.userId], references: [users.id] }),
}));

export const aiWorkflowsRelations = relations(aiWorkflows, ({ one }) => ({
  client: one(clients, { fields: [aiWorkflows.clientId], references: [clients.id] }),
  createdByUser: one(users, { fields: [aiWorkflows.createdBy], references: [users.id] }),
}));

export const aiUsageTrackingRelations = relations(aiUsageTracking, ({ one }) => ({
  user: one(users, { fields: [aiUsageTracking.userId], references: [users.id] }),
  client: one(clients, { fields: [aiUsageTracking.clientId], references: [clients.id] }),
}));

export const aiInsightReportsRelations = relations(aiInsightReports, ({ one }) => ({
  client: one(clients, { fields: [aiInsightReports.clientId], references: [clients.id] }),
}));

export const promptTemplatesRelations = relations(promptTemplates, ({ one }) => ({
  createdByUser: one(users, { fields: [promptTemplates.createdBy], references: [users.id] }),
}));

// TypeScript types
export type AiConversation = typeof aiConversations.$inferSelect;
export type NewAiConversation = typeof aiConversations.$inferInsert;
export type AiMessage = typeof aiMessages.$inferSelect;
export type NewAiMessage = typeof aiMessages.$inferInsert;
export type AiMessageFeedback = typeof aiMessageFeedback.$inferSelect;
export type NewAiMessageFeedback = typeof aiMessageFeedback.$inferInsert;
export type AiTask = typeof aiTasks.$inferSelect;
export type NewAiTask = typeof aiTasks.$inferInsert;
export type AiWorkflow = typeof aiWorkflows.$inferSelect;
export type NewAiWorkflow = typeof aiWorkflows.$inferInsert;
export type AiProvider = typeof aiProviders.$inferSelect;
export type NewAiProvider = typeof aiProviders.$inferInsert;
export type AiUsageTracking = typeof aiUsageTracking.$inferSelect;
export type NewAiUsageTracking = typeof aiUsageTracking.$inferInsert;
export type AiInsightReport = typeof aiInsightReports.$inferSelect;
export type NewAiInsightReport = typeof aiInsightReports.$inferInsert;
export type PromptTemplate = typeof promptTemplates.$inferSelect;
export type NewPromptTemplate = typeof promptTemplates.$inferInsert;
