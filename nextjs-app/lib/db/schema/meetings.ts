import { pgTable, uuid, text, timestamp, integer, jsonb } from "drizzle-orm/pg-core";
import { relations } from "drizzle-orm";
import { clients } from "./clients";
import { users } from "./users";
import { requests } from "./requests";

/**
 * Meeting status enum
 */
export const meetingStatusEnum = ["scheduled", "in_progress", "completed", "cancelled", "rescheduled"] as const;
export type MeetingStatus = (typeof meetingStatusEnum)[number];

/**
 * Meeting type enum
 */
export const meetingTypeEnum = [
  "discovery",
  "planning",
  "review",
  "qbr",
  "standup",
  "demo",
  "training",
  "support",
  "other",
] as const;
export type MeetingType = (typeof meetingTypeEnum)[number];

/**
 * Attendee status enum
 */
export const attendeeStatusEnum = ["invited", "accepted", "declined", "tentative", "attended", "no_show"] as const;
export type AttendeeStatus = (typeof attendeeStatusEnum)[number];

/**
 * Meetings table
 *
 * Stores all types of meetings including regular meetings and QBR meetings.
 */
export const meetings = pgTable("meetings", {
  id: uuid("id").primaryKey().defaultRandom(),
  clientId: uuid("client_id")
    .notNull()
    .references(() => clients.id, { onDelete: "cascade" }),
  requestId: uuid("request_id").references(() => requests.id, { onDelete: "set null" }),
  title: text("title").notNull(),
  description: text("description"),
  meetingType: text("meeting_type", { enum: meetingTypeEnum }).notNull(),
  status: text("status", { enum: meetingStatusEnum }).default("scheduled").notNull(),
  scheduledAt: timestamp("scheduled_at", { withTimezone: true }).notNull(),
  durationMinutes: integer("duration_minutes").default(60).notNull(),
  location: text("location"),
  meetingUrl: text("meeting_url"),
  attendees: jsonb("attendees").$type<
    Array<{
      userId?: string;
      name: string;
      email: string;
      role?: string;
      isExternal?: boolean;
    }>
  >(),
  agenda: text("agenda"),
  notes: text("notes"),
  recordingUrl: text("recording_url"),
  actionItems: jsonb("action_items").$type<
    Array<{
      id: string;
      description: string;
      assignedTo?: string;
      dueDate?: string;
      status: "pending" | "completed";
    }>
  >(),
  metadata: jsonb("metadata").$type<{
    presentationUrl?: string;
    nextQbrDate?: string;
    completedAt?: string;
    tags?: string[];
    customFields?: Record<string, any>;
  }>(),
  createdBy: uuid("created_by")
    .notNull()
    .references(() => users.id),
  createdAt: timestamp("created_at", { withTimezone: true }).defaultNow().notNull(),
  updatedAt: timestamp("updated_at", { withTimezone: true }).defaultNow().notNull(),
  deletedAt: timestamp("deleted_at", { withTimezone: true }),
});

/**
 * Meeting notes table
 *
 * Stores structured notes for meetings by section.
 */
export const meetingNotes = pgTable("meeting_notes", {
  id: uuid("id").primaryKey().defaultRandom(),
  meetingId: uuid("meeting_id")
    .notNull()
    .references(() => meetings.id, { onDelete: "cascade" }),
  section: text("section").notNull(),
  content: text("content").notNull(),
  orderIndex: integer("order_index").default(0),
  createdBy: uuid("created_by")
    .notNull()
    .references(() => users.id),
  createdAt: timestamp("created_at", { withTimezone: true }).defaultNow().notNull(),
  updatedAt: timestamp("updated_at", { withTimezone: true }).defaultNow().notNull(),
});

/**
 * Meeting attendees table
 *
 * Junction table for tracking meeting attendees and their status.
 */
export const meetingAttendees = pgTable("meeting_attendees", {
  id: uuid("id").primaryKey().defaultRandom(),
  meetingId: uuid("meeting_id")
    .notNull()
    .references(() => meetings.id, { onDelete: "cascade" }),
  userId: uuid("user_id")
    .notNull()
    .references(() => users.id, { onDelete: "cascade" }),
  status: text("status", { enum: attendeeStatusEnum }).default("invited").notNull(),
  attended: jsonb("attended").$type<{
    joinedAt?: string;
    leftAt?: string;
    duration?: number;
  }>(),
  responseNote: text("response_note"),
  createdAt: timestamp("created_at", { withTimezone: true }).defaultNow().notNull(),
  updatedAt: timestamp("updated_at", { withTimezone: true }).defaultNow().notNull(),
});

/**
 * Meeting relations
 */
export const meetingsRelations = relations(meetings, ({ one, many }) => ({
  client: one(clients, {
    fields: [meetings.clientId],
    references: [clients.id],
  }),
  request: one(requests, {
    fields: [meetings.requestId],
    references: [requests.id],
  }),
  creator: one(users, {
    fields: [meetings.createdBy],
    references: [users.id],
  }),
  notes: many(meetingNotes),
  attendeeRecords: many(meetingAttendees),
}));

export const meetingNotesRelations = relations(meetingNotes, ({ one }) => ({
  meeting: one(meetings, {
    fields: [meetingNotes.meetingId],
    references: [meetings.id],
  }),
  creator: one(users, {
    fields: [meetingNotes.createdBy],
    references: [users.id],
  }),
}));

export const meetingAttendeesRelations = relations(meetingAttendees, ({ one }) => ({
  meeting: one(meetings, {
    fields: [meetingAttendees.meetingId],
    references: [meetings.id],
  }),
  user: one(users, {
    fields: [meetingAttendees.userId],
    references: [users.id],
  }),
}));

/**
 * TypeScript types
 */
export type Meeting = typeof meetings.$inferSelect;
export type NewMeeting = typeof meetings.$inferInsert;
export type MeetingNote = typeof meetingNotes.$inferSelect;
export type NewMeetingNote = typeof meetingNotes.$inferInsert;
export type MeetingAttendee = typeof meetingAttendees.$inferSelect;
export type NewMeetingAttendee = typeof meetingAttendees.$inferInsert;
