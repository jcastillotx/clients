import { z } from "zod";

/**
 * Validation schema for creating a meeting
 */
export const createMeetingSchema = z.object({
  clientId: z.string().uuid("Invalid client ID"),
  requestId: z.string().uuid("Invalid request ID").optional().nullable(),
  title: z.string().min(1, "Title is required").max(255),
  description: z.string().optional(),
  meetingType: z.enum(["discovery", "planning", "review", "qbr", "standup", "demo", "training", "support", "other"]),
  scheduledAt: z.string().datetime("Invalid date and time"),
  durationMinutes: z.number().int().min(15).max(480).default(60),
  location: z.string().optional(),
  meetingUrl: z.string().url("Invalid URL").optional().nullable(),
  attendees: z
    .array(
      z.object({
        userId: z.string().uuid().optional(),
        name: z.string().min(1),
        email: z.string().email(),
        role: z.string().optional(),
        isExternal: z.boolean().optional(),
      }),
    )
    .optional(),
  agenda: z.string().optional(),
});

export type CreateMeetingInput = z.infer<typeof createMeetingSchema>;

/**
 * Validation schema for updating a meeting
 */
export const updateMeetingSchema = createMeetingSchema.partial().extend({
  status: z.enum(["scheduled", "in_progress", "completed", "cancelled", "rescheduled"]).optional(),
  notes: z.string().optional(),
  recordingUrl: z.string().url("Invalid URL").optional().nullable(),
  actionItems: z
    .array(
      z.object({
        id: z.string(),
        description: z.string(),
        assignedTo: z.string().optional(),
        dueDate: z.string().optional(),
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
  meetingId: z.string().uuid("Invalid meeting ID"),
  section: z.string().min(1, "Section name is required"),
  content: z.string().min(1, "Content is required"),
  orderIndex: z.number().int().min(0).optional(),
});

export type CreateMeetingNoteInput = z.infer<typeof createMeetingNoteSchema>;

/**
 * Validation schema for updating attendee status
 */
export const updateAttendeeStatusSchema = z.object({
  status: z.enum(["invited", "accepted", "declined", "tentative", "attended", "no_show"]),
  responseNote: z.string().optional(),
});

export type UpdateAttendeeStatusInput = z.infer<typeof updateAttendeeStatusSchema>;
