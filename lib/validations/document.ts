import { z } from "zod";
import { checkDateOrder, cleanTextSchema } from "./fields";

/**
 * Document Upload Schema
 */
export const uploadDocumentSchema = z.object({
  name: cleanTextSchema.min(1, "Document name is required").max(255),
  description: cleanTextSchema.max(1000).optional().nullable(),
  fileName: cleanTextSchema.min(1, "File name is required"),
  fileSize: z.number().finite().int().positive("File size must be positive"),
  mimeType: cleanTextSchema.min(1, "MIME type is required"),
  storagePath: cleanTextSchema.min(1, "Storage path is required"),
  storageUrl: cleanTextSchema.url("Invalid storage URL").regex(/^https?:\/\/[^\s\/@]+(?:[\/?#][^\s]*)?$/i, "Use an HTTP or HTTPS URL without login credentials.").optional().nullable(),
  clientId: cleanTextSchema.uuid("Invalid client ID"),
  requestId: cleanTextSchema.uuid().optional().nullable(),
  isPublic: z.boolean().default(false),
  tags: z.array(cleanTextSchema).optional(),
  metadata: z.object({}).passthrough().optional(),
});

export type UploadDocumentInput = z.infer<typeof uploadDocumentSchema>;

/**
 * Contract Creation Schema
 */
export const createContractSchema = z.object({
  title: cleanTextSchema.min(1, "Title is required").max(255),
  description: cleanTextSchema.max(100000).optional().nullable(),
  contractNumber: cleanTextSchema.min(1, "Contract number is required").max(50),
  clientId: cleanTextSchema.uuid("Invalid client ID"),
  type: cleanTextSchema.min(1, "Contract type is required"),
  status: z.enum(["draft", "sent", "signed", "active", "expired", "terminated"]).default("draft"),
  startDate: cleanTextSchema.datetime().optional().nullable(),
  endDate: cleanTextSchema.datetime().optional().nullable(),
  value: z.number().finite().positive("Contract value must be positive").optional().nullable(),
  currency: z.enum(["USD", "EUR", "GBP", "CAD"]).default("USD"),
  billingCycle: z.enum(["monthly", "quarterly", "annually", "one-time"]).optional().nullable(),
  autoRenew: z.boolean().default(false),
  terms: z.object({}).passthrough().optional(),
  metadata: z.object({}).passthrough().optional(),
}).superRefine((data, context) => checkDateOrder(data.startDate, data.endDate, "endDate", context));

export type CreateContractInput = z.infer<typeof createContractSchema>;
