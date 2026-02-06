import { pgTable, uuid, text, timestamp, jsonb } from "drizzle-orm/pg-core";
import { relations } from "drizzle-orm";

/**
 * Client status enum
 */
export const clientStatusEnum = ["active", "inactive", "pending", "suspended"] as const;
export type ClientStatus = (typeof clientStatusEnum)[number];

/**
 * Clients table
 *
 * Stores client companies with multi-tenant isolation via RLS.
 */
export const clients = pgTable("clients", {
  id: uuid("id").primaryKey().defaultRandom(),
  companyName: text("company_name").notNull(),
  email: text("email").notNull(),
  phone: text("phone"),
  website: text("website"),
  address: text("address"),
  city: text("city"),
  state: text("state"),
  zipCode: text("zip_code"),
  country: text("country").default("US"),
  status: text("status", { enum: clientStatusEnum }).default("active").notNull(),
  enabledFeatures: jsonb("enabled_features").$type<{
    invoicing?: boolean;
    contracts?: boolean;
    projects?: boolean;
    seo?: boolean;
    ai?: boolean;
  }>(),
  googleSearchConsoleData: jsonb("google_search_console_data").$type<{
    propertyUrl?: string;
    accessToken?: string;
    refreshToken?: string;
  }>(),
  marketingStrategy: jsonb("marketing_strategy").$type<{
    targetAudience?: string;
    channels?: string[];
    budget?: number;
  }>(),
  createdAt: timestamp("created_at", { withTimezone: true }).defaultNow().notNull(),
  updatedAt: timestamp("updated_at", { withTimezone: true }).defaultNow().notNull(),
  deletedAt: timestamp("deleted_at", { withTimezone: true }),
});

/**
 * Client relations
 */
export const clientsRelations = relations(clients, ({ many }) => ({
  users: many(users),
  requests: many(requests),
  invoices: many(invoices),
  contracts: many(contracts),
  documents: many(documents),
  projects: many(projects),
}));

/**
 * TypeScript types
 */
export type Client = typeof clients.$inferSelect;
export type NewClient = typeof clients.$inferInsert;
