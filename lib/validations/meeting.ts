import { z } from "zod";
import { calendarDateSchema, cleanTextSchema } from "./fields";

/**
 * Validation schema for creating a meeting
 */
export const createMeetingSchema = z.object({
  clientId: cleanTextSchema.guid("Invalid client ID"),
  requestId: cleanTextSchema.guid("Invalid request ID").optional().nullable(),
  title: cleanTextSchema.min(1, "Title is required").max(255),
  description: cleanTextSchema.max(100000).optional(),
  meetingType: z.enum(["discovery", "planning", "review", "qbr", "standup", "demo", "training", "support", "other"]),
  scheduledAt: cleanTextSchema.datetime("Invalid date and time"),
  durationMinutes: z.number().finite().int().min(15).max(480).default(60),
  location: cleanTextSchema.max(100000).optional(),
  meetingUrl: cleanTextSchema.url("Invalid URL").regex(/^https?:\/\/[^\s\/@]+(?:[\/?#][^\s]*)?$/i, "Use an HTTP or HTTPS URL without login credentials.").optional().nullable(),
  attendees: z
    .array(
      z.object({
        userId: cleanTextSchema.guid().optional(),
        name: cleanTextSchema.min(1),
        email: cleanTextSchema.max(254).email(),
        role: cleanTextSchema.max(100000).optional(),
        isExternal: z.boolean().optional(),
      }),
    )
    .optional(),
  agenda: cleanTextSchema.max(100000).optional(),
});

export type CreateMeetingInput = z.infer<typeof createMeetingSchema>;

/**
 * Validation schema for updating a meeting
 */
export const updateMeetingSchema = createMeetingSchema.partial().extend({
  status: z.enum(["scheduled", "in_progress", "completed", "cancelled", "rescheduled"]).optional(),
  notes: cleanTextSchema.max(100000).optional(),
  recordingUrl: cleanTextSchema.url("Invalid URL").regex(/^https?:\/\/[^\s\/@]+(?:[\/?#][^\s]*)?$/i, "Use an HTTP or HTTPS URL without login credentials.").optional().nullable(),
  actionItems: z
    .array(
      z.object({
        id: cleanTextSchema,
        description: cleanTextSchema,
        assignedTo: cleanTextSchema.max(100000).optional(),
        dueDate: calendarDateSchema.optional(),
        status: z.enum(["pending", "completed"]),
      }),
    )
    .optional(),
});

export type UpdateMeetingInput = z.infer<typeof updateMeetingSchema>;

/**
 * Validation schema for meeting notes
 */
export const createMeetingNoteSchema = z.object({
  meetingId: cleanTextSchema.guid("Invalid meeting ID"),
  section: cleanTextSchema.min(1, "Section name is required"),
  content: cleanTextSchema.min(1, "Content is required"),
  orderIndex: z.number().finite().int().min(0).optional(),
});

export type CreateMeetingNoteInput = z.infer<typeof createMeetingNoteSchema>;

/**
 * Validation schema for updating attendee status
 */
export const updateAttendeeStatusSchema = z.object({
  status: z.enum(["invited", "accepted", "declined", "tentative", "attended", "no_show"]),
  responseNote: cleanTextSchema.max(100000).optional(),
});

export type UpdateAttendeeStatusInput = z.infer<typeof updateAttendeeStatusSchema>;
