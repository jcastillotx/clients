import { pgTable, uuid, text, timestamp, jsonb, boolean, integer, bigint } from "drizzle-orm/pg-core";
import { relations } from "drizzle-orm";

/**
 * Automation trigger types
 */
export const automationTriggerTypes = [
  "request.created",
  "request.updated",
  "request.status_changed",
  "invoice.created",
  "invoice.paid",
  "invoice.overdue",
  "project.created",
  "project.status_changed",
  "meeting.scheduled",
  "ticket.created",
  "schedule.daily",
  "schedule.weekly",
  "schedule.monthly",
] as const;

export type AutomationTriggerType = (typeof automationTriggerTypes)[number];

/**
 * Automation run status
 */
export const automationStatusEnum = ["pending", "running", "completed", "failed"] as const;
export type AutomationStatus = (typeof automationStatusEnum)[number];

/**
 * Automation Rules table
 *
 * Defines workflow automation rules with triggers, conditions, and actions.
 */
export const automationRules = pgTable("automation_rules", {
  id: uuid("id").primaryKey().defaultRandom(),
  name: text("name").notNull(),
  description: text("description"),
  trigger: text("trigger").notNull(),
  conditions: jsonb("conditions").$type<{
    operator?: "and" | "or";
    rules?: Array<{
      field: string;
      operator: "equals" | "not_equals" | "contains" | "greater_than" | "less_than";
      value: any;
    }>;
  }>(),
  actions: jsonb("actions")
    .$type<
      Array<{
        type: "send_email" | "create_task" | "update_status" | "send_notification" | "webhook";
        config: {
          template?: string;
          recipients?: string[];
          url?: string;
          status?: string;
          message?: string;
          [key: string]: any;
        };
      }>
    >()
    .notNull(),
  isActive: boolean("is_active").default(true).notNull(),
  sortOrder: integer("sort_order").default(0).notNull(),
  runCount: integer("run_count").default(0).notNull(),
  lastRunAt: timestamp("last_run_at", { withTimezone: true }),
  createdBy: uuid("created_by").references(() => users.id, { onDelete: "set null" }),
  updatedBy: uuid("updated_by").references(() => users.id, { onDelete: "set null" }),
  createdAt: timestamp("created_at", { withTimezone: true }).defaultNow().notNull(),
  updatedAt: timestamp("updated_at", { withTimezone: true }).defaultNow().notNull(),
});

/**
 * Automation Runs table
 *
 * Tracks individual executions of automation rules.
 */
export const automationRuns = pgTable("automation_runs", {
  id: uuid("id").primaryKey().defaultRandom(),
  ruleId: uuid("automation_rule_id")
    .notNull()
    .references(() => automationRules.id, { onDelete: "cascade" }),
  trigger: text("trigger").notNull(),
  clientId: uuid("client_id"),
  context: jsonb("context").$type<{
    entity?: string;
    entityId?: string;
    data?: Record<string, any>;
    [key: string]: any;
  }>(),
  matched: boolean("matched").default(false).notNull(),
  succeeded: boolean("succeeded").default(false).notNull(),
  actionsTotal: integer("actions_total").default(0).notNull(),
  actionsSucceeded: integer("actions_succeeded").default(0).notNull(),
  actionsFailed: integer("actions_failed").default(0).notNull(),
  error: text("error"),
  ranAt: timestamp("ran_at", { withTimezone: true }).defaultNow().notNull(),
  createdAt: timestamp("created_at", { withTimezone: true }).defaultNow().notNull(),
  updatedAt: timestamp("updated_at", { withTimezone: true }).defaultNow().notNull(),
});

/**
 * Automation Logs table
 *
 * Detailed execution logs for debugging and auditing.
 */
export const automationLogs = pgTable("automation_logs", {
  id: uuid("id").primaryKey().defaultRandom(),
  ruleId: uuid("automation_rule_id")
    .notNull()
    .references(() => automationRules.id, { onDelete: "cascade" }),
  trigger: text("trigger").notNull(),
  status: text("status", { enum: automationStatusEnum }).notNull(),
  message: text("message"),
  context: jsonb("context").$type<Record<string, any>>(),
  startedAt: timestamp("started_at", { withTimezone: true }).notNull(),
  finishedAt: timestamp("finished_at", { withTimezone: true }),
  createdAt: timestamp("created_at", { withTimezone: true }).defaultNow().notNull(),
  updatedAt: timestamp("updated_at", { withTimezone: true }).defaultNow().notNull(),
});

/**
 * Report Templates table
 *
 * Configurable report templates with custom sections and formatting.
 */
export const reportTemplates = pgTable("report_templates", {
  id: uuid("id").primaryKey().defaultRandom(),
  name: text("name").notNull(),
  description: text("description"),
  reportType: text("report_type").notNull(), // marketing, seo, project, financial
  config: jsonb("config").$type<{
    dateRange?: string;
    filters?: Record<string, any>;
    groupBy?: string[];
    metrics?: string[];
    charts?: Array<{
      type: string;
      data: string;
      options?: Record<string, any>;
    }>;
  }>(),
  sections: jsonb("sections").$type<
    Array<{
      id: string;
      title: string;
      type: "chart" | "table" | "text" | "metric" | "custom";
      config: Record<string, any>;
      order: number;
    }>
  >(),
  createdBy: uuid("created_by").references(() => users.id, { onDelete: "set null" }),
  createdAt: timestamp("created_at", { withTimezone: true }).defaultNow().notNull(),
  updatedAt: timestamp("updated_at", { withTimezone: true }).defaultNow().notNull(),
});

/**
 * Report Schedules table
 *
 * Scheduled automatic report generation and delivery.
 */
export const reportSchedules = pgTable("report_schedules", {
  id: uuid("id").primaryKey().defaultRandom(),
  templateId: uuid("report_template_id")
    .notNull()
    .references(() => reportTemplates.id, { onDelete: "cascade" }),
  clientId: uuid("client_id"),
  name: text("name").notNull(),
  frequency: text("frequency").notNull(), // daily, weekly, monthly, quarterly
  recipients: jsonb("recipients")
    .$type<
      Array<{
        email: string;
        name?: string;
        type: "to" | "cc" | "bcc";
      }>
    >()
    .notNull(),
  config: jsonb("config").$type<{
    timezone?: string;
    timeOfDay?: string;
    dayOfWeek?: number;
    dayOfMonth?: number;
    format?: "pdf" | "excel" | "html";
  }>(),
  isActive: boolean("is_active").default(true).notNull(),
  nextRunAt: timestamp("next_run_at", { withTimezone: true }),
  lastRunAt: timestamp("last_run_at", { withTimezone: true }),
  lastError: text("last_error"),
  createdBy: uuid("created_by").references(() => users.id, { onDelete: "set null" }),
  createdAt: timestamp("created_at", { withTimezone: true }).defaultNow().notNull(),
  updatedAt: timestamp("updated_at", { withTimezone: true }).defaultNow().notNull(),
});

/**
 * Report Deliveries table
 *
 * Tracks generated and sent reports.
 */
export const reportDeliveries = pgTable("report_deliveries", {
  id: uuid("id").primaryKey().defaultRandom(),
  scheduleId: uuid("report_schedule_id").references(() => reportSchedules.id, {
    onDelete: "set null",
  }),
  templateId: uuid("report_template_id")
    .notNull()
    .references(() => reportTemplates.id, { onDelete: "cascade" }),
  clientId: uuid("client_id"),
  category: text("category"), // marketing, seo, project, financial
  meta: jsonb("meta").$type<{
    reportName?: string;
    dateRange?: { start: string; end: string };
    filters?: Record<string, any>;
  }>(),
  disk: text("disk").default("public"),
  path: text("path").notNull(),
  recipients: jsonb("recipients").$type<string[]>(),
  generatedAt: timestamp("generated_at", { withTimezone: true }).notNull(),
  sentAt: timestamp("sent_at", { withTimezone: true }),
  status: text("status").notNull(), // generated, sent, failed
  error: text("error"),
  createdAt: timestamp("created_at", { withTimezone: true }).defaultNow().notNull(),
  updatedAt: timestamp("updated_at", { withTimezone: true }).defaultNow().notNull(),
});

/**
 * Custom Dashboards table
 *
 * User-created custom dashboard layouts with widgets.
 */
export const customDashboards = pgTable("custom_dashboards", {
  id: uuid("id").primaryKey().defaultRandom(),
  userId: uuid("user_id").references(() => users.id, { onDelete: "cascade" }),
  clientId: uuid("client_id"),
  name: text("dashboard_name").notNull(),
  layout: jsonb("layout").$type<{
    type?: "grid" | "flex";
    columns?: number;
    gap?: number;
    responsive?: boolean;
  }>(),
  widgets: jsonb("widgets").$type<
    Array<{
      id: string;
      type: "metric" | "chart" | "table" | "list" | "calendar" | "activity";
      title: string;
      config: {
        dataSource?: string;
        metric?: string;
        chartType?: "line" | "bar" | "pie" | "area";
        filters?: Record<string, any>;
        refreshInterval?: number;
      };
      position: {
        x: number;
        y: number;
        w: number;
        h: number;
      };
    }>
  >(),
  isDefault: boolean("is_default").default(false).notNull(),
  meta: jsonb("meta").$type<{
    description?: string;
    tags?: string[];
    shared?: boolean;
    theme?: string;
  }>(),
  createdBy: uuid("created_by").references(() => users.id, { onDelete: "set null" }),
  createdAt: timestamp("created_at", { withTimezone: true }).defaultNow().notNull(),
  updatedAt: timestamp("updated_at", { withTimezone: true }).defaultNow().notNull(),
});

/**
 * Relations
 */
export const automationRulesRelations = relations(automationRules, ({ many, one }) => ({
  runs: many(automationRuns),
  logs: many(automationLogs),
  creator: one(users, {
    fields: [automationRules.createdBy],
    references: [users.id],
  }),
}));

export const automationRunsRelations = relations(automationRuns, ({ one }) => ({
  rule: one(automationRules, {
    fields: [automationRuns.ruleId],
    references: [automationRules.id],
  }),
}));

export const automationLogsRelations = relations(automationLogs, ({ one }) => ({
  rule: one(automationRules, {
    fields: [automationLogs.ruleId],
    references: [automationRules.id],
  }),
}));

export const reportTemplatesRelations = relations(reportTemplates, ({ many, one }) => ({
  schedules: many(reportSchedules),
  deliveries: many(reportDeliveries),
  creator: one(users, {
    fields: [reportTemplates.createdBy],
    references: [users.id],
  }),
}));

export const reportSchedulesRelations = relations(reportSchedules, ({ one, many }) => ({
  template: one(reportTemplates, {
    fields: [reportSchedules.templateId],
    references: [reportTemplates.id],
  }),
  deliveries: many(reportDeliveries),
  creator: one(users, {
    fields: [reportSchedules.createdBy],
    references: [users.id],
  }),
}));

export const reportDeliveriesRelations = relations(reportDeliveries, ({ one }) => ({
  schedule: one(reportSchedules, {
    fields: [reportDeliveries.scheduleId],
    references: [reportSchedules.id],
  }),
  template: one(reportTemplates, {
    fields: [reportDeliveries.templateId],
    references: [reportTemplates.id],
  }),
}));

export const customDashboardsRelations = relations(customDashboards, ({ one }) => ({
  user: one(users, {
    fields: [customDashboards.userId],
    references: [users.id],
  }),
  creator: one(users, {
    fields: [customDashboards.createdBy],
    references: [users.id],
  }),
}));

/**
 * TypeScript types
 */
export type AutomationRule = typeof automationRules.$inferSelect;
export type NewAutomationRule = typeof automationRules.$inferInsert;

export type AutomationRun = typeof automationRuns.$inferSelect;
export type NewAutomationRun = typeof automationRuns.$inferInsert;

export type AutomationLog = typeof automationLogs.$inferSelect;
export type NewAutomationLog = typeof automationLogs.$inferInsert;

export type ReportTemplate = typeof reportTemplates.$inferSelect;
export type NewReportTemplate = typeof reportTemplates.$inferInsert;

export type ReportSchedule = typeof reportSchedules.$inferSelect;
export type NewReportSchedule = typeof reportSchedules.$inferInsert;

export type ReportDelivery = typeof reportDeliveries.$inferSelect;
export type NewReportDelivery = typeof reportDeliveries.$inferInsert;

export type CustomDashboard = typeof customDashboards.$inferSelect;
export type NewCustomDashboard = typeof customDashboards.$inferInsert;

// Import users for relations
import { users } from "./users";
