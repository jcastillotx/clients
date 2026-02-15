import { z } from "zod";

/**
 * Document Upload Schema
 */
export const uploadDocumentSchema = z.object({
  name: z.string().min(1, "Document name is required").max(255),
  description: z.string().max(1000).optional().nullable(),
  fileName: z.string().min(1, "File name is required"),
  fileSize: z.number().int().positive("File size must be positive"),
  mimeType: z.string().min(1, "MIME type is required"),
  storagePath: z.string().min(1, "Storage path is required"),
  storageUrl: z.string().url("Invalid storage URL").optional().nullable(),
  clientId: z.string().uuid("Invalid client ID"),
  requestId: z.string().uuid().optional().nullable(),
  isPublic: z.boolean().default(false),
  tags: z.array(z.string()).optional(),
  metadata: z.object({}).passthrough().optional(),
});

export type UploadDocumentInput = z.infer<typeof uploadDocumentSchema>;

/**
 * Contract Creation Schema
 */
export const createContractSchema = z.object({
  title: z.string().min(1, "Title is required").max(255),
  description: z.string().optional().nullable(),
  contractNumber: z.string().min(1, "Contract number is required").max(50),
  clientId: z.string().uuid("Invalid client ID"),
  type: z.string().min(1, "Contract type is required"),
  status: z.enum(["draft", "sent", "signed", "active", "expired", "terminated"]).default("draft"),
  startDate: z.string().datetime().optional().nullable(),
  endDate: z.string().datetime().optional().nullable(),
  value: z.number().positive("Contract value must be positive").optional().nullable(),
  currency: z.enum(["USD", "EUR", "GBP", "CAD"]).default("USD"),
  billingCycle: z.enum(["monthly", "quarterly", "annually", "one-time"]).optional().nullable(),
  autoRenew: z.boolean().default(false),
  terms: z.object({}).passthrough().optional(),
  metadata: z.object({}).passthrough().optional(),
});

export type CreateContractInput = z.infer<typeof createContractSchema>;
