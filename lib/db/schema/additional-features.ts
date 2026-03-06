import { pgTable, uuid, text, timestamp, jsonb, integer, boolean, bigint, real } from "drizzle-orm/pg-core";
import { relations } from "drizzle-orm";
import { clients } from "./clients";
import { users } from "./users";

/**
 * Risk level enum for account health
 */
export const riskLevelEnum = ["low", "medium", "high", "critical"] as const;
export type RiskLevel = (typeof riskLevelEnum)[number];

/**
 * Account Health table
 *
 * Tracks client account health scores, engagement metrics, and risk assessment.
 * Provides predictive analytics for client retention and growth opportunities.
 */
export const accountHealth = pgTable("account_health", {
  id: uuid("id").primaryKey().defaultRandom(),
  clientId: uuid("client_id")
    .notNull()
    .references(() => clients.id, { onDelete: "cascade" }),
  score: real("score").notNull(), // 0-100 health score
  factors: jsonb("factors")
    .$type<{
      engagement?: number; // 0-100
      payment_history?: number; // 0-100
      support_satisfaction?: number; // 0-100
      feature_adoption?: number; // 0-100
      communication?: number; // 0-100
    }>()
    .notNull(),
  lastInteraction: timestamp("last_interaction", { withTimezone: true }),
  revenueTrend: text("revenue_trend", { enum: ["increasing", "stable", "decreasing"] }),
  satisfactionScore: real("satisfaction_score"), // 0-10 scale
  riskLevel: text("risk_level", { enum: riskLevelEnum }).notNull(),
  recommendations: jsonb("recommendations").$type<
    Array<{
      type: "upsell" | "retention" | "engagement" | "support";
      priority: "low" | "medium" | "high";
      message: string;
      actionUrl?: string;
    }>
  >(),
  calculatedAt: timestamp("calculated_at", { withTimezone: true }).notNull(),
  createdAt: timestamp("created_at", { withTimezone: true }).defaultNow().notNull(),
  updatedAt: timestamp("updated_at", { withTimezone: true }).defaultNow().notNull(),
});

/**
 * Client Health Snapshots table
 *
 * Historical snapshots of client health metrics for trend analysis.
 */
export const clientHealthSnapshots = pgTable("client_health_snapshots", {
  id: uuid("id").primaryKey().defaultRandom(),
  clientId: uuid("client_id")
    .notNull()
    .references(() => clients.id, { onDelete: "cascade" }),
  snapshotDate: timestamp("snapshot_date", { withTimezone: true }).notNull(),
  metrics: jsonb("metrics")
    .$type<{
      totalRevenue?: number;
      activeProjects?: number;
      openTickets?: number;
      completedTasks?: number;
      responseTime?: number; // in hours
      nps?: number; // Net Promoter Score
      churnRisk?: number; // 0-100 probability
    }>()
    .notNull(),
  alerts: jsonb("alerts").$type<
    Array<{
      type: "warning" | "critical" | "info";
      message: string;
      metric: string;
      value: number;
      threshold: number;
    }>
  >(),
  createdAt: timestamp("created_at", { withTimezone: true }).defaultNow().notNull(),
  updatedAt: timestamp("updated_at", { withTimezone: true }).defaultNow().notNull(),
});

/**
 * Storage provider enum
 */
export const storageProviderEnum = ["s3", "gcs", "azure", "dropbox", "google-drive", "onedrive"] as const;
export type StorageProvider = (typeof storageProviderEnum)[number];

/**
 * Storage connection owner type enum
 *
 * "company" = company-wide connection managed by admins (e.g., primary S3, company Dropbox)
 * "client"  = per-client connection so staff can access that client's cloud files
 */
export const storageOwnerTypeEnum = ["company", "client"] as const;
export type StorageOwnerType = (typeof storageOwnerTypeEnum)[number];

/**
 * Storage Connections table
 *
 * Manages external storage integrations at two levels:
 * 1. Company-level: Primary AWS S3 + company Dropbox/Google Drive/OneDrive (admin-managed)
 * 2. Client-level: Each client connects their own Dropbox/Google Drive/OneDrive for staff access
 */
export const storageConnections = pgTable("storage_connections", {
  id: uuid("id").primaryKey().defaultRandom(),
  clientId: uuid("client_id")
    .notNull()
    .references(() => clients.id, { onDelete: "cascade" }),
  provider: text("provider", { enum: storageProviderEnum }).notNull(),
  ownerType: text("owner_type", { enum: storageOwnerTypeEnum }).default("client").notNull(),
  connectionName: text("connection_name").notNull(),
  credentialsEncrypted: text("credentials_encrypted").notNull(), // Encrypted JSON credentials
  syncEnabled: boolean("sync_enabled").default(true).notNull(),
  lastSyncAt: timestamp("last_sync_at", { withTimezone: true }),
  config: jsonb("config").$type<{
    bucket?: string;
    region?: string;
    path?: string;
    autoSync?: boolean;
    syncInterval?: number; // minutes
    fileFilters?: string[]; // e.g., ["*.pdf", "*.docx"]
  }>(),
  createdAt: timestamp("created_at", { withTimezone: true }).defaultNow().notNull(),
  updatedAt: timestamp("updated_at", { withTimezone: true }).defaultNow().notNull(),
});

/**
 * Sync status enum
 */
export const syncStatusEnum = ["pending", "syncing", "synced", "failed"] as const;
export type SyncStatus = (typeof syncStatusEnum)[number];

/**
 * Storage Files table
 *
 * Tracks files synced from external storage providers.
 */
export const storageFiles = pgTable("storage_files", {
  id: uuid("id").primaryKey().defaultRandom(),
  connectionId: uuid("connection_id")
    .notNull()
    .references(() => storageConnections.id, { onDelete: "cascade" }),
  filePath: text("file_path").notNull(),
  fileName: text("file_name").notNull(),
  fileSize: bigint("file_size", { mode: "number" }).notNull(), // bytes
  mimeType: text("mime_type"),
  lastModified: timestamp("last_modified", { withTimezone: true }).notNull(),
  syncStatus: text("sync_status", { enum: syncStatusEnum }).default("pending").notNull(),
  createdAt: timestamp("created_at", { withTimezone: true }).defaultNow().notNull(),
  updatedAt: timestamp("updated_at", { withTimezone: true }).defaultNow().notNull(),
});

/**
 * Data privacy request type enum
 */
export const privacyRequestTypeEnum = ["export", "delete", "rectify", "restrict", "object"] as const;
export type PrivacyRequestType = (typeof privacyRequestTypeEnum)[number];

/**
 * Data privacy request status enum
 */
export const privacyRequestStatusEnum = ["pending", "processing", "completed", "rejected"] as const;
export type PrivacyRequestStatus = (typeof privacyRequestStatusEnum)[number];

/**
 * Data Privacy Requests table
 *
 * GDPR/CCPA compliance tracking for data subject requests.
 */
export const dataPrivacyRequests = pgTable("data_privacy_requests", {
  id: uuid("id").primaryKey().defaultRandom(),
  clientId: uuid("client_id")
    .notNull()
    .references(() => clients.id, { onDelete: "cascade" }),
  userId: uuid("user_id").references(() => users.id),
  requestType: text("request_type", { enum: privacyRequestTypeEnum }).notNull(),
  status: text("status", { enum: privacyRequestStatusEnum }).default("pending").notNull(),
  requestedAt: timestamp("requested_at", { withTimezone: true }).defaultNow().notNull(),
  completedAt: timestamp("completed_at", { withTimezone: true }),
  dataExportUrl: text("data_export_url"), // Presigned URL for data exports
  notes: text("notes"),
  createdAt: timestamp("created_at", { withTimezone: true }).defaultNow().notNull(),
  updatedAt: timestamp("updated_at", { withTimezone: true }).defaultNow().notNull(),
});

/**
 * White Label Configs table
 *
 * Customizable branding and theming for client portals.
 */
export const whiteLabelConfigs = pgTable("white_label_configs", {
  id: uuid("id").primaryKey().defaultRandom(),
  clientId: uuid("client_id")
    .notNull()
    .references(() => clients.id, { onDelete: "cascade" })
    .unique(), // One config per client
  domain: text("domain").unique(), // Custom domain for white-labeled portal
  logoUrl: text("logo_url"),
  faviconUrl: text("favicon_url"),
  primaryColor: text("primary_color").default("#000000"),
  secondaryColor: text("secondary_color").default("#ffffff"),
  customCss: text("custom_css"), // Additional CSS overrides
  emailFromName: text("email_from_name"),
  isActive: boolean("is_active").default(true).notNull(),
  createdAt: timestamp("created_at", { withTimezone: true }).defaultNow().notNull(),
  updatedAt: timestamp("updated_at", { withTimezone: true }).defaultNow().notNull(),
});

/**
 * Form Templates table
 *
 * Reusable form templates with dynamic field definitions.
 */
export const formTemplates = pgTable("form_templates", {
  id: uuid("id").primaryKey().defaultRandom(),
  clientId: uuid("client_id").references(() => clients.id, { onDelete: "cascade" }), // Null for global templates
  name: text("name").notNull(),
  description: text("description"),
  fields: jsonb("fields")
    .$type<
      Array<{
        id: string;
        type: "text" | "email" | "number" | "select" | "textarea" | "checkbox" | "radio" | "date" | "file";
        label: string;
        placeholder?: string;
        required?: boolean;
        options?: Array<{ label: string; value: string }>; // For select/radio
        validation?: {
          min?: number;
          max?: number;
          pattern?: string;
          message?: string;
        };
      }>
    >()
    .notNull(),
  validationRules: jsonb("validation_rules").$type<{
    conditionalLogic?: Array<{
      field: string;
      condition: "equals" | "not_equals" | "contains";
      value: string;
      action: "show" | "hide" | "require";
      targetFields: string[];
    }>;
  }>(),
  createdBy: uuid("created_by").references(() => users.id),
  createdAt: timestamp("created_at", { withTimezone: true }).defaultNow().notNull(),
  updatedAt: timestamp("updated_at", { withTimezone: true }).defaultNow().notNull(),
});

/**
 * Webhook Endpoints table
 *
 * Configurable webhook endpoints for event notifications.
 */
export const webhookEndpoints = pgTable("webhook_endpoints", {
  id: uuid("id").primaryKey().defaultRandom(),
  clientId: uuid("client_id")
    .notNull()
    .references(() => clients.id, { onDelete: "cascade" }),
  url: text("url").notNull(),
  secret: text("secret").notNull(), // For HMAC signature verification
  events: jsonb("events")
    .$type<
      Array<
        | "client.created"
        | "client.updated"
        | "invoice.created"
        | "invoice.paid"
        | "project.completed"
        | "ticket.created"
        | "ticket.resolved"
        | "user.created"
      >
    >()
    .notNull(),
  isActive: boolean("is_active").default(true).notNull(),
  retryConfig: jsonb("retry_config").$type<{
    maxAttempts?: number;
    backoffMultiplier?: number;
    initialDelay?: number; // seconds
  }>(),
  createdBy: uuid("created_by").references(() => users.id),
  createdAt: timestamp("created_at", { withTimezone: true }).defaultNow().notNull(),
  updatedAt: timestamp("updated_at", { withTimezone: true }).defaultNow().notNull(),
});

/**
 * Webhook delivery status enum
 */
export const webhookStatusEnum = ["pending", "success", "failed", "retrying"] as const;
export type WebhookStatus = (typeof webhookStatusEnum)[number];

/**
 * Webhook Deliveries table
 *
 * Tracks webhook delivery attempts and status.
 */
export const webhookDeliveries = pgTable("webhook_deliveries", {
  id: uuid("id").primaryKey().defaultRandom(),
  endpointId: uuid("endpoint_id")
    .notNull()
    .references(() => webhookEndpoints.id, { onDelete: "cascade" }),
  eventType: text("event_type").notNull(),
  payload: jsonb("payload").notNull(),
  status: text("status", { enum: webhookStatusEnum }).default("pending").notNull(),
  attempts: integer("attempts").default(0).notNull(),
  lastAttemptAt: timestamp("last_attempt_at", { withTimezone: true }),
  response: jsonb("response").$type<{
    statusCode?: number;
    body?: string;
    error?: string;
  }>(),
  createdAt: timestamp("created_at", { withTimezone: true }).defaultNow().notNull(),
  updatedAt: timestamp("updated_at", { withTimezone: true }).defaultNow().notNull(),
});

/**
 * Relations
 */
export const accountHealthRelations = relations(accountHealth, ({ one }) => ({
  client: one(clients, {
    fields: [accountHealth.clientId],
    references: [clients.id],
  }),
}));

export const clientHealthSnapshotsRelations = relations(clientHealthSnapshots, ({ one }) => ({
  client: one(clients, {
    fields: [clientHealthSnapshots.clientId],
    references: [clients.id],
  }),
}));

export const storageConnectionsRelations = relations(storageConnections, ({ one, many }) => ({
  client: one(clients, {
    fields: [storageConnections.clientId],
    references: [clients.id],
  }),
  files: many(storageFiles),
}));

export const storageFilesRelations = relations(storageFiles, ({ one }) => ({
  connection: one(storageConnections, {
    fields: [storageFiles.connectionId],
    references: [storageConnections.id],
  }),
}));

export const dataPrivacyRequestsRelations = relations(dataPrivacyRequests, ({ one }) => ({
  client: one(clients, {
    fields: [dataPrivacyRequests.clientId],
    references: [clients.id],
  }),
  user: one(users, {
    fields: [dataPrivacyRequests.userId],
    references: [users.id],
  }),
}));

export const whiteLabelConfigsRelations = relations(whiteLabelConfigs, ({ one }) => ({
  client: one(clients, {
    fields: [whiteLabelConfigs.clientId],
    references: [clients.id],
  }),
}));

export const formTemplatesRelations = relations(formTemplates, ({ one }) => ({
  client: one(clients, {
    fields: [formTemplates.clientId],
    references: [clients.id],
  }),
  createdBy: one(users, {
    fields: [formTemplates.createdBy],
    references: [users.id],
  }),
}));

export const webhookEndpointsRelations = relations(webhookEndpoints, ({ one, many }) => ({
  client: one(clients, {
    fields: [webhookEndpoints.clientId],
    references: [clients.id],
  }),
  createdBy: one(users, {
    fields: [webhookEndpoints.createdBy],
    references: [users.id],
  }),
  deliveries: many(webhookDeliveries),
}));

export const webhookDeliveriesRelations = relations(webhookDeliveries, ({ one }) => ({
  endpoint: one(webhookEndpoints, {
    fields: [webhookDeliveries.endpointId],
    references: [webhookEndpoints.id],
  }),
}));

/**
 * TypeScript types
 */
export type AccountHealth = typeof accountHealth.$inferSelect;
export type NewAccountHealth = typeof accountHealth.$inferInsert;

export type ClientHealthSnapshot = typeof clientHealthSnapshots.$inferSelect;
export type NewClientHealthSnapshot = typeof clientHealthSnapshots.$inferInsert;

export type StorageConnection = typeof storageConnections.$inferSelect;
export type NewStorageConnection = typeof storageConnections.$inferInsert;

export type StorageFile = typeof storageFiles.$inferSelect;
export type NewStorageFile = typeof storageFiles.$inferInsert;

export type DataPrivacyRequest = typeof dataPrivacyRequests.$inferSelect;
export type NewDataPrivacyRequest = typeof dataPrivacyRequests.$inferInsert;

export type WhiteLabelConfig = typeof whiteLabelConfigs.$inferSelect;
export type NewWhiteLabelConfig = typeof whiteLabelConfigs.$inferInsert;

export type FormTemplate = typeof formTemplates.$inferSelect;
export type NewFormTemplate = typeof formTemplates.$inferInsert;

export type WebhookEndpoint = typeof webhookEndpoints.$inferSelect;
export type NewWebhookEndpoint = typeof webhookEndpoints.$inferInsert;

export type WebhookDelivery = typeof webhookDeliveries.$inferSelect;
export type NewWebhookDelivery = typeof webhookDeliveries.$inferInsert;
