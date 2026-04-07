import { pgTable, uuid, text, timestamp, boolean, jsonb, integer } from "drizzle-orm/pg-core";
import { relations } from "drizzle-orm";
import { users } from "./users";

/**
 * Project task template category enum
 */
export const templateCategoryEnum = [
  "web_development",
  "marketing",
  "design",
  "seo",
  "maintenance",
  "migration",
  "launch",
  "general",
] as const;
export type TemplateCategory = (typeof templateCategoryEnum)[number];

/**
 * Project Task Templates table
 *
 * Reusable task checklist templates that can be applied to projects.
 * Each template contains a structured list of phases with tasks and subtasks.
 */
export const projectTaskTemplates = pgTable("project_task_templates", {
  id: uuid("id").primaryKey().defaultRandom(),
  name: text("name").notNull(),
  description: text("description"),
  category: text("category", { enum: templateCategoryEnum }).default("general").notNull(),
  icon: text("icon").default("ClipboardList"),
  color: text("color").default("#3b82f6"),
  estimatedHours: integer("estimated_hours"),
  isSystem: boolean("is_system").default(false).notNull(),
  isActive: boolean("is_active").default(true).notNull(),
  createdBy: uuid("created_by").references(() => users.id),
  /**
   * Template structure stored as JSONB:
   * Array of phases, each containing tasks with optional subtasks/checklists.
   */
  phases: jsonb("phases").$type<ProjectTemplatePhase[]>().notNull(),
  metadata: jsonb("metadata").$type<{
    tags?: string[];
    version?: string;
    source?: string;
  }>(),
  createdAt: timestamp("created_at", { withTimezone: true }).defaultNow().notNull(),
  updatedAt: timestamp("updated_at", { withTimezone: true }).defaultNow().notNull(),
  deletedAt: timestamp("deleted_at", { withTimezone: true }),
});

/**
 * Template phase structure
 */
export interface ProjectTemplatePhase {
  name: string;
  description?: string;
  sortOrder: number;
  tasks: ProjectTemplateTask[];
}

/**
 * Template task structure
 */
export interface ProjectTemplateTask {
  title: string;
  description?: string;
  priority?: "low" | "normal" | "high" | "urgent";
  estimatedHours?: number;
  sortOrder: number;
  checklist?: ProjectTemplateChecklistItem[];
  labels?: string[];
}

/**
 * Template checklist item structure
 */
export interface ProjectTemplateChecklistItem {
  title: string;
  sortOrder: number;
}

/**
 * Relations
 */
export const projectTaskTemplatesRelations = relations(projectTaskTemplates, ({ one }) => ({
  creator: one(users, {
    fields: [projectTaskTemplates.createdBy],
    references: [users.id],
  }),
}));

/**
 * TypeScript types
 */
export type ProjectTaskTemplate = typeof projectTaskTemplates.$inferSelect;
export type NewProjectTaskTemplate = typeof projectTaskTemplates.$inferInsert;
