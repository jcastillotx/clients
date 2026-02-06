import { pgTable, uuid, text, boolean, timestamp, jsonb, uniqueIndex } from "drizzle-orm/pg-core";
import { relations } from "drizzle-orm";
import { users } from "./users";
import { roles } from "./rbac";
import { clients } from "./clients";

/**
 * Feature Flags System
 *
 * Allows granular control of features at multiple levels:
 * - Global (system-wide)
 * - Client-level (per client)
 * - Role-level (per role)
 * - User-level (per user)
 *
 * Priority: User > Role > Client > Global
 */

// All available features in the system
export const features = pgTable("features", {
  id: uuid("id").primaryKey().defaultRandom(),
  name: text("name").notNull().unique(), // e.g., 'support_tickets', 'proposals', 'time_tracking'
  displayName: text("display_name").notNull(), // e.g., 'Support Tickets'
  description: text("description"),
  category: text("category").notNull(), // e.g., 'ticketing', 'projects', 'marketing', 'ai'
  isEnabledByDefault: boolean("is_enabled_by_default").default(true),
  requiresSetup: boolean("requires_setup").default(false), // Some features need configuration
  setupInstructions: text("setup_instructions"), // Optional setup guide
  dependencies: jsonb("dependencies").$type<string[]>(), // Feature dependencies (e.g., proposals require clients)
  metadata: jsonb("metadata").$type<{
    icon?: string;
    color?: string;
    documentation?: string;
    estimatedSetupTime?: string;
  }>(),
  createdAt: timestamp("created_at", { withTimezone: true }).defaultNow(),
  updatedAt: timestamp("updated_at", { withTimezone: true }).defaultNow(),
});

// Client-level feature toggles
export const clientFeatures = pgTable(
  "client_features",
  {
    id: uuid("id").primaryKey().defaultRandom(),
    clientId: uuid("client_id")
      .notNull()
      .references(() => clients.id, { onDelete: "cascade" }),
    featureId: uuid("feature_id")
      .notNull()
      .references(() => features.id, { onDelete: "cascade" }),
    isEnabled: boolean("is_enabled").notNull().default(true),
    config: jsonb("config").$type<Record<string, any>>(), // Feature-specific configuration
    enabledAt: timestamp("enabled_at", { withTimezone: true }),
    enabledBy: uuid("enabled_by").references(() => users.id),
    disabledAt: timestamp("disabled_at", { withTimezone: true }),
    disabledBy: uuid("disabled_by").references(() => users.id),
    notes: text("notes"), // Why enabled/disabled
    createdAt: timestamp("created_at", { withTimezone: true }).defaultNow(),
    updatedAt: timestamp("updated_at", { withTimezone: true }).defaultNow(),
  },
  (table) => ({
    uniqueClientFeature: uniqueIndex("unique_client_feature").on(table.clientId, table.featureId),
  }),
);

// Role-level feature toggles
export const roleFeatures = pgTable(
  "role_features",
  {
    id: uuid("id").primaryKey().defaultRandom(),
    roleId: uuid("role_id")
      .notNull()
      .references(() => roles.id, { onDelete: "cascade" }),
    featureId: uuid("feature_id")
      .notNull()
      .references(() => features.id, { onDelete: "cascade" }),
    isEnabled: boolean("is_enabled").notNull().default(true),
    createdAt: timestamp("created_at", { withTimezone: true }).defaultNow(),
    updatedAt: timestamp("updated_at", { withTimezone: true }).defaultNow(),
  },
  (table) => ({
    uniqueRoleFeature: uniqueIndex("unique_role_feature").on(table.roleId, table.featureId),
  }),
);

// User-level feature toggles (overrides)
export const userFeatures = pgTable(
  "user_features",
  {
    id: uuid("id").primaryKey().defaultRandom(),
    userId: uuid("user_id")
      .notNull()
      .references(() => users.id, { onDelete: "cascade" }),
    featureId: uuid("feature_id")
      .notNull()
      .references(() => features.id, { onDelete: "cascade" }),
    isEnabled: boolean("is_enabled").notNull().default(true),
    createdAt: timestamp("created_at", { withTimezone: true }).defaultNow(),
    updatedAt: timestamp("updated_at", { withTimezone: true }).defaultNow(),
  },
  (table) => ({
    uniqueUserFeature: uniqueIndex("unique_user_feature").on(table.userId, table.featureId),
  }),
);

// Relations
export const featuresRelations = relations(features, ({ many }) => ({
  clientFeatures: many(clientFeatures),
  roleFeatures: many(roleFeatures),
  userFeatures: many(userFeatures),
}));

export const clientFeaturesRelations = relations(clientFeatures, ({ one }) => ({
  client: one(clients, { fields: [clientFeatures.clientId], references: [clients.id] }),
  feature: one(features, { fields: [clientFeatures.featureId], references: [features.id] }),
  enabledByUser: one(users, { fields: [clientFeatures.enabledBy], references: [users.id] }),
  disabledByUser: one(users, { fields: [clientFeatures.disabledBy], references: [users.id] }),
}));

export const roleFeaturesRelations = relations(roleFeatures, ({ one }) => ({
  role: one(roles, { fields: [roleFeatures.roleId], references: [roles.id] }),
  feature: one(features, { fields: [roleFeatures.featureId], references: [features.id] }),
}));

export const userFeaturesRelations = relations(userFeatures, ({ one }) => ({
  user: one(users, { fields: [userFeatures.userId], references: [users.id] }),
  feature: one(features, { fields: [userFeatures.featureId], references: [features.id] }),
}));

// TypeScript types
export type Feature = typeof features.$inferSelect;
export type NewFeature = typeof features.$inferInsert;
export type ClientFeature = typeof clientFeatures.$inferSelect;
export type NewClientFeature = typeof clientFeatures.$inferInsert;
export type RoleFeature = typeof roleFeatures.$inferSelect;
export type NewRoleFeature = typeof roleFeatures.$inferInsert;
export type UserFeature = typeof userFeatures.$inferSelect;
export type NewUserFeature = typeof userFeatures.$inferInsert;
