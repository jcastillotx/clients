import { pgTable, uuid, text, timestamp, boolean, integer, decimal, jsonb } from "drizzle-orm/pg-core";
import { relations } from "drizzle-orm";
import { clients } from "./clients";
import { users } from "./users";

/**
 * Support ticket status enum
 */
export const ticketStatusEnum = [
  "open",
  "in_progress",
  "waiting_on_client",
  "waiting_on_vendor",
  "resolved",
  "closed",
] as const;
export type TicketStatus = (typeof ticketStatusEnum)[number];

/**
 * Support ticket priority enum
 */
export const ticketPriorityEnum = ["low", "medium", "high", "urgent"] as const;
export type TicketPriority = (typeof ticketPriorityEnum)[number];

/**
 * Support ticket category enum
 */
export const ticketCategoryEnum = [
  "technical",
  "billing",
  "general",
  "feature_request",
  "bug_report",
  "security",
  "performance",
] as const;
export type TicketCategory = (typeof ticketCategoryEnum)[number];

/**
 * Support Tickets table
 *
 * Manages support tickets with SLA tracking, escalation, and billing integration.
 */
export const supportTickets = pgTable("support_tickets", {
  id: uuid("id").primaryKey().defaultRandom(),

  // References
  clientId: uuid("client_id")
    .notNull()
    .references(() => clients.id),
  maintenancePlanId: uuid("maintenance_plan_id"),
  createdBy: uuid("created_by")
    .notNull()
    .references(() => users.id),
  assignedTo: uuid("assigned_to").references(() => users.id),
  invoiceId: uuid("invoice_id"),

  // Ticket details
  ticketNumber: text("ticket_number").notNull().unique(),
  subject: text("subject").notNull(),
  description: text("description").notNull(),
  category: text("category", { enum: ticketCategoryEnum }).notNull(),
  status: text("status", { enum: ticketStatusEnum }).default("open").notNull(),
  priority: text("priority", { enum: ticketPriorityEnum }).default("medium").notNull(),

  // Billing
  isBillable: boolean("is_billable").default(true).notNull(),
  estimatedHours: decimal("estimated_hours", { precision: 10, scale: 2 }),
  actualHours: decimal("actual_hours", { precision: 10, scale: 2 }),
  hourlyRate: decimal("hourly_rate", { precision: 10, scale: 2 }),

  // Timeline
  firstResponseAt: timestamp("first_response_at", { withTimezone: true }),
  resolvedAt: timestamp("resolved_at", { withTimezone: true }),
  closedAt: timestamp("closed_at", { withTimezone: true }),

  // SLA tracking
  slaResponseDueAt: timestamp("sla_response_due_at", { withTimezone: true }),
  slaResolutionDueAt: timestamp("sla_resolution_due_at", { withTimezone: true }),
  slaResponseBreached: boolean("sla_response_breached").default(false).notNull(),
  slaResolutionBreached: boolean("sla_resolution_breached").default(false).notNull(),
  slaResponseBreachedAt: timestamp("sla_response_breached_at", { withTimezone: true }),
  slaResolutionBreachedAt: timestamp("sla_resolution_breached_at", { withTimezone: true }),

  // SLA pause tracking
  slaPaused: boolean("sla_paused").default(false).notNull(),
  slaPausedDurationMinutes: integer("sla_paused_duration_minutes").default(0).notNull(),

  // Escalation
  escalationLevel: integer("escalation_level").default(0).notNull(),
  lastEscalatedAt: timestamp("last_escalated_at", { withTimezone: true }),

  // Metadata
  metadata: jsonb("metadata").$type<{
    tags?: string[];
    customFields?: Record<string, any>;
    attachments?: Array<{
      name: string;
      url: string;
      type: string;
      size: number;
    }>;
  }>(),

  // Timestamps
  createdAt: timestamp("created_at", { withTimezone: true }).defaultNow().notNull(),
  updatedAt: timestamp("updated_at", { withTimezone: true }).defaultNow().notNull(),
  deletedAt: timestamp("deleted_at", { withTimezone: true }),
});

/**
 * Support Ticket Comments table
 *
 * Stores comments and internal notes on support tickets.
 */
export const supportTicketComments = pgTable("support_ticket_comments", {
  id: uuid("id").primaryKey().defaultRandom(),

  // References
  supportTicketId: uuid("support_ticket_id")
    .notNull()
    .references(() => supportTickets.id, { onDelete: "cascade" }),
  userId: uuid("user_id")
    .notNull()
    .references(() => users.id),

  // Comment content
  comment: text("comment").notNull(),
  isInternal: boolean("is_internal").default(false).notNull(),

  // Attachments
  attachments: jsonb("attachments").$type<
    Array<{
      name: string;
      url: string;
      type: string;
      size: number;
    }>
  >(),

  // Timestamps
  createdAt: timestamp("created_at", { withTimezone: true }).defaultNow().notNull(),
  updatedAt: timestamp("updated_at", { withTimezone: true }).defaultNow().notNull(),
  deletedAt: timestamp("deleted_at", { withTimezone: true }),
});

/**
 * Support ticket relations
 */
export const supportTicketsRelations = relations(supportTickets, ({ one, many }) => ({
  client: one(clients, {
    fields: [supportTickets.clientId],
    references: [clients.id],
  }),
  creator: one(users, {
    fields: [supportTickets.createdBy],
    references: [users.id],
  }),
  assignedUser: one(users, {
    fields: [supportTickets.assignedTo],
    references: [users.id],
  }),
  comments: many(supportTicketComments),
}));

/**
 * Support ticket comments relations
 */
export const supportTicketCommentsRelations = relations(supportTicketComments, ({ one }) => ({
  supportTicket: one(supportTickets, {
    fields: [supportTicketComments.supportTicketId],
    references: [supportTickets.id],
  }),
  user: one(users, {
    fields: [supportTicketComments.userId],
    references: [users.id],
  }),
}));

/**
 * TypeScript types
 */
export type SupportTicket = typeof supportTickets.$inferSelect;
export type NewSupportTicket = typeof supportTickets.$inferInsert;
export type SupportTicketComment = typeof supportTicketComments.$inferSelect;
export type NewSupportTicketComment = typeof supportTicketComments.$inferInsert;
