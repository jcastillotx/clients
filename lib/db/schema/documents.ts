import { pgTable, uuid, text, timestamp, boolean, integer, jsonb } from "drizzle-orm/pg-core";

export const folders = pgTable("folders", {
  id: uuid("id").primaryKey().defaultRandom(),
  name: text("name").notNull(),
  description: text("description"),
  clientId: uuid("client_id").notNull(),
  type: text("type"), // e.g., 'brand_management', 'general'
  createdAt: timestamp("created_at", { withTimezone: true }).defaultNow(),
  updatedAt: timestamp("updated_at", { withTimezone: true }).defaultNow(),
});

export const documents = pgTable("documents", {
  id: uuid("id").primaryKey().defaultRandom(),

  // Basic info
  name: text("name").notNull(),
  description: text("description"),
  fileName: text("file_name").notNull(),
  fileSize: integer("file_size").notNull(), // in bytes
  mimeType: text("mime_type").notNull(),

  // Storage
  storagePath: text("storage_path").notNull(), // Path in Supabase Storage
  storageUrl: text("storage_url"), // Public URL if shared

  // Relationships
  clientId: uuid("client_id").notNull(),
  requestId: uuid("request_id"), // Optional - link to service request
  folderId: uuid("folder_id"), // Optional - link to folder
  uploadedBy: uuid("uploaded_by").notNull(), // User who uploaded

  // Versioning
  version: integer("version").default(1).notNull(),
  parentDocumentId: uuid("parent_document_id"), // For versioning
  isLatestVersion: boolean("is_latest_version").default(true).notNull(),

  // Access control
  isPublic: boolean("is_public").default(false).notNull(),
  sharedWith: jsonb("shared_with").$type<string[]>(), // Array of user IDs

  // Metadata
  tags: jsonb("tags").$type<string[]>(),
  metadata: jsonb("metadata").$type<Record<string, any>>(),

  // Timestamps
  createdAt: timestamp("created_at", { withTimezone: true }).defaultNow(),
  updatedAt: timestamp("updated_at", { withTimezone: true }).defaultNow(),
  deletedAt: timestamp("deleted_at", { withTimezone: true }),
});

export const contracts = pgTable("contracts", {
  id: uuid("id").primaryKey().defaultRandom(),

  // Basic info
  title: text("title").notNull(),
  description: text("description"),
  contractNumber: text("contract_number").notNull().unique(),

  // Parties
  clientId: uuid("client_id").notNull(),

  // Contract details
  type: text("type").notNull(), // service_agreement, nda, sow, etc.
  status: text("status").default("draft").notNull(), // draft, pending_signature, signed, expired, terminated

  // Dates
  startDate: timestamp("start_date", { withTimezone: true }),
  endDate: timestamp("end_date", { withTimezone: true }),
  signedDate: timestamp("signed_date", { withTimezone: true }),

  // Financial
  value: integer("value"), // Contract value in cents
  currency: text("currency").default("USD"),
  billingCycle: text("billing_cycle"), // monthly, quarterly, annually, one-time

  // Document
  documentId: uuid("document_id"), // Link to documents table

  // Signatures
  clientSignedBy: text("client_signed_by"),
  clientSignedAt: timestamp("client_signed_at", { withTimezone: true }),
  companySignedBy: uuid("company_signed_by"), // User ID
  companySignedAt: timestamp("company_signed_at", { withTimezone: true }),

  // Terms
  terms: jsonb("terms").$type<Record<string, any>>(),
  autoRenew: boolean("auto_renew").default(false),
  noticeRequired: integer("notice_required"), // Days notice required for termination

  // Metadata
  tags: jsonb("tags").$type<string[]>(),
  customFields: jsonb("custom_fields").$type<Record<string, any>>(),

  // Timestamps
  createdAt: timestamp("created_at", { withTimezone: true }).defaultNow(),
  updatedAt: timestamp("updated_at", { withTimezone: true }).defaultNow(),
  deletedAt: timestamp("deleted_at", { withTimezone: true }),
});

export const documentShares = pgTable("document_shares", {
  id: uuid("id").primaryKey().defaultRandom(),

  documentId: uuid("document_id").notNull(),
  sharedWithUserId: uuid("shared_with_user_id"),
  sharedWithEmail: text("shared_with_email"), // For external shares

  // Access control
  canView: boolean("can_view").default(true).notNull(),
  canDownload: boolean("can_download").default(true).notNull(),
  canEdit: boolean("can_edit").default(false).notNull(),

  // Expiration
  expiresAt: timestamp("expires_at", { withTimezone: true }),

  // Tracking
  sharedBy: uuid("shared_by").notNull(),
  lastAccessedAt: timestamp("last_accessed_at", { withTimezone: true }),
  accessCount: integer("access_count").default(0),

  // Timestamps
  createdAt: timestamp("created_at", { withTimezone: true }).defaultNow(),
  updatedAt: timestamp("updated_at", { withTimezone: true }).defaultNow(),
});

export type Document = typeof documents.$inferSelect;
export type NewDocument = typeof documents.$inferInsert;

export type Folder = typeof folders.$inferSelect;
export type NewFolder = typeof folders.$inferInsert;

export type Contract = typeof contracts.$inferSelect;
export type NewContract = typeof contracts.$inferInsert;

export type DocumentShare = typeof documentShares.$inferSelect;
export type NewDocumentShare = typeof documentShares.$inferInsert;

// Document types enum
export const DocumentTypes = {
  PDF: "application/pdf",
  DOC: "application/msword",
  DOCX: "application/vnd.openxmlformats-officedocument.wordprocessingml.document",
  XLS: "application/vnd.ms-excel",
  XLSX: "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
  IMAGE: "image/*",
  VIDEO: "video/*",
} as const;

// Contract types enum
export const ContractTypes = {
  SERVICE_AGREEMENT: "service_agreement",
  NDA: "nda",
  SOW: "sow",
  CONSULTING: "consulting",
  RETAINER: "retainer",
  PROJECT_BASED: "project_based",
  CUSTOM: "custom",
} as const;

// Contract status enum
export const ContractStatus = {
  DRAFT: "draft",
  PENDING_SIGNATURE: "pending_signature",
  SIGNED: "signed",
  ACTIVE: "active",
  EXPIRED: "expired",
  TERMINATED: "terminated",
  RENEWED: "renewed",
} as const;
