import { pgTable, uuid, text, timestamp, boolean, jsonb } from "drizzle-orm/pg-core";
import { relations } from "drizzle-orm";
import { clients } from "./clients";
import { requests } from "./requests";

/**
 * User status enum
 */
export const userStatusEnum = ["active", "inactive", "suspended"] as const;
export type UserStatus = (typeof userStatusEnum)[number];

/**
 * Users table
 *
 * Stores user accounts with authentication and authorization data.
 * Syncs with Supabase Auth via user_metadata.
 */
export const users = pgTable("users", {
  id: uuid("id").primaryKey().defaultRandom(),
  name: text("name").notNull(),
  email: text("email").notNull().unique(),
  phone: text("phone"),
  avatar: text("avatar"),
  clientId: uuid("client_id").references(() => clients.id),
  isActive: boolean("is_active").default(true).notNull(),
  isSuperAdmin: boolean("is_super_admin").default(false).notNull(),
  status: text("status", { enum: userStatusEnum }).default("active").notNull(),
  lastLoginAt: timestamp("last_login_at", { withTimezone: true }),
  manualPermissions: jsonb("manual_permissions").$type<string[]>(),
  securitySettings: jsonb("security_settings").$type<{
    twoFactorEnabled?: boolean;
    ipWhitelist?: string[];
    sessionTimeout?: number;
  }>(),
  createdAt: timestamp("created_at", { withTimezone: true }).defaultNow().notNull(),
  updatedAt: timestamp("updated_at", { withTimezone: true }).defaultNow().notNull(),
  deletedAt: timestamp("deleted_at", { withTimezone: true }),
});

/**
 * User relations
 */
export const usersRelations = relations(users, ({ one, many }) => ({
  client: one(clients, {
    fields: [users.clientId],
    references: [clients.id],
  }),
  createdRequests: many(requests as any),
  assignedRequests: many(requests as any),
  userRoles: many(userRoles),
}));

/**
 * TypeScript types
 */
export type User = typeof users.$inferSelect;
export type NewUser = typeof users.$inferInsert;

/**
 * Roles table
 */
export const roles = pgTable("roles", {
  id: uuid("id").primaryKey().defaultRandom(),
  name: text("name").notNull().unique(),
  description: text("description"),
  createdAt: timestamp("created_at", { withTimezone: true }).defaultNow().notNull(),
});

export type Role = typeof roles.$inferSelect;
export type NewRole = typeof roles.$inferInsert;

/**
 * Permissions table
 */
export const permissions = pgTable("permissions", {
  id: uuid("id").primaryKey().defaultRandom(),
  name: text("name").notNull().unique(),
  description: text("description"),
  createdAt: timestamp("created_at", { withTimezone: true }).defaultNow().notNull(),
});

export type Permission = typeof permissions.$inferSelect;
export type NewPermission = typeof permissions.$inferInsert;

/**
 * User-Role junction table
 */
export const userRoles = pgTable("user_roles", {
  userId: uuid("user_id")
    .notNull()
    .references(() => users.id, { onDelete: "cascade" }),
  roleId: uuid("role_id")
    .notNull()
    .references(() => roles.id, { onDelete: "cascade" }),
});

/**
 * Role-Permission junction table
 */
export const rolePermissions = pgTable("role_permissions", {
  roleId: uuid("role_id")
    .notNull()
    .references(() => roles.id, { onDelete: "cascade" }),
  permissionId: uuid("permission_id")
    .notNull()
    .references(() => permissions.id, { onDelete: "cascade" }),
});
