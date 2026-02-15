import { z } from "zod";

/**
 * Time Entry Creation Schema
 */
export const createTimeEntrySchema = z.object({
  userId: z.string().uuid("Invalid user ID").optional(), // Optional if auto-set from auth
  clientId: z.string().uuid("Invalid client ID").optional().nullable(),
  requestId: z.string().uuid("Invalid request ID").optional().nullable(),
  projectId: z.string().uuid("Invalid project ID").optional().nullable(),
  taskId: z.string().uuid("Invalid task ID").optional().nullable(),
  description: z.string().max(1000).optional().nullable(),
  startedAt: z.string().datetime("Invalid start time"),
  endedAt: z.string().datetime("Invalid end time").optional().nullable(),
  durationMinutes: z.number().int().positive("Duration must be positive").optional(),
  isBillable: z.boolean().default(true),
  hourlyRate: z.number().positive("Hourly rate must be positive").optional().nullable(),
  status: z.enum(["pending", "approved", "billed", "rejected"]).default("pending"),
});

export type CreateTimeEntryInput = z.infer<typeof createTimeEntrySchema>;

/**
 * Time Entry Update Schema
 */
export const updateTimeEntrySchema = z.object({
  description: z.string().max(1000).optional().nullable(),
  startedAt: z.string().datetime().optional(),
  endedAt: z.string().datetime().optional().nullable(),
  durationMinutes: z.number().int().positive().optional(),
  isBillable: z.boolean().optional(),
  hourlyRate: z.number().positive().optional().nullable(),
  status: z.enum(["pending", "approved", "billed", "rejected"]).optional(),
});

export type UpdateTimeEntryInput = z.infer<typeof updateTimeEntrySchema>;

/**
 * Request Time Entry Schema (Simplified)
 */
export const createRequestTimeEntrySchema = z.object({
  requestId: z.string().uuid("Invalid request ID"),
  hours: z.number().positive("Hours must be positive").max(24, "Hours cannot exceed 24"),
  note: z.string().max(500).optional().nullable(),
  loggedAt: z.string().datetime("Invalid logged time"),
});

export type CreateRequestTimeEntryInput = z.infer<typeof createRequestTimeEntrySchema>;
