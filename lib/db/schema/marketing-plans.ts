import {
  pgTable,
  uniqueIndex,
  uuid,
  text,
  jsonb,
  boolean,
  integer,
  timestamp,
} from "drizzle-orm/pg-core";
import { clients } from "./clients";
import { users } from "./users";
import type { MarketingToolGroupId } from "@/lib/ai/marketing-skills/menu";
export const marketingPlans = pgTable("marketing_plans", {
  id: uuid("id").primaryKey().defaultRandom(),
  slug: text("slug").unique(),
  name: text("name").notNull(),
  toolGroups: jsonb("tool_groups")
    .$type<MarketingToolGroupId[]>()
    .notNull()
    .default([]),
  isActive: boolean("is_active").notNull().default(true),
  published: boolean("published").notNull().default(false),
  monthlyPriceCents: integer("monthly_price_cents").notNull().default(0),
  currency: text("currency").notNull().default("usd"),
  createdAt: timestamp("created_at", { withTimezone: true })
    .notNull()
    .defaultNow(),
  updatedAt: timestamp("updated_at", { withTimezone: true })
    .notNull()
    .defaultNow(),
});
export const clientMarketingPlans = pgTable("client_marketing_plans", {
  clientId: uuid("client_id")
    .primaryKey()
    .references(() => clients.id),
  planId: uuid("plan_id")
    .notNull()
    .references(() => marketingPlans.id),
  status: text("status").notNull().default("active"),
  startsAt: timestamp("starts_at", { withTimezone: true })
    .notNull()
    .defaultNow(),
  endsAt: timestamp("ends_at", { withTimezone: true }),
  stripeSubscriptionId: text("stripe_subscription_id").unique(),
  stripeCustomerId: text("stripe_customer_id"),
  cancelAtPeriodEnd: boolean("cancel_at_period_end").notNull().default(false),
  updatedAt: timestamp("updated_at", { withTimezone: true })
    .notNull()
    .defaultNow(),
});
export const marketingCheckoutSessions = pgTable(
  "marketing_checkout_sessions",
  {
    id: uuid("id").primaryKey().defaultRandom(),
    clientId: uuid("client_id")
      .notNull()
      .references(() => clients.id),
    planId: uuid("plan_id")
      .notNull()
      .references(() => marketingPlans.id),
    stripeSessionId: text("stripe_session_id").unique(),
    stripeSubscriptionId: text("stripe_subscription_id").unique(),
    priceCents: integer("price_cents").notNull(),
    currency: text("currency").notNull(),
    createdAt: timestamp("created_at", { withTimezone: true })
      .notNull()
      .defaultNow(),
  },
);
export const marketingConnections = pgTable(
  "marketing_connections",
  {
    id: uuid("id").primaryKey().defaultRandom(),
    clientId: uuid("client_id")
      .notNull()
      .references(() => clients.id),
    userId: uuid("user_id")
      .notNull()
      .references(() => users.id),
    provider: text("provider").notNull(),
    accessToken: text("access_token").notNull(),
    refreshToken: text("refresh_token"),
    expiresAt: timestamp("expires_at", { withTimezone: true }),
    resourceId: text("resource_id"),
    createdAt: timestamp("created_at", { withTimezone: true })
      .notNull()
      .defaultNow(),
    updatedAt: timestamp("updated_at", { withTimezone: true })
      .notNull()
      .defaultNow(),
  },
  (t) => ({
    ownerProvider: uniqueIndex(
      "marketing_connections_client_id_user_id_provider_key",
    ).on(t.clientId, t.userId, t.provider),
  }),
);
export const marketingOAuthStates = pgTable("marketing_oauth_states", {
  stateHash: text("state_hash").primaryKey(),
  clientId: uuid("client_id")
    .notNull()
    .references(() => clients.id),
  userId: uuid("user_id")
    .notNull()
    .references(() => users.id),
  provider: text("provider").notNull(),
  verifier: text("verifier").notNull(),
  expiresAt: timestamp("expires_at", { withTimezone: true }).notNull(),
});
