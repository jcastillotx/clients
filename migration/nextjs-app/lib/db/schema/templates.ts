import { pgTable, uuid, text, timestamp, boolean, jsonb } from "drizzle-orm/pg-core";

export const invoiceTemplates = pgTable("invoice_templates", {
  id: uuid("id").primaryKey().defaultRandom(),
  name: text("name").notNull(),
  description: text("description"),

  // Template content
  htmlContent: text("html_content").notNull(),
  cssContent: text("css_content"),

  // Available variables for this template
  availableVariables: jsonb("available_variables").$type<string[]>(),

  // Template metadata
  isDefault: boolean("is_default").default(false),
  isActive: boolean("is_active").default(true),

  // Preview settings
  previewData: jsonb("preview_data").$type<Record<string, any>>(),

  createdAt: timestamp("created_at", { withTimezone: true }).defaultNow(),
  updatedAt: timestamp("updated_at", { withTimezone: true }).defaultNow(),
  deletedAt: timestamp("deleted_at", { withTimezone: true }),
});

export const emailTemplates = pgTable("email_templates", {
  id: uuid("id").primaryKey().defaultRandom(),
  name: text("name").notNull(),
  description: text("description"),

  // Email type (invoice_sent, invoice_reminder, payment_received, etc.)
  type: text("type").notNull(),

  // Email content
  subject: text("subject").notNull(),
  htmlContent: text("html_content").notNull(),
  textContent: text("text_content"),

  // Available variables for this template
  availableVariables: jsonb("available_variables").$type<string[]>(),

  // Template metadata
  isDefault: boolean("is_default").default(false),
  isActive: boolean("is_active").default(true),

  // Preview settings
  previewData: jsonb("preview_data").$type<Record<string, any>>(),

  createdAt: timestamp("created_at", { withTimezone: true }).defaultNow(),
  updatedAt: timestamp("updated_at", { withTimezone: true }).defaultNow(),
  deletedAt: timestamp("deleted_at", { withTimezone: true }),
});

export type InvoiceTemplate = typeof invoiceTemplates.$inferSelect;
export type NewInvoiceTemplate = typeof invoiceTemplates.$inferInsert;

export type EmailTemplate = typeof emailTemplates.$inferSelect;
export type NewEmailTemplate = typeof emailTemplates.$inferInsert;

// Email template types
export const EmailTemplateTypes = {
  INVOICE_SENT: "invoice_sent",
  INVOICE_REMINDER: "invoice_reminder",
  PAYMENT_RECEIVED: "payment_received",
  PAYMENT_FAILED: "payment_failed",
  INVOICE_OVERDUE: "invoice_overdue",
} as const;

export type EmailTemplateType = (typeof EmailTemplateTypes)[keyof typeof EmailTemplateTypes];
