import { z } from "zod";

/**
 * Request creation validation schema
 */
export const createRequestSchema = z.object({
  clientId: z.string().uuid("Please select a client"),
  title: z.string().min(3, "Title must be at least 3 characters").max(200, "Title too long"),
  description: z.string().min(10, "Description must be at least 10 characters").optional(),
  priority: z.enum(["low", "medium", "high"]).default("medium"),
  status: z.enum(["pending", "in_progress", "completed", "cancelled"]).default("pending"),
  dueDate: z.string().optional(),
  customFields: z.record(z.any()).optional(),
});

export type CreateRequestInput = z.infer<typeof createRequestSchema>;

/**
 * Request update validation schema
 */
export const updateRequestSchema = createRequestSchema.partial().extend({
  status: z
    .enum(["pending", "in_progress", "completed", "cancelled", "on_hold", "awaiting_approval", "approved", "rejected"])
    .optional(),
  assignedTo: z.string().uuid().optional(),
});

export type UpdateRequestInput = z.infer<typeof updateRequestSchema>;

/**
 * Request comment validation schema
 */
export const createRequestCommentSchema = z.object({
  requestId: z.string().uuid(),
  content: z.string().min(1, "Comment cannot be empty").max(5000, "Comment too long"),
});

export type CreateRequestCommentInput = z.infer<typeof createRequestCommentSchema>;
