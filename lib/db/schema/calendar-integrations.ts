import { pgTable, uuid, text, timestamp, boolean, uniqueIndex } from "drizzle-orm/pg-core";
import { relations } from "drizzle-orm";
import { users } from "./users";

export const calendarProviderEnum = ["google", "microsoft"] as const;
export type CalendarProvider = (typeof calendarProviderEnum)[number];

/**
 * Stores per-user OAuth calendar connections.
 *
 * Access tokens and refresh tokens are always stored encrypted using
 * lib/encryption.ts (AES-256-GCM). Never store plaintext tokens.
 *
 * Required env vars:
 *   GOOGLE_CALENDAR_CLIENT_ID / GOOGLE_CALENDAR_CLIENT_SECRET
 *   MICROSOFT_CALENDAR_CLIENT_ID / MICROSOFT_CALENDAR_CLIENT_SECRET
 *   NEXT_PUBLIC_APP_URL (used to build callback URLs)
 */
export const calendarConnections = pgTable(
  "calendar_connections",
  {
    id: uuid("id").primaryKey().defaultRandom(),
    userId: uuid("user_id")
      .notNull()
      .references(() => users.id, { onDelete: "cascade" }),
    provider: text("provider", { enum: calendarProviderEnum }).notNull(),
    /** Provider-specific calendar ID (e.g., "primary" for Google). */
    calendarId: text("calendar_id").notNull().default("primary"),
    calendarName: text("calendar_name"),
    encryptedAccessToken: text("encrypted_access_token").notNull(),
    encryptedRefreshToken: text("encrypted_refresh_token"),
    tokenExpiry: timestamp("token_expiry", { withTimezone: true }),
    isActive: boolean("is_active").default(true).notNull(),
    createdAt: timestamp("created_at", { withTimezone: true }).defaultNow().notNull(),
    updatedAt: timestamp("updated_at", { withTimezone: true }).defaultNow().notNull(),
  },
  (t) => ({
    // One active connection per provider per user
    userProviderIdx: uniqueIndex("calendar_connections_user_provider_idx").on(t.userId, t.provider),
  }),
);

export const calendarConnectionsRelations = relations(calendarConnections, ({ one }) => ({
  user: one(users, {
    fields: [calendarConnections.userId],
    references: [users.id],
  }),
}));

export type CalendarConnection = typeof calendarConnections.$inferSelect;
export type NewCalendarConnection = typeof calendarConnections.$inferInsert;

/** Availability result for a single user at a requested time slot. */
export type UserAvailability = {
  userId: string;
  name: string;
  status: "free" | "busy" | "no_calendar";
  busyBlocks?: Array<{ start: string; end: string }>;
};
