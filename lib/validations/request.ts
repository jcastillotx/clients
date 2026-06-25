import { z } from "zod";

export const requestTypeEnum = ["maintenance", "support", "design", "development", "content", "other"] as const;
export const requestStatusEnum = [
  "pending",
  "in_progress",
  "completed",
  "cancelled",
  "on_hold",
  "awaiting_approval",
  "approved",
  "rejected",
] as const;

/**
 * Request creation validation schema
 */
export const createRequestSchema = z.object({
  clientId: z.preprocess(
    (value) => (value === "" ? undefined : value),
    z.string().uuid("Please select a client").optional(),
  ),
  title: z.string().min(3, "Title must be at least 3 characters").max(200, "Title too long"),
  description: z.string().min(10, "Description must be at least 10 characters").optional(),
  priority: z.enum(["low", "medium", "high"]).default("medium"),
  status: z.enum(requestStatusEnum).default("pending"),
  type: z.enum(requestTypeEnum).default("support"),
  assignedTo: z.string().uuid().nullable().optional(),
  dueDate: z.string().optional(),
  customFields: z.record(z.any()).optional(),
});

export type CreateRequestInput = z.infer<typeof createRequestSchema>;
export type RequestType = (typeof requestTypeEnum)[number];
export type RequestStatus = (typeof requestStatusEnum)[number];

/**
 * Request update validation schema
 */
export const updateRequestSchema = createRequestSchema.partial().extend({
  status: z.enum(requestStatusEnum).optional(),
  assignedTo: z.string().uuid().nullable().optional(),
});

export type UpdateRequestInput = z.infer<typeof updateRequestSchema>;

export const bulkRequestsSchema = z.object({
  ids: z.array(z.string().uuid()).min(1).max(100),
  action: z.enum(["close", "delete"]),
});

export type BulkRequestsInput = z.infer<typeof bulkRequestsSchema>;

/**
 * Request comment validation schema
 */
export const createRequestCommentSchema = z.object({
  requestId: z.string().uuid(),
  content: z.string().min(1, "Comment cannot be empty").max(5000, "Comment too long"),
});

export type CreateRequestCommentInput = z.infer<typeof createRequestCommentSchema>;
