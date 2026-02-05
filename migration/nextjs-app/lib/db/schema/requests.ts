import { pgTable, uuid, text, timestamp, jsonb } from "drizzle-orm/pg-core";
import { relations } from "drizzle-orm";
import { clients } from "./clients";
import { users } from "./users";

/**
 * Request status enum
 */
export const requestStatusEnum = [
  "pending",
  "in_progress",
  "completed",
  "cancelled",
  "on_hold",
  "awaiting_approval",
  "approved",
  "rejected",
] as const;
export type RequestStatus = (typeof requestStatusEnum)[number];

/**
 * Requests table
 *
 * Stores service requests from clients with multi-tenant isolation.
 */
export const requests = pgTable("requests", {
  id: uuid("id").primaryKey().defaultRandom(),
  clientId: uuid("client_id")
    .notNull()
    .references(() => clients.id, { onDelete: "cascade" }),
  title: text("title").notNull(),
  description: text("description"),
  status: text("status", { enum: requestStatusEnum }).default("pending").notNull(),
  priority: text("priority", { enum: ["low", "medium", "high"] }).default("medium"),
  createdBy: uuid("created_by")
    .notNull()
    .references(() => users.id),
  assignedTo: uuid("assigned_to").references(() => users.id),
  dueDate: timestamp("due_date", { withTimezone: true }),
  customFields: jsonb("custom_fields").$type<Record<string, any>>(),
  createdAt: timestamp("created_at", { withTimezone: true }).defaultNow().notNull(),
  updatedAt: timestamp("updated_at", { withTimezone: true }).defaultNow().notNull(),
  deletedAt: timestamp("deleted_at", { withTimezone: true }),
});

/**
 * Request comments table
 */
export const requestComments = pgTable("request_comments", {
  id: uuid("id").primaryKey().defaultRandom(),
  requestId: uuid("request_id")
    .notNull()
    .references(() => requests.id, { onDelete: "cascade" }),
  userId: uuid("user_id")
    .notNull()
    .references(() => users.id),
  content: text("content").notNull(),
  createdAt: timestamp("created_at", { withTimezone: true }).defaultNow().notNull(),
  updatedAt: timestamp("updated_at", { withTimezone: true }).defaultNow().notNull(),
});

/**
 * Request relations
 */
export const requestsRelations = relations(requests, ({ one, many }) => ({
  client: one(clients, {
    fields: [requests.clientId],
    references: [clients.id],
  }),
  creator: one(users, {
    fields: [requests.createdBy],
    references: [users.id],
  }),
  assignee: one(users, {
    fields: [requests.assignedTo],
    references: [users.id],
  }),
  comments: many(requestComments),
}));

export const requestCommentsRelations = relations(requestComments, ({ one }) => ({
  request: one(requests, {
    fields: [requestComments.requestId],
    references: [requests.id],
  }),
  user: one(users, {
    fields: [requestComments.userId],
    references: [users.id],
  }),
}));

/**
 * TypeScript types
 */
export type Request = typeof requests.$inferSelect;
export type NewRequest = typeof requests.$inferInsert;
export type RequestComment = typeof requestComments.$inferSelect;
export type NewRequestComment = typeof requestComments.$inferInsert;
