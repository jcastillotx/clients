import { z } from "zod";
import { checkDateOrder, cleanTextSchema } from "./fields";

/**
 * Time Entry Creation Schema
 */
export const createTimeEntrySchema = z.object({
  userId: cleanTextSchema.uuid("Invalid user ID").optional(), // Optional if auto-set from auth
  clientId: cleanTextSchema.uuid("Invalid client ID").optional().nullable(),
  requestId: cleanTextSchema.uuid("Invalid request ID").optional().nullable(),
  projectId: cleanTextSchema.uuid("Invalid project ID").optional().nullable(),
  taskId: cleanTextSchema.uuid("Invalid task ID").optional().nullable(),
  description: cleanTextSchema.max(1000).optional().nullable(),
  startedAt: cleanTextSchema.datetime("Invalid start time"),
  endedAt: cleanTextSchema.datetime("Invalid end time").optional().nullable(),
  durationMinutes: z.number().finite().int().positive("Duration must be positive").optional(),
  isBillable: z.boolean().default(true),
  hourlyRate: z.number().finite().nonnegative("Hourly rate cannot be negative").optional().nullable(),
  status: z.enum(["pending", "approved", "billed", "rejected"]).default("pending"),
}).superRefine((data, context) => {
  checkDateOrder(data.startedAt, data.endedAt, "endedAt", context);
  if (data.startedAt && data.endedAt && data.durationMinutes !== undefined && Number.isFinite(Date.parse(data.startedAt)) && Number.isFinite(Date.parse(data.endedAt))) {
    const duration = Math.round((Date.parse(data.endedAt) - Date.parse(data.startedAt)) / 60000);
    if (duration !== data.durationMinutes) context.addIssue({code:z.ZodIssueCode.custom,path:["durationMinutes"],message:"Duration must match the selected start and end times."});
  }
});

export type CreateTimeEntryInput = z.infer<typeof createTimeEntrySchema>;

/**
 * Time Entry Update Schema
 */
export const updateTimeEntrySchema = z.object({
  description: cleanTextSchema.max(1000).optional().nullable(),
  startedAt: cleanTextSchema.datetime().optional(),
  endedAt: cleanTextSchema.datetime().optional().nullable(),
  durationMinutes: z.number().finite().int().positive().optional(),
  isBillable: z.boolean().optional(),
  hourlyRate: z.number().finite().positive().optional().nullable(),
  status: z.enum(["pending", "approved", "billed", "rejected"]).optional(),
}).superRefine((data, context) => {
  checkDateOrder(data.startedAt, data.endedAt, "endedAt", context);
  if (data.startedAt && data.endedAt && data.durationMinutes !== undefined && Number.isFinite(Date.parse(data.startedAt)) && Number.isFinite(Date.parse(data.endedAt))) {
    const duration = Math.round((Date.parse(data.endedAt) - Date.parse(data.startedAt)) / 60000);
    if (duration !== data.durationMinutes) context.addIssue({code:z.ZodIssueCode.custom,path:["durationMinutes"],message:"Duration must match the selected start and end times."});
  }
});

export type UpdateTimeEntryInput = z.infer<typeof updateTimeEntrySchema>;

/**
 * Request Time Entry Schema (Simplified)
 */
export const createRequestTimeEntrySchema = z.object({
  requestId: cleanTextSchema.uuid("Invalid request ID"),
  hours: z.number().finite().positive("Hours must be positive").max(24, "Hours cannot exceed 24"),
  note: cleanTextSchema.max(500).optional().nullable(),
  loggedAt: cleanTextSchema.datetime("Invalid logged time"),
});

export type CreateRequestTimeEntryInput = z.infer<typeof createRequestTimeEntrySchema>;
