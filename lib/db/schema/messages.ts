import { pgTable, uuid, text, timestamp, boolean, jsonb, bigint, index, unique } from "drizzle-orm/pg-core";
import { relations } from "drizzle-orm";
import { users } from "./users";
import { clients } from "./clients";

/**
 * Message type enum
 */
export const messageTypeEnum = ["text", "file", "system"] as const;
export type MessageType = (typeof messageTypeEnum)[number];

/**
 * Context type enum for conversations
 */
export const contextTypeEnum = ["request", "project", "general"] as const;
export type ContextType = (typeof contextTypeEnum)[number];

/**
 * Participant role enum
 */
export const participantRoleEnum = ["client", "staff"] as const;
export type ParticipantRole = (typeof participantRoleEnum)[number];

/**
 * Conversations table
 *
 * Stores message conversations between users with optional context linking
 * to requests or projects.
 */
export const conversations = pgTable(
  "conversations",
  {
    id: uuid("id").primaryKey().defaultRandom(),
    clientId: uuid("client_id")
      .references(() => clients.id, { onDelete: "cascade" })
      .notNull(),
    title: text("title"),
    contextType: text("context_type", { enum: contextTypeEnum }),
    contextId: uuid("context_id"),
    isClosed: boolean("is_closed").default(false).notNull(),
    lastMessageAt: timestamp("last_message_at", { withTimezone: true }),
    createdAt: timestamp("created_at", { withTimezone: true }).defaultNow().notNull(),
    updatedAt: timestamp("updated_at", { withTimezone: true }).defaultNow().notNull(),
  },
  (table) => ({
    clientIdIsClosedIdx: index("conversations_client_id_is_closed_idx").on(table.clientId, table.isClosed),
    clientIdContextIdx: index("conversations_context_idx").on(table.clientId, table.contextType, table.contextId),
    clientIdLastMessageIdx: index("conversations_client_id_last_message_at_idx").on(
      table.clientId,
      table.lastMessageAt,
    ),
  }),
);

/**
 * Conversation participants pivot table
 *
 * Links users to conversations with their role (client or staff).
 */
export const conversationParticipants = pgTable(
  "conversation_participants",
  {
    id: uuid("id").primaryKey().defaultRandom(),
    conversationId: uuid("conversation_id")
      .references(() => conversations.id, { onDelete: "cascade" })
      .notNull(),
    userId: uuid("user_id")
      .references(() => users.id, { onDelete: "cascade" })
      .notNull(),
    role: text("role", { enum: participantRoleEnum }),
    createdAt: timestamp("created_at", { withTimezone: true }).defaultNow().notNull(),
    updatedAt: timestamp("updated_at", { withTimezone: true }).defaultNow().notNull(),
  },
  (table) => ({
    conversationUserUnique: unique("conversation_participants_conversation_id_user_id_unique").on(
      table.conversationId,
      table.userId,
    ),
  }),
);

/**
 * Messages table
 *
 * Stores individual messages within conversations with support for
 * text, files, and system messages.
 */
export const messages = pgTable(
  "messages",
  {
    id: uuid("id").primaryKey().defaultRandom(),
    conversationId: uuid("conversation_id")
      .references(() => conversations.id, { onDelete: "cascade" })
      .notNull(),
    senderId: uuid("sender_id").references(() => users.id, { onDelete: "set null" }),
    body: text("body"),
    type: text("type", { enum: messageTypeEnum }).default("text").notNull(),
    meta: jsonb("meta").$type<{
      editHistory?: Array<{ editedAt: string; oldBody: string }>;
      systemAction?: string;
      [key: string]: unknown;
    }>(),
    mentions: jsonb("mentions").$type<string[]>(),
    isPinned: boolean("is_pinned").default(false).notNull(),
    pinnedAt: timestamp("pinned_at", { withTimezone: true }),
    pinnedBy: uuid("pinned_by").references(() => users.id, { onDelete: "set null" }),
    createdAt: timestamp("created_at", { withTimezone: true }).defaultNow().notNull(),
    updatedAt: timestamp("updated_at", { withTimezone: true }).defaultNow().notNull(),
  },
  (table) => ({
    conversationIdCreatedAtIdx: index("messages_conversation_id_created_at_idx").on(
      table.conversationId,
      table.createdAt,
    ),
  }),
);

/**
 * Message reads table
 *
 * Tracks which users have read which messages for read receipts.
 */
export const messageReads = pgTable(
  "message_reads",
  {
    id: uuid("id").primaryKey().defaultRandom(),
    messageId: uuid("message_id")
      .references(() => messages.id, { onDelete: "cascade" })
      .notNull(),
    userId: uuid("user_id")
      .references(() => users.id, { onDelete: "cascade" })
      .notNull(),
    readAt: timestamp("read_at", { withTimezone: true }),
    createdAt: timestamp("created_at", { withTimezone: true }).defaultNow().notNull(),
    updatedAt: timestamp("updated_at", { withTimezone: true }).defaultNow().notNull(),
  },
  (table) => ({
    messageUserUnique: unique("message_reads_message_id_user_id_unique").on(table.messageId, table.userId),
    userIdReadAtIdx: index("message_reads_user_id_read_at_idx").on(table.userId, table.readAt),
  }),
);

/**
 * Message attachments table
 *
 * Stores file attachments associated with messages.
 */
export const messageAttachments = pgTable("message_attachments", {
  id: uuid("id").primaryKey().defaultRandom(),
  messageId: uuid("message_id")
    .references(() => messages.id, { onDelete: "cascade" })
    .notNull(),
  disk: text("disk").default("attachments").notNull(),
  path: text("path").notNull(),
  filename: text("filename").notNull(),
  mimeType: text("mime_type"),
  sizeBytes: bigint("size_bytes", { mode: "number" }).default(0).notNull(),
  createdAt: timestamp("created_at", { withTimezone: true }).defaultNow().notNull(),
  updatedAt: timestamp("updated_at", { withTimezone: true }).defaultNow().notNull(),
});

/**
 * Conversations relations
 */
export const conversationsRelations = relations(conversations, ({ one, many }) => ({
  client: one(clients, {
    fields: [conversations.clientId],
    references: [clients.id],
  }),
  participants: many(conversationParticipants),
  messages: many(messages),
}));

/**
 * Conversation participants relations
 */
export const conversationParticipantsRelations = relations(conversationParticipants, ({ one }) => ({
  conversation: one(conversations, {
    fields: [conversationParticipants.conversationId],
    references: [conversations.id],
  }),
  user: one(users, {
    fields: [conversationParticipants.userId],
    references: [users.id],
  }),
}));

/**
 * Messages relations
 */
export const messagesRelations = relations(messages, ({ one, many }) => ({
  conversation: one(conversations, {
    fields: [messages.conversationId],
    references: [conversations.id],
  }),
  sender: one(users, {
    fields: [messages.senderId],
    references: [users.id],
  }),
  pinnedByUser: one(users, {
    fields: [messages.pinnedBy],
    references: [users.id],
  }),
  reads: many(messageReads),
  attachments: many(messageAttachments),
}));

/**
 * Message reads relations
 */
export const messageReadsRelations = relations(messageReads, ({ one }) => ({
  message: one(messages, {
    fields: [messageReads.messageId],
    references: [messages.id],
  }),
  user: one(users, {
    fields: [messageReads.userId],
    references: [users.id],
  }),
}));

/**
 * Message attachments relations
 */
export const messageAttachmentsRelations = relations(messageAttachments, ({ one }) => ({
  message: one(messages, {
    fields: [messageAttachments.messageId],
    references: [messages.id],
  }),
}));
