import {
  pgTable,
  uuid,
  text,
  timestamp,
  integer,
  boolean,
  decimal,
  jsonb,
  date,
  index,
  unique,
} from "drizzle-orm/pg-core";
import { relations } from "drizzle-orm";
import { users } from "./users";
import { clients } from "./clients";
import { requests } from "./requests";

/**
 * Time entry status enum
 */
export const timeEntryStatusEnum = ["pending", "approved", "billed", "rejected"] as const;
export type TimeEntryStatus = (typeof timeEntryStatusEnum)[number];

/**
 * Time entries table
 *
 * Stores time tracking entries for users working on tasks, requests, or projects.
 * Supports billable/non-billable tracking, hourly rates, and period locking.
 */
export const timeEntries = pgTable(
  "time_entries",
  {
    id: uuid("id").primaryKey().defaultRandom(),
    userId: uuid("user_id")
      .notNull()
      .references(() => users.id, { onDelete: "cascade" }),
    clientId: uuid("client_id").references(() => clients.id, { onDelete: "set null" }),
    requestId: uuid("request_id").references(() => requests.id, { onDelete: "set null" }),
    taskId: uuid("task_id"), // Reference to tasks if you have a tasks table
    projectId: uuid("project_id"), // Reference to projects if you have a projects table
    description: text("description"),
    startedAt: timestamp("started_at", { withTimezone: true }),
    endedAt: timestamp("ended_at", { withTimezone: true }),
    durationMinutes: integer("duration_minutes"),
    isBillable: boolean("is_billable").default(true).notNull(),
    hourlyRate: decimal("hourly_rate", { precision: 10, scale: 2 }),
    totalAmount: decimal("total_amount", { precision: 10, scale: 2 }),
    status: text("status", { enum: timeEntryStatusEnum }).default("pending").notNull(),
    lockedAt: timestamp("locked_at", { withTimezone: true }),
    lockedBy: uuid("locked_by").references(() => users.id, { onDelete: "set null" }),
    approvedBy: uuid("approved_by").references(() => users.id, { onDelete: "set null" }),
    approvedAt: timestamp("approved_at", { withTimezone: true }),
    billedAt: timestamp("billed_at", { withTimezone: true }),
    metadata: jsonb("metadata").$type<Record<string, any>>(),
    createdAt: timestamp("created_at", { withTimezone: true }).defaultNow().notNull(),
    updatedAt: timestamp("updated_at", { withTimezone: true }).defaultNow().notNull(),
  },
  (table) => ({
    userStartedAtIdx: index("time_entries_user_started_at_idx").on(table.userId, table.startedAt),
    requestStartedAtIdx: index("time_entries_request_started_at_idx").on(table.requestId, table.startedAt),
    clientStartedAtIdx: index("time_entries_client_started_at_idx").on(table.clientId, table.startedAt),
    statusIdx: index("time_entries_status_idx").on(table.status),
  }),
);

/**
 * Time entry locks table
 *
 * Prevents editing time entries for locked periods (typically weekly locks).
 * Used for payroll and billing finalization.
 */
export const timeEntryLocks = pgTable(
  "time_entry_locks",
  {
    id: uuid("id").primaryKey().defaultRandom(),
    userId: uuid("user_id")
      .notNull()
      .references(() => users.id, { onDelete: "cascade" }),
    periodStart: date("period_start").notNull(), // Start of the locked period (e.g., Monday)
    periodEnd: date("period_end").notNull(), // End of the locked period (e.g., Sunday)
    lockedAt: timestamp("locked_at", { withTimezone: true }).notNull(),
    lockedBy: uuid("locked_by")
      .notNull()
      .references(() => users.id, { onDelete: "cascade" }),
    reason: text("reason"),
    createdAt: timestamp("created_at", { withTimezone: true }).defaultNow().notNull(),
    updatedAt: timestamp("updated_at", { withTimezone: true }).defaultNow().notNull(),
  },
  (table) => ({
    userPeriodIdx: unique("time_entry_locks_user_period_unique").on(table.userId, table.periodStart),
  }),
);

/**
 * Request time entries table (simplified time tracking for requests)
 *
 * Alternative simpler time tracking specifically for requests.
 * Used when detailed start/end times aren't needed.
 */
export const requestTimeEntries = pgTable(
  "request_time_entries",
  {
    id: uuid("id").primaryKey().defaultRandom(),
    requestId: uuid("request_id")
      .notNull()
      .references(() => requests.id, { onDelete: "cascade" }),
    userId: uuid("user_id")
      .notNull()
      .references(() => users.id, { onDelete: "cascade" }),
    hours: decimal("hours", { precision: 5, scale: 2 }).notNull(),
    note: text("note"),
    loggedAt: timestamp("logged_at", { withTimezone: true }).notNull(),
    createdAt: timestamp("created_at", { withTimezone: true }).defaultNow().notNull(),
    updatedAt: timestamp("updated_at", { withTimezone: true }).defaultNow().notNull(),
  },
  (table) => ({
    requestLoggedAtIdx: index("request_time_entries_request_logged_at_idx").on(table.requestId, table.loggedAt),
    userLoggedAtIdx: index("request_time_entries_user_logged_at_idx").on(table.userId, table.loggedAt),
  }),
);

/**
 * Time entry relations
 */
export const timeEntriesRelations = relations(timeEntries, ({ one }) => ({
  user: one(users, {
    fields: [timeEntries.userId],
    references: [users.id],
  }),
  client: one(clients, {
    fields: [timeEntries.clientId],
    references: [clients.id],
  }),
  request: one(requests, {
    fields: [timeEntries.requestId],
    references: [requests.id],
  }),
  locker: one(users, {
    fields: [timeEntries.lockedBy],
    references: [users.id],
  }),
  approver: one(users, {
    fields: [timeEntries.approvedBy],
    references: [users.id],
  }),
}));

export const timeEntryLocksRelations = relations(timeEntryLocks, ({ one }) => ({
  user: one(users, {
    fields: [timeEntryLocks.userId],
    references: [users.id],
  }),
  locker: one(users, {
    fields: [timeEntryLocks.lockedBy],
    references: [users.id],
  }),
}));

export const requestTimeEntriesRelations = relations(requestTimeEntries, ({ one }) => ({
  request: one(requests, {
    fields: [requestTimeEntries.requestId],
    references: [requests.id],
  }),
  user: one(users, {
    fields: [requestTimeEntries.userId],
    references: [users.id],
  }),
}));

/**
 * TypeScript types
 */
export type TimeEntry = typeof timeEntries.$inferSelect;
export type NewTimeEntry = typeof timeEntries.$inferInsert;
export type TimeEntryLock = typeof timeEntryLocks.$inferSelect;
export type NewTimeEntryLock = typeof timeEntryLocks.$inferInsert;
export type RequestTimeEntry = typeof requestTimeEntries.$inferSelect;
export type NewRequestTimeEntry = typeof requestTimeEntries.$inferInsert;
