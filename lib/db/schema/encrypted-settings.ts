import { pgTable, uuid, text, timestamp, boolean, uniqueIndex } from "drizzle-orm/pg-core";
import { relations } from "drizzle-orm";
import { clients } from "./clients";
import { users } from "./users";

/**
 * Integration provider categories
 */
export const integrationCategoryEnum = [
  "ai",
  "payments",
  "email",
  "social",
  "analytics",
  "automation",
  "calendar",
  "storage",
  "branding",
] as const;
export type IntegrationCategory = (typeof integrationCategoryEnum)[number];

/**
 * Integration provider names
 */
export const integrationProviderEnum = [
  // AI
  "anthropic",
  "openrouter",
  "openai",
  "google_ai",
  "google_stitch",
  // Payments
  "stripe",
  // Email
  "resend",
  "google_workspace",
  "office365",
  // Calendar OAuth
  "google_calendar",
  "microsoft_calendar",
  // Social OAuth
  "facebook",
  "instagram",
  "twitter",
  "linkedin",
  "tiktok",
  "youtube",
  "pinterest",
  "google_ads",
  "google_search_console",
  "google_business_profile",
  "semrush",
  "ahrefs",
  "canva",
  // Analytics
  "google_analytics",
  // Automation
  "zapier",
  // Storage (already in storageConnections, but keys stored here)
  "google_drive",
  "dropbox",
] as const;
export type IntegrationProvider = (typeof integrationProviderEnum)[number];

/**
 * Encrypted Settings table
 *
 * Stores encrypted API keys, OAuth credentials, and integration settings
 * at the client (company) level. Each client can configure their own
 * third-party integrations. Values are encrypted using AES-256-GCM
 * before being stored.
 *
 * The `setting_key` identifies the specific credential within a provider
 * (e.g., "api_key", "client_id", "client_secret", "webhook_secret").
 */
export const encryptedSettings = pgTable(
  "encrypted_settings",
  {
    id: uuid("id").primaryKey().defaultRandom(),
    clientId: uuid("client_id")
      .notNull()
      .references(() => clients.id, { onDelete: "cascade" }),
    provider: text("provider", { enum: integrationProviderEnum }).notNull(),
    category: text("category", { enum: integrationCategoryEnum }).notNull(),
    settingKey: text("setting_key").notNull(), // e.g., "api_key", "client_id", "client_secret"
    encryptedValue: text("encrypted_value").notNull(), // AES-256-GCM encrypted
    isActive: boolean("is_active").default(true).notNull(),
    label: text("label"), // Human-readable label, e.g., "Production API Key"
    lastRotatedAt: timestamp("last_rotated_at", { withTimezone: true }),
    lastVerifiedAt: timestamp("last_verified_at", { withTimezone: true }),
    updatedBy: uuid("updated_by").references(() => users.id),
    createdAt: timestamp("created_at", { withTimezone: true }).defaultNow().notNull(),
    updatedAt: timestamp("updated_at", { withTimezone: true }).defaultNow().notNull(),
  },
  (table) => ({
    clientProviderKeyIdx: uniqueIndex("encrypted_settings_client_provider_key").on(
      table.clientId,
      table.provider,
      table.settingKey,
    ),
  }),
);

/**
 * Relations
 */
export const encryptedSettingsRelations = relations(encryptedSettings, ({ one }) => ({
  client: one(clients, {
    fields: [encryptedSettings.clientId],
    references: [clients.id],
  }),
  updatedByUser: one(users, {
    fields: [encryptedSettings.updatedBy],
    references: [users.id],
  }),
}));

/**
 * TypeScript types
 */
export type EncryptedSetting = typeof encryptedSettings.$inferSelect;
export type NewEncryptedSetting = typeof encryptedSettings.$inferInsert;

/**
 * Provider configuration metadata (not stored in DB, used by UI)
 */
export interface ProviderConfig {
  provider: IntegrationProvider;
  category: IntegrationCategory;
  displayName: string;
  description: string;
  fields: Array<{
    key: string;
    label: string;
    placeholder: string;
    required: boolean;
    type: "secret" | "text";
  }>;
  docsUrl?: string;
}

export const PROVIDER_CONFIGS: ProviderConfig[] = [
  // AI Providers
  {
    provider: "anthropic",
    category: "ai",
    displayName: "Anthropic (Claude)",
    description: "AI assistant powered by Claude models for content generation and analysis.",
    fields: [
      { key: "api_key", label: "API Key", placeholder: "sk-ant-api03-...", required: true, type: "secret" },
    ],
    docsUrl: "https://docs.anthropic.com/en/api/getting-started",
  },
  {
    provider: "openrouter",
    category: "ai",
    displayName: "OpenRouter",
    description: "Access multiple AI models through a unified API.",
    fields: [
      { key: "api_key", label: "API Key", placeholder: "sk-or-v1-...", required: true, type: "secret" },
    ],
    docsUrl: "https://openrouter.ai/docs",
  },
  {
    provider: "openai",
    category: "ai",
    displayName: "OpenAI",
    description: "GPT models for text generation, DALL-E for image generation.",
    fields: [
      { key: "api_key", label: "API Key", placeholder: "sk-proj-...", required: true, type: "secret" },
    ],
    docsUrl: "https://platform.openai.com/docs",
  },
  {
    provider: "google_ai",
    category: "ai",
    displayName: "Google AI (Gemini)",
    description: "Google Gemini models for AI-powered features.",
    fields: [
      { key: "api_key", label: "API Key", placeholder: "AIzaSy...", required: true, type: "secret" },
    ],
    docsUrl: "https://ai.google.dev/docs",
  },
  {
    provider: "google_stitch",
    category: "ai",
    displayName: "Google Stitch",
    description: "Generate UI screens from text prompts and export HTML and screenshots.",
    fields: [
      { key: "api_key", label: "API Key", placeholder: "stitch_...", required: true, type: "secret" },
    ],
    docsUrl: "https://stitch.withgoogle.com/docs/",
  },
  // Payments
  {
    provider: "stripe",
    category: "payments",
    displayName: "Stripe",
    description: "Payment processing for invoices, subscriptions, and one-time charges.",
    fields: [
      { key: "publishable_key", label: "Publishable Key", placeholder: "pk_live_...", required: true, type: "text" },
      { key: "secret_key", label: "Secret Key", placeholder: "sk_live_...", required: true, type: "secret" },
      { key: "webhook_secret", label: "Webhook Secret", placeholder: "whsec_...", required: false, type: "secret" },
    ],
    docsUrl: "https://stripe.com/docs/api",
  },
  // Email
  {
    provider: "resend",
    category: "email",
    displayName: "Resend",
    description: "Transactional email delivery for invoices, notifications, and marketing.",
    fields: [
      { key: "api_key", label: "API Key", placeholder: "re_...", required: true, type: "secret" },
      { key: "from_email", label: "From Email", placeholder: "noreply@yourdomain.com", required: false, type: "text" },
      { key: "from_name", label: "From Name", placeholder: "Your Company", required: false, type: "text" },
    ],
    docsUrl: "https://resend.com/docs",
  },
  {
    provider: "google_workspace",
    category: "email",
    displayName: "Google Workspace",
    description: "Google Workspace email sending for invoices, notifications, and system alerts.",
    fields: [
      { key: "from_email", label: "From Email", placeholder: "noreply@yourdomain.com", required: true, type: "text" },
      { key: "smtp_user", label: "Mailbox Email", placeholder: "user@yourdomain.com", required: true, type: "text" },
      { key: "smtp_password", label: "App Password", placeholder: "Google app password", required: true, type: "secret" },
      { key: "smtp_host", label: "SMTP Host", placeholder: "smtp.gmail.com", required: false, type: "text" },
      { key: "smtp_port", label: "SMTP Port", placeholder: "587", required: false, type: "text" },
    ],
    docsUrl: "https://support.google.com/a/answer/176600",
  },
  {
    provider: "office365",
    category: "email",
    displayName: "Office 365",
    description: "Office 365 email sending through Microsoft 365 and Exchange Online.",
    fields: [
      { key: "from_email", label: "From Email", placeholder: "noreply@yourdomain.com", required: true, type: "text" },
      { key: "smtp_user", label: "Mailbox Email", placeholder: "user@yourdomain.com", required: true, type: "text" },
      { key: "smtp_password", label: "Mailbox Password", placeholder: "Office 365 mailbox password", required: true, type: "secret" },
      { key: "smtp_host", label: "SMTP Host", placeholder: "smtp.office365.com", required: false, type: "text" },
      { key: "smtp_port", label: "SMTP Port", placeholder: "587", required: false, type: "text" },
    ],
    docsUrl: "https://learn.microsoft.com/en-us/exchange/clients-and-mobile-in-exchange-online/authenticated-client-smtp-submission",
  },
  // Calendar OAuth
  {
    provider: "google_calendar",
    category: "calendar",
    displayName: "Google Calendar",
    description: "OAuth credentials for staff Google Calendar availability checks.",
    fields: [
      { key: "client_id", label: "OAuth Client ID", placeholder: "xxxxxxxx.apps.googleusercontent.com", required: true, type: "text" },
      { key: "client_secret", label: "OAuth Client Secret", placeholder: "GOCSPX-...", required: true, type: "secret" },
    ],
    docsUrl: "https://developers.google.com/calendar/api/guides/auth",
  },
  {
    provider: "microsoft_calendar",
    category: "calendar",
    displayName: "Microsoft Outlook Calendar",
    description: "OAuth credentials for staff Outlook Calendar availability checks through Microsoft Graph.",
    fields: [
      { key: "client_id", label: "Application (client) ID", placeholder: "xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx", required: true, type: "text" },
      { key: "client_secret", label: "Client Secret", placeholder: "Azure app client secret", required: true, type: "secret" },
      { key: "tenant_id", label: "Tenant ID", placeholder: "common", required: false, type: "text" },
    ],
    docsUrl: "https://learn.microsoft.com/en-us/graph/auth-v2-user",
  },
  {
    provider: "google_ads",
    category: "branding",
    displayName: "Google Ads",
    description: "Manage ad accounts, campaigns, and conversion integrations for paid search.",
    fields: [
      { key: "client_id", label: "OAuth Client ID", placeholder: "xxxxxxxx.apps.googleusercontent.com", required: true, type: "text" },
      { key: "client_secret", label: "OAuth Client Secret", placeholder: "GOCSPX-...", required: true, type: "secret" },
      { key: "refresh_token", label: "Refresh Token", placeholder: "1//0g...", required: true, type: "secret" },
      { key: "developer_token", label: "Developer Token", placeholder: "...", required: true, type: "secret" },
      { key: "customer_id", label: "Customer ID", placeholder: "123-456-7890", required: false, type: "text" },
    ],
    docsUrl: "https://developers.google.com/google-ads/api/docs/start",
  },
  {
    provider: "google_search_console",
    category: "branding",
    displayName: "Google Search Console",
    description: "Track search performance, indexing, and SEO health for branded properties.",
    fields: [
      { key: "client_id", label: "OAuth Client ID", placeholder: "xxxxxxxx.apps.googleusercontent.com", required: true, type: "text" },
      { key: "client_secret", label: "OAuth Client Secret", placeholder: "GOCSPX-...", required: true, type: "secret" },
      { key: "refresh_token", label: "Refresh Token", placeholder: "1//0g...", required: true, type: "secret" },
      { key: "site_url", label: "Site URL", placeholder: "https://example.com/", required: false, type: "text" },
    ],
    docsUrl: "https://developers.google.com/webmaster-tools",
  },
  {
    provider: "google_business_profile",
    category: "branding",
    displayName: "Google Business Profile",
    description: "Manage business listings, reviews, and location visibility for brand presence.",
    fields: [
      { key: "client_id", label: "OAuth Client ID", placeholder: "xxxxxxxx.apps.googleusercontent.com", required: true, type: "text" },
      { key: "client_secret", label: "OAuth Client Secret", placeholder: "GOCSPX-...", required: true, type: "secret" },
      { key: "refresh_token", label: "Refresh Token", placeholder: "1//0g...", required: true, type: "secret" },
      { key: "location_id", label: "Location ID", placeholder: "locations/123456789", required: false, type: "text" },
    ],
    docsUrl: "https://developers.google.com/my-business",
  },
  {
    provider: "semrush",
    category: "branding",
    displayName: "Semrush",
    description: "SEO, competitive research, and brand visibility tooling for campaigns and monitoring.",
    fields: [
      { key: "api_key", label: "API Key", placeholder: "...", required: true, type: "secret" },
      { key: "project_id", label: "Project ID", placeholder: "123456", required: false, type: "text" },
    ],
    docsUrl: "https://developer.semrush.com/",
  },
  {
    provider: "ahrefs",
    category: "branding",
    displayName: "Ahrefs",
    description: "Backlink, keyword, and site audit data for SEO and brand monitoring workflows.",
    fields: [
      { key: "api_key", label: "API Key", placeholder: "...", required: true, type: "secret" },
      { key: "workspace_id", label: "Workspace ID", placeholder: "...", required: false, type: "text" },
    ],
    docsUrl: "https://docs.ahrefs.com/docs/api",
  },
  {
    provider: "canva",
    category: "branding",
    displayName: "Canva",
    description: "Connect Canva brand kits and design workflows for branded creative production.",
    fields: [
      { key: "client_id", label: "Client ID", placeholder: "...", required: true, type: "text" },
      { key: "client_secret", label: "Client Secret", placeholder: "...", required: true, type: "secret" },
    ],
    docsUrl: "https://www.canva.dev/docs/connect/",
  },
  // Social OAuth
  {
    provider: "facebook",
    category: "social",
    displayName: "Facebook / Meta",
    description: "Connect Facebook Pages and Instagram for social media management.",
    fields: [
      { key: "app_id", label: "App ID", placeholder: "123456789012345", required: true, type: "text" },
      { key: "app_secret", label: "App Secret", placeholder: "abc123...", required: true, type: "secret" },
    ],
    docsUrl: "https://developers.facebook.com/docs",
  },
  {
    provider: "instagram",
    category: "social",
    displayName: "Instagram",
    description: "Instagram Business API for post scheduling and analytics.",
    fields: [
      { key: "app_id", label: "App ID", placeholder: "123456789012345", required: true, type: "text" },
      { key: "app_secret", label: "App Secret", placeholder: "abc123...", required: true, type: "secret" },
    ],
    docsUrl: "https://developers.facebook.com/docs/instagram-api",
  },
  {
    provider: "twitter",
    category: "social",
    displayName: "X (Twitter)",
    description: "Post to X/Twitter and track engagement metrics.",
    fields: [
      { key: "api_key", label: "API Key", placeholder: "...", required: true, type: "secret" },
      { key: "api_secret", label: "API Secret", placeholder: "...", required: true, type: "secret" },
      { key: "bearer_token", label: "Bearer Token", placeholder: "...", required: false, type: "secret" },
    ],
    docsUrl: "https://developer.x.com/en/docs",
  },
  {
    provider: "linkedin",
    category: "social",
    displayName: "LinkedIn",
    description: "LinkedIn Pages management and content publishing.",
    fields: [
      { key: "client_id", label: "Client ID", placeholder: "...", required: true, type: "text" },
      { key: "client_secret", label: "Client Secret", placeholder: "...", required: true, type: "secret" },
    ],
    docsUrl: "https://learn.microsoft.com/en-us/linkedin/",
  },
  {
    provider: "tiktok",
    category: "social",
    displayName: "TikTok",
    description: "TikTok for Business API integration.",
    fields: [
      { key: "app_id", label: "App ID", placeholder: "...", required: true, type: "text" },
      { key: "app_secret", label: "App Secret", placeholder: "...", required: true, type: "secret" },
    ],
    docsUrl: "https://developers.tiktok.com/doc",
  },
  {
    provider: "youtube",
    category: "social",
    displayName: "YouTube",
    description: "YouTube channel management and video publishing.",
    fields: [
      { key: "client_id", label: "Client ID", placeholder: "...", required: true, type: "text" },
      { key: "client_secret", label: "Client Secret", placeholder: "...", required: true, type: "secret" },
    ],
    docsUrl: "https://developers.google.com/youtube/v3",
  },
  {
    provider: "pinterest",
    category: "social",
    displayName: "Pinterest",
    description: "Pinterest API for pin scheduling and board management.",
    fields: [
      { key: "app_id", label: "App ID", placeholder: "...", required: true, type: "text" },
      { key: "app_secret", label: "App Secret", placeholder: "...", required: true, type: "secret" },
    ],
    docsUrl: "https://developers.pinterest.com/docs",
  },
  // Analytics
  {
    provider: "google_analytics",
    category: "analytics",
    displayName: "Google Analytics",
    description: "Track website traffic, conversions, and user behavior with GA4.",
    fields: [
      { key: "measurement_id", label: "Measurement ID", placeholder: "G-XXXXXXXXXX", required: true, type: "text" },
      { key: "api_secret", label: "API Secret", placeholder: "...", required: false, type: "secret" },
      { key: "property_id", label: "Property ID", placeholder: "123456789", required: false, type: "text" },
    ],
    docsUrl: "https://developers.google.com/analytics/devguides/reporting",
  },
  // Automation
  {
    provider: "zapier",
    category: "automation",
    displayName: "Zapier",
    description: "Connect to 5,000+ apps with Zapier webhooks and triggers.",
    fields: [
      { key: "webhook_url", label: "Webhook URL", placeholder: "https://hooks.zapier.com/hooks/catch/...", required: true, type: "text" },
      { key: "api_key", label: "API Key (NLA)", placeholder: "sk-ak-...", required: false, type: "secret" },
    ],
    docsUrl: "https://zapier.com/developer",
  },
  // Cloud Storage OAuth
  {
    provider: "google_drive",
    category: "storage",
    displayName: "Google Drive",
    description: "Sync files with Google Drive for document management.",
    fields: [
      { key: "client_id", label: "Client ID", placeholder: "...apps.googleusercontent.com", required: true, type: "text" },
      { key: "client_secret", label: "Client Secret", placeholder: "GOCSPX-...", required: true, type: "secret" },
    ],
    docsUrl: "https://developers.google.com/drive",
  },
  {
    provider: "dropbox",
    category: "storage",
    displayName: "Dropbox",
    description: "Sync files with Dropbox for cloud storage integration.",
    fields: [
      { key: "app_key", label: "App Key", placeholder: "...", required: true, type: "text" },
      { key: "app_secret", label: "App Secret", placeholder: "...", required: true, type: "secret" },
    ],
    docsUrl: "https://www.dropbox.com/developers",
  },
];
