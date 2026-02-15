import { pgTable, uuid, text, timestamp, jsonb } from "drizzle-orm/pg-core";
import { clients } from "./clients";
import { users } from "./users";

/**
 * Activity Logs table
 *
 * Tracks user activity and system events (payments, updates, etc.)
 * Used by admin dashboard, Stripe webhooks, and reporting widgets.
 */
export const activityLogs = pgTable("activity_logs", {
  id: uuid("id").primaryKey().defaultRandom(),
  clientId: uuid("client_id").references(() => clients.id, { onDelete: "cascade" }),
  causerId: uuid("causer_id").references(() => users.id, { onDelete: "set null" }),
  subjectType: text("subject_type").notNull(),
  subjectId: uuid("subject_id"),
  description: text("description").notNull(),
  properties: jsonb("properties").$type<Record<string, unknown>>().default({}),
  createdAt: timestamp("created_at", { withTimezone: true }).defaultNow().notNull(),
});

/**
 * Staff Assignments table
 *
 * Tracks which staff members are assigned to which clients.
 * Used by client detail pages and RLS policies for staff access control.
 */
export const staffAssignments = pgTable("staff_assignments", {
  id: uuid("id").primaryKey().defaultRandom(),
  clientId: uuid("client_id")
    .notNull()
    .references(() => clients.id, { onDelete: "cascade" }),
  userId: uuid("user_id")
    .notNull()
    .references(() => users.id, { onDelete: "cascade" }),
  role: text("role").default("member"),
  createdAt: timestamp("created_at", { withTimezone: true }).defaultNow().notNull(),
  updatedAt: timestamp("updated_at", { withTimezone: true }).defaultNow().notNull(),
});

export type ActivityLog = typeof activityLogs.$inferSelect;
export type NewActivityLog = typeof activityLogs.$inferInsert;
export type StaffAssignment = typeof staffAssignments.$inferSelect;
export type NewStaffAssignment = typeof staffAssignments.$inferInsert;
