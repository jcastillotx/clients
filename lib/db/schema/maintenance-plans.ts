import { pgTable, uuid, text, timestamp, decimal, integer, boolean, jsonb, date } from "drizzle-orm/pg-core";
import { relations } from "drizzle-orm";
import { clients } from "./clients";
import { users } from "./users";
import { supportTickets } from "./support-tickets";

/**
 * Maintenance plan status enum
 */
export const maintenancePlanStatusEnum = ["active", "paused", "expired", "cancelled"] as const;
export type MaintenancePlanStatus = (typeof maintenancePlanStatusEnum)[number];

/**
 * Billing cycle enum
 */
export const billingCycleEnum = ["monthly", "quarterly", "semi_annual", "annual"] as const;
export type BillingCycle = (typeof billingCycleEnum)[number];

/**
 * Maintenance Plan Templates table
 *
 * Admin-created general plan templates that clients can choose from and subscribe to.
 */
export const maintenancePlanTemplates = pgTable("maintenance_plan_templates", {
  id: uuid("id").primaryKey().defaultRandom(),

  // Plan template details
  name: text("name").notNull(),
  description: text("description"),
  planType: text("plan_type").default("standard").notNull(), // standard, premium, enterprise, custom
  isActive: boolean("is_active").default(true).notNull(),

  // Billing
  billingCycle: text("billing_cycle", { enum: billingCycleEnum }).default("monthly").notNull(),
  monthlyRate: decimal("monthly_rate", { precision: 10, scale: 2 }).notNull(),
  currency: text("currency").default("USD").notNull(),

  // Hours
  includedHours: decimal("included_hours", { precision: 10, scale: 2 }).notNull(),
  hourlyRateOverage: decimal("hourly_rate_overage", { precision: 10, scale: 2 }).notNull(),

  // Settings
  autoRenew: boolean("auto_renew").default(true).notNull(),
  rolloverEnabled: boolean("rollover_enabled").default(false).notNull(),
  maxRolloverHours: decimal("max_rollover_hours", { precision: 10, scale: 2 }),
  overageBillingEnabled: boolean("overage_billing_enabled").default(true).notNull(),
  overageApprovalRequired: boolean("overage_approval_required").default(false).notNull(),
  overageNotificationThreshold: decimal("overage_notification_threshold", { precision: 5, scale: 2 })
    .default("90")
    .notNull(),
  renewalTermMonths: integer("renewal_term_months").default(12).notNull(),

  // Services covered
  servicesIncluded: jsonb("services_included").$type<
    Array<{
      category: string;
      description: string;
      included: boolean;
    }>
  >(),

  // Metadata
  metadata: jsonb("metadata").$type<{
    features?: string[];
    notes?: string;
  }>(),

  // Created by admin
  createdBy: uuid("created_by")
    .notNull()
    .references(() => users.id),

  // Timestamps
  createdAt: timestamp("created_at", { withTimezone: true }).defaultNow().notNull(),
  updatedAt: timestamp("updated_at", { withTimezone: true }).defaultNow().notNull(),
});

/**
 * Maintenance Plans table
 *
 * Manages recurring maintenance plans with included hours, billing cycles, and service coverage.
 */
export const maintenancePlans = pgTable("maintenance_plans", {
  id: uuid("id").primaryKey().defaultRandom(),

  // References
  clientId: uuid("client_id")
    .notNull()
    .references(() => clients.id),
  createdBy: uuid("created_by")
    .notNull()
    .references(() => users.id),
  templateId: uuid("template_id").references(() => maintenancePlanTemplates.id),

  // Plan details
  name: text("name").notNull(),
  description: text("description"),
  planType: text("plan_type").default("standard").notNull(), // standard, premium, enterprise, custom
  status: text("status", { enum: maintenancePlanStatusEnum }).default("active").notNull(),

  // Dates
  startDate: date("start_date").notNull(),
  endDate: date("end_date"),
  nextBillingDate: date("next_billing_date"),

  // Billing
  billingCycle: text("billing_cycle", { enum: billingCycleEnum }).default("monthly").notNull(),
  monthlyRate: decimal("monthly_rate", { precision: 10, scale: 2 }).notNull(),
  currency: text("currency").default("USD").notNull(),
  autoRenew: boolean("auto_renew").default(true).notNull(),

  // Hours and usage
  includedHours: decimal("included_hours", { precision: 10, scale: 2 }).notNull(),
  usedHours: decimal("used_hours", { precision: 10, scale: 2 }).default("0").notNull(),
  hourlyRateOverage: decimal("hourly_rate_overage", { precision: 10, scale: 2 }).notNull(),

  // Hour rollover settings
  rolloverEnabled: boolean("rollover_enabled").default(false).notNull(),
  maxRolloverHours: decimal("max_rollover_hours", { precision: 10, scale: 2 }),
  rolloverHoursAvailable: decimal("rollover_hours_available", { precision: 10, scale: 2 }).default("0").notNull(),

  // Services covered
  servicesIncluded: jsonb("services_included").$type<
    Array<{
      category: string;
      description: string;
      included: boolean;
    }>
  >(),
  coveredServices: jsonb("covered_services").$type<string[]>(), // Legacy compatibility

  // Overage billing settings
  overageBillingEnabled: boolean("overage_billing_enabled").default(true).notNull(),
  overageApprovalRequired: boolean("overage_approval_required").default(false).notNull(),
  overageNotificationThreshold: decimal("overage_notification_threshold", { precision: 5, scale: 2 })
    .default("90")
    .notNull(), // Percentage

  // Renewal automation
  autoRenewNotificationDays: integer("auto_renew_notification_days").default(30).notNull(),
  lastRenewalNotificationSent: timestamp("last_renewal_notification_sent", { withTimezone: true }),
  renewalTermMonths: integer("renewal_term_months").default(12).notNull(),

  // Metadata
  metadata: jsonb("metadata").$type<{
    tags?: string[];
    customFields?: Record<string, any>;
    serviceCategories?: Array<{
      name: string;
      description: string;
    }>;
    contractTerms?: string;
    notes?: string;
  }>(),

  // Timestamps
  createdAt: timestamp("created_at", { withTimezone: true }).defaultNow().notNull(),
  updatedAt: timestamp("updated_at", { withTimezone: true }).defaultNow().notNull(),
  deletedAt: timestamp("deleted_at", { withTimezone: true }),
});

/**
 * Maintenance Plan Usage table
 *
 * Tracks hours used against maintenance plans with detailed logging.
 */
export const maintenancePlanUsage = pgTable("maintenance_plan_usage", {
  id: uuid("id").primaryKey().defaultRandom(),

  // References
  planId: uuid("plan_id")
    .notNull()
    .references(() => maintenancePlans.id, { onDelete: "cascade" }),
  supportTicketId: uuid("support_ticket_id").references(() => supportTickets.id),
  loggedBy: uuid("logged_by")
    .notNull()
    .references(() => users.id),

  // Usage details
  hoursUsed: decimal("hours_used", { precision: 10, scale: 2 }).notNull(),
  description: text("description").notNull(),
  taskCategory: text("task_category"), // maintenance, support, development, etc.
  isOverage: boolean("is_overage").default(false).notNull(),
  overageAmount: decimal("overage_amount", { precision: 10, scale: 2 }),

  // Billing
  isBillable: boolean("is_billable").default(true).notNull(),
  billedAmount: decimal("billed_amount", { precision: 10, scale: 2 }),
  invoiceId: uuid("invoice_id"),

  // Timeline
  loggedAt: timestamp("logged_at", { withTimezone: true }).defaultNow().notNull(),
  workPerformedAt: timestamp("work_performed_at", { withTimezone: true }),

  // Approval workflow
  requiresApproval: boolean("requires_approval").default(false).notNull(),
  approvedBy: uuid("approved_by").references(() => users.id),
  approvedAt: timestamp("approved_at", { withTimezone: true }),
  approvalStatus: text("approval_status", { enum: ["pending", "approved", "rejected"] }).default("approved"),

  // Metadata
  metadata: jsonb("metadata").$type<{
    notes?: string;
    attachments?: Array<{
      name: string;
      url: string;
      type: string;
    }>;
    tags?: string[];
  }>(),

  // Timestamps
  createdAt: timestamp("created_at", { withTimezone: true }).defaultNow().notNull(),
  updatedAt: timestamp("updated_at", { withTimezone: true }).defaultNow().notNull(),
  deletedAt: timestamp("deleted_at", { withTimezone: true }),
});

/**
 * Maintenance Plan Billing History table
 *
 * Tracks billing events for maintenance plans.
 */
export const maintenancePlanBillingHistory = pgTable("maintenance_plan_billing_history", {
  id: uuid("id").primaryKey().defaultRandom(),

  // References
  planId: uuid("plan_id")
    .notNull()
    .references(() => maintenancePlans.id, { onDelete: "cascade" }),
  invoiceId: uuid("invoice_id"),

  // Billing period
  billingPeriodStart: date("billing_period_start").notNull(),
  billingPeriodEnd: date("billing_period_end").notNull(),

  // Amounts
  baseAmount: decimal("base_amount", { precision: 10, scale: 2 }).notNull(),
  overageHours: decimal("overage_hours", { precision: 10, scale: 2 }).default("0").notNull(),
  overageAmount: decimal("overage_amount", { precision: 10, scale: 2 }).default("0").notNull(),
  totalAmount: decimal("total_amount", { precision: 10, scale: 2 }).notNull(),

  // Usage summary
  includedHours: decimal("included_hours", { precision: 10, scale: 2 }).notNull(),
  usedHours: decimal("used_hours", { precision: 10, scale: 2 }).notNull(),
  rolloverHoursUsed: decimal("rollover_hours_used", { precision: 10, scale: 2 }).default("0").notNull(),

  // Status
  billingStatus: text("billing_status", { enum: ["pending", "invoiced", "paid", "failed"] })
    .default("pending")
    .notNull(),
  billedAt: timestamp("billed_at", { withTimezone: true }),
  paidAt: timestamp("paid_at", { withTimezone: true }),

  // Metadata
  metadata: jsonb("metadata").$type<{
    notes?: string;
    adjustments?: Array<{
      type: string;
      amount: number;
      reason: string;
    }>;
  }>(),

  // Timestamps
  createdAt: timestamp("created_at", { withTimezone: true }).defaultNow().notNull(),
  updatedAt: timestamp("updated_at", { withTimezone: true }).defaultNow().notNull(),
});

/**
 * Maintenance plan template relations
 */
export const maintenancePlanTemplatesRelations = relations(maintenancePlanTemplates, ({ one, many }) => ({
  creator: one(users, {
    fields: [maintenancePlanTemplates.createdBy],
    references: [users.id],
  }),
  plans: many(maintenancePlans),
}));

/**
 * Maintenance plan relations
 */
export const maintenancePlansRelations = relations(maintenancePlans, ({ one, many }) => ({
  client: one(clients, {
    fields: [maintenancePlans.clientId],
    references: [clients.id],
  }),
  creator: one(users, {
    fields: [maintenancePlans.createdBy],
    references: [users.id],
  }),
  template: one(maintenancePlanTemplates, {
    fields: [maintenancePlans.templateId],
    references: [maintenancePlanTemplates.id],
  }),
  usage: many(maintenancePlanUsage),
  billingHistory: many(maintenancePlanBillingHistory),
  supportTickets: many(supportTickets),
}));

/**
 * Maintenance plan usage relations
 */
export const maintenancePlanUsageRelations = relations(maintenancePlanUsage, ({ one }) => ({
  plan: one(maintenancePlans, {
    fields: [maintenancePlanUsage.planId],
    references: [maintenancePlans.id],
  }),
  supportTicket: one(supportTickets, {
    fields: [maintenancePlanUsage.supportTicketId],
    references: [supportTickets.id],
  }),
  logger: one(users, {
    fields: [maintenancePlanUsage.loggedBy],
    references: [users.id],
  }),
  approver: one(users, {
    fields: [maintenancePlanUsage.approvedBy],
    references: [users.id],
  }),
}));

/**
 * Maintenance plan billing history relations
 */
export const maintenancePlanBillingHistoryRelations = relations(maintenancePlanBillingHistory, ({ one }) => ({
  plan: one(maintenancePlans, {
    fields: [maintenancePlanBillingHistory.planId],
    references: [maintenancePlans.id],
  }),
}));

/**
 * TypeScript types
 */
export type MaintenancePlanTemplate = typeof maintenancePlanTemplates.$inferSelect;
export type NewMaintenancePlanTemplate = typeof maintenancePlanTemplates.$inferInsert;
export type MaintenancePlan = typeof maintenancePlans.$inferSelect;
export type NewMaintenancePlan = typeof maintenancePlans.$inferInsert;
export type MaintenancePlanUsage = typeof maintenancePlanUsage.$inferSelect;
export type NewMaintenancePlanUsage = typeof maintenancePlanUsage.$inferInsert;
export type MaintenancePlanBillingHistory = typeof maintenancePlanBillingHistory.$inferSelect;
export type NewMaintenancePlanBillingHistory = typeof maintenancePlanBillingHistory.$inferInsert;
