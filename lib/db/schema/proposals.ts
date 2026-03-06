import { pgTable, uuid, text, decimal, timestamp, jsonb, boolean } from "drizzle-orm/pg-core";
import { relations } from "drizzle-orm";
import { clients } from "./clients";
import { users } from "./users";

/**
 * Proposal status enum
 */
export const proposalStatusEnum = ["draft", "sent", "viewed", "accepted", "rejected", "expired", "cancelled"] as const;
export type ProposalStatus = (typeof proposalStatusEnum)[number];

/**
 * Proposal currency enum
 */
export const proposalCurrencyEnum = ["USD", "EUR", "GBP", "CAD"] as const;
export type ProposalCurrency = (typeof proposalCurrencyEnum)[number];

/**
 * Proposal line item interface
 */
export interface ProposalLineItem {
  id: string;
  description: string;
  quantity: number;
  unitPrice: number;
  amount: number;
  category?: string;
}

/**
 * Proposal signature data interface
 */
export interface ProposalSignatureData {
  signatureImage: string;
  signedBy: string;
  signedAt: string;
  ipAddress: string;
  userAgent?: string;
}

/**
 * Service Templates table
 *
 * Admin-created general service offerings that clients can choose from.
 * When a client selects a service, it becomes a proposal for review.
 */
export const serviceTemplates = pgTable("service_templates", {
  id: uuid("id").primaryKey().defaultRandom(),
  name: text("name").notNull(),
  description: text("description"),
  category: text("category"),
  isActive: boolean("is_active").default(true).notNull(),
  currency: text("currency", { enum: proposalCurrencyEnum }).default("USD").notNull(),
  lineItems: jsonb("line_items").$type<ProposalLineItem[]>().notNull(),
  totalAmount: decimal("total_amount", { precision: 10, scale: 2 }).notNull(),
  terms: text("terms"),
  metadata: jsonb("metadata").$type<{
    features?: string[];
    deliverables?: string[];
    estimatedTimeline?: string;
    notes?: string;
  }>(),
  createdBy: uuid("created_by")
    .references(() => users.id)
    .notNull(),
  createdAt: timestamp("created_at", { withTimezone: true }).defaultNow().notNull(),
  updatedAt: timestamp("updated_at", { withTimezone: true }).defaultNow().notNull(),
});

/**
 * Proposals table
 *
 * Stores business proposals sent to clients with e-signature support,
 * line items, terms & conditions, and tracking.
 */
export const proposals = pgTable("proposals", {
  id: uuid("id").primaryKey().defaultRandom(),
  clientId: uuid("client_id")
    .references(() => clients.id, { onDelete: "cascade" })
    .notNull(),
  serviceTemplateId: uuid("service_template_id").references(() => serviceTemplates.id),
  title: text("title").notNull(),
  description: text("description"),
  status: text("status", { enum: proposalStatusEnum }).default("draft").notNull(),
  totalAmount: decimal("total_amount", { precision: 10, scale: 2 }).notNull(),
  currency: text("currency", { enum: proposalCurrencyEnum }).default("USD").notNull(),
  validUntil: timestamp("valid_until", { withTimezone: true }),
  createdBy: uuid("created_by")
    .references(() => users.id)
    .notNull(),
  sentAt: timestamp("sent_at", { withTimezone: true }),
  viewedAt: timestamp("viewed_at", { withTimezone: true }),
  acceptedAt: timestamp("accepted_at", { withTimezone: true }),
  rejectedAt: timestamp("rejected_at", { withTimezone: true }),
  cancelledAt: timestamp("cancelled_at", { withTimezone: true }),
  signatureData: jsonb("signature_data").$type<ProposalSignatureData>(),
  terms: text("terms"),
  lineItems: jsonb("line_items").$type<ProposalLineItem[]>().notNull(),
  clientFeedback: text("client_feedback"),
  metadata: jsonb("metadata").$type<{
    notes?: string;
    internalNotes?: string;
    tags?: string[];
    attachments?: { name: string; url: string; size: number }[];
    isCustomRequest?: boolean;
    customRequestDescription?: string;
  }>(),
  createdAt: timestamp("created_at", { withTimezone: true }).defaultNow().notNull(),
  updatedAt: timestamp("updated_at", { withTimezone: true }).defaultNow().notNull(),
  deletedAt: timestamp("deleted_at", { withTimezone: true }),
});

/**
 * Proposal selections table
 *
 * Stores client selections for proposal sections with multiple options
 * (e.g., package tiers, add-ons, service options).
 */
export const proposalSelections = pgTable("proposal_selections", {
  id: uuid("id").primaryKey().defaultRandom(),
  proposalId: uuid("proposal_id")
    .references(() => proposals.id, { onDelete: "cascade" })
    .notNull(),
  sectionName: text("section_name").notNull(),
  selectedOption: text("selected_option").notNull(),
  createdAt: timestamp("created_at", { withTimezone: true }).defaultNow().notNull(),
  updatedAt: timestamp("updated_at", { withTimezone: true }).defaultNow().notNull(),
});

/**
 * Proposal views table
 *
 * Tracks when and by whom proposals are viewed (analytics).
 */
export const proposalViews = pgTable("proposal_views", {
  id: uuid("id").primaryKey().defaultRandom(),
  proposalId: uuid("proposal_id")
    .references(() => proposals.id, { onDelete: "cascade" })
    .notNull(),
  viewedByIp: text("viewed_by_ip"),
  viewedByUserId: uuid("viewed_by_user_id").references(() => users.id),
  viewedAt: timestamp("viewed_at", { withTimezone: true }).defaultNow().notNull(),
});

/**
 * Service template relations
 */
export const serviceTemplatesRelations = relations(serviceTemplates, ({ one, many }) => ({
  creator: one(users, {
    fields: [serviceTemplates.createdBy],
    references: [users.id],
  }),
  proposals: many(proposals),
}));

/**
 * Proposal relations
 */
export const proposalsRelations = relations(proposals, ({ one, many }) => ({
  client: one(clients, {
    fields: [proposals.clientId],
    references: [clients.id],
  }),
  creator: one(users, {
    fields: [proposals.createdBy],
    references: [users.id],
  }),
  serviceTemplate: one(serviceTemplates, {
    fields: [proposals.serviceTemplateId],
    references: [serviceTemplates.id],
  }),
  selections: many(proposalSelections),
  views: many(proposalViews),
}));

export const proposalSelectionsRelations = relations(proposalSelections, ({ one }) => ({
  proposal: one(proposals, {
    fields: [proposalSelections.proposalId],
    references: [proposals.id],
  }),
}));

export const proposalViewsRelations = relations(proposalViews, ({ one }) => ({
  proposal: one(proposals, {
    fields: [proposalViews.proposalId],
    references: [proposals.id],
  }),
  user: one(users, {
    fields: [proposalViews.viewedByUserId],
    references: [users.id],
  }),
}));

/**
 * TypeScript types
 */
export type ServiceTemplate = typeof serviceTemplates.$inferSelect;
export type NewServiceTemplate = typeof serviceTemplates.$inferInsert;
export type Proposal = typeof proposals.$inferSelect;
export type NewProposal = typeof proposals.$inferInsert;
export type ProposalSelection = typeof proposalSelections.$inferSelect;
export type NewProposalSelection = typeof proposalSelections.$inferInsert;
export type ProposalView = typeof proposalViews.$inferSelect;
export type NewProposalView = typeof proposalViews.$inferInsert;
