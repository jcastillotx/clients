import { pgTable, uuid, text, decimal, timestamp, integer, jsonb } from "drizzle-orm/pg-core";
import { relations } from "drizzle-orm";
import { clients } from "./clients";
import { users } from "./users";

/**
 * Project status enum
 */
export const projectStatusEnum = ["planning", "active", "on_hold", "completed", "cancelled"] as const;
export type ProjectStatus = (typeof projectStatusEnum)[number];

/**
 * Budget category enum
 */
export const budgetCategoryEnum = ["development", "design", "marketing", "infrastructure", "other"] as const;
export type BudgetCategory = (typeof budgetCategoryEnum)[number];

/**
 * Deliverable status enum
 */
export const deliverableStatusEnum = ["pending", "in_progress", "review", "completed", "rejected"] as const;
export type DeliverableStatus = (typeof deliverableStatusEnum)[number];

/**
 * Currency enum
 */
export const currencyEnum = ["USD", "EUR", "GBP", "CAD", "AUD"] as const;
export type Currency = (typeof currencyEnum)[number];

/**
 * Projects table
 *
 * Core project management with budget tracking, team assignments, and timeline management.
 */
export const projects = pgTable("projects", {
  id: uuid("id").primaryKey().defaultRandom(),
  clientId: uuid("client_id")
    .references(() => clients.id, { onDelete: "cascade" })
    .notNull(),
  name: text("name").notNull(),
  description: text("description"),
  status: text("status", { enum: projectStatusEnum }).default("planning").notNull(),
  startDate: timestamp("start_date", { withTimezone: true }),
  endDate: timestamp("end_date", { withTimezone: true }),
  estimatedHours: decimal("estimated_hours", { precision: 10, scale: 2 }),
  actualHours: decimal("actual_hours", { precision: 10, scale: 2 }).default("0"),
  budgetAmount: decimal("budget_amount", { precision: 12, scale: 2 }),
  spentAmount: decimal("spent_amount", { precision: 12, scale: 2 }).default("0"),
  currency: text("currency", { enum: currencyEnum }).default("USD").notNull(),
  projectManagerId: uuid("project_manager_id").references(() => users.id),
  progressPercent: integer("progress_percent").default(0),
  // Team members stored as JSONB array with roles and rates
  teamMembers: jsonb("team_members").$type<
    {
      userId: string;
      name: string;
      role: string;
      hourlyRate?: number;
    }[]
  >(),
  // Additional metadata
  metadata: jsonb("metadata").$type<{
    tags?: string[];
    priority?: "low" | "medium" | "high" | "critical";
    repository?: string;
    slackChannel?: string;
    notes?: string;
  }>(),
  createdAt: timestamp("created_at", { withTimezone: true }).defaultNow().notNull(),
  updatedAt: timestamp("updated_at", { withTimezone: true }).defaultNow().notNull(),
  deletedAt: timestamp("deleted_at", { withTimezone: true }),
});

/**
 * Project budgets table
 *
 * Breakdown of project budget by category for detailed expense tracking.
 */
export const projectBudgets = pgTable("project_budgets", {
  id: uuid("id").primaryKey().defaultRandom(),
  projectId: uuid("project_id")
    .references(() => projects.id, { onDelete: "cascade" })
    .notNull(),
  category: text("category", { enum: budgetCategoryEnum }).notNull(),
  allocatedAmount: decimal("allocated_amount", { precision: 12, scale: 2 }).notNull(),
  spentAmount: decimal("spent_amount", { precision: 12, scale: 2 }).default("0"),
  currency: text("currency", { enum: currencyEnum }).default("USD").notNull(),
  notes: text("notes"),
  createdAt: timestamp("created_at", { withTimezone: true }).defaultNow().notNull(),
  updatedAt: timestamp("updated_at", { withTimezone: true }).defaultNow().notNull(),
});

/**
 * Project cost entries table
 *
 * Individual cost/expense entries for tracking project spending.
 */
export const projectCostEntries = pgTable("project_cost_entries", {
  id: uuid("id").primaryKey().defaultRandom(),
  projectId: uuid("project_id")
    .references(() => projects.id, { onDelete: "cascade" })
    .notNull(),
  budgetId: uuid("budget_id").references(() => projectBudgets.id),
  userId: uuid("user_id").references(() => users.id),
  description: text("description").notNull(),
  amount: decimal("amount", { precision: 12, scale: 2 }).notNull(),
  entryDate: timestamp("entry_date", { withTimezone: true }).notNull(),
  approvedBy: uuid("approved_by").references(() => users.id),
  approvedAt: timestamp("approved_at", { withTimezone: true }),
  // Additional metadata for cost entry
  metadata: jsonb("metadata").$type<{
    receiptUrl?: string;
    invoiceNumber?: string;
    vendor?: string;
    category?: string;
  }>(),
  createdAt: timestamp("created_at", { withTimezone: true }).defaultNow().notNull(),
  updatedAt: timestamp("updated_at", { withTimezone: true }).defaultNow().notNull(),
});

/**
 * Project milestones table
 *
 * Major project milestones with progress tracking.
 */
export const projectMilestones = pgTable("project_milestones", {
  id: uuid("id").primaryKey().defaultRandom(),
  projectId: uuid("project_id")
    .references(() => projects.id, { onDelete: "cascade" })
    .notNull(),
  title: text("title").notNull(),
  description: text("description"),
  dueDate: timestamp("due_date", { withTimezone: true }),
  completedAt: timestamp("completed_at", { withTimezone: true }),
  completionPercentage: integer("completion_percentage").default(0),
  sortOrder: integer("sort_order").default(0),
  // Additional metadata
  metadata: jsonb("metadata").$type<{
    dependencies?: string[];
    assignedTo?: string[];
    notes?: string;
  }>(),
  createdAt: timestamp("created_at", { withTimezone: true }).defaultNow().notNull(),
  updatedAt: timestamp("updated_at", { withTimezone: true }).defaultNow().notNull(),
});

/**
 * Project deliverables table
 *
 * Specific deliverables within projects or milestones.
 */
export const projectDeliverables = pgTable("project_deliverables", {
  id: uuid("id").primaryKey().defaultRandom(),
  projectId: uuid("project_id")
    .references(() => projects.id, { onDelete: "cascade" })
    .notNull(),
  milestoneId: uuid("milestone_id").references(() => projectMilestones.id),
  title: text("title").notNull(),
  description: text("description"),
  status: text("status", { enum: deliverableStatusEnum }).default("pending").notNull(),
  dueDate: timestamp("due_date", { withTimezone: true }),
  deliveredAt: timestamp("delivered_at", { withTimezone: true }),
  documentId: uuid("document_id"),
  sortOrder: integer("sort_order").default(0),
  // Additional metadata
  metadata: jsonb("metadata").$type<{
    assignedTo?: string;
    reviewers?: string[];
    attachments?: string[];
    checklistItems?: { text: string; completed: boolean }[];
  }>(),
  createdAt: timestamp("created_at", { withTimezone: true }).defaultNow().notNull(),
  updatedAt: timestamp("updated_at", { withTimezone: true }).defaultNow().notNull(),
});

/**
 * Project relations
 */
export const projectsRelations = relations(projects, ({ one, many }) => ({
  client: one(clients, {
    fields: [projects.clientId],
    references: [clients.id],
  }),
  projectManager: one(users, {
    fields: [projects.projectManagerId],
    references: [users.id],
  }),
  budgets: many(projectBudgets),
  costEntries: many(projectCostEntries),
  milestones: many(projectMilestones),
  deliverables: many(projectDeliverables),
}));

export const projectBudgetsRelations = relations(projectBudgets, ({ one, many }) => ({
  project: one(projects, {
    fields: [projectBudgets.projectId],
    references: [projects.id],
  }),
  costEntries: many(projectCostEntries),
}));

export const projectCostEntriesRelations = relations(projectCostEntries, ({ one }) => ({
  project: one(projects, {
    fields: [projectCostEntries.projectId],
    references: [projects.id],
  }),
  budget: one(projectBudgets, {
    fields: [projectCostEntries.budgetId],
    references: [projectBudgets.id],
  }),
  user: one(users, {
    fields: [projectCostEntries.userId],
    references: [users.id],
  }),
  approver: one(users, {
    fields: [projectCostEntries.approvedBy],
    references: [users.id],
  }),
}));

export const projectMilestonesRelations = relations(projectMilestones, ({ one, many }) => ({
  project: one(projects, {
    fields: [projectMilestones.projectId],
    references: [projects.id],
  }),
  deliverables: many(projectDeliverables),
}));

export const projectDeliverablesRelations = relations(projectDeliverables, ({ one }) => ({
  project: one(projects, {
    fields: [projectDeliverables.projectId],
    references: [projects.id],
  }),
  milestone: one(projectMilestones, {
    fields: [projectDeliverables.milestoneId],
    references: [projectMilestones.id],
  }),
}));

/**
 * TypeScript types
 */
export type Project = typeof projects.$inferSelect;
export type NewProject = typeof projects.$inferInsert;
export type ProjectBudget = typeof projectBudgets.$inferSelect;
export type NewProjectBudget = typeof projectBudgets.$inferInsert;
export type ProjectCostEntry = typeof projectCostEntries.$inferSelect;
export type NewProjectCostEntry = typeof projectCostEntries.$inferInsert;
export type ProjectMilestone = typeof projectMilestones.$inferSelect;
export type NewProjectMilestone = typeof projectMilestones.$inferInsert;
export type ProjectDeliverable = typeof projectDeliverables.$inferSelect;
export type NewProjectDeliverable = typeof projectDeliverables.$inferInsert;
