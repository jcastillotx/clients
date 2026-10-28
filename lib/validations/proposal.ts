import { cleanTextSchema } from "./fields";
import { z } from "zod";

/**
 * Proposal Line Item Schema
 */
const proposalLineItemSchema = z.object({
  id: cleanTextSchema,
  description: cleanTextSchema.min(1, "Description is required"),
  quantity: z.number().finite().positive("Quantity must be positive"),
  unitPrice: z.number().finite().nonnegative("Unit price must be non-negative"),
  amount: z.number().finite().nonnegative("Amount must be non-negative"),
  category: cleanTextSchema.max(100000).optional(),
});

/**
 * Proposal Creation Schema
 */
export const createProposalSchema = z.object({
  clientId: cleanTextSchema.guid("Invalid client ID"),
  title: cleanTextSchema.min(1, "Title is required").max(255),
  description: cleanTextSchema.max(100000).optional().nullable(),
  totalAmount: z.number().finite().positive("Total amount must be positive"),
  currency: z.enum(["USD", "EUR", "GBP", "CAD"]).default("USD"),
  validUntil: cleanTextSchema.datetime().optional().nullable(),
  terms: cleanTextSchema.max(100000).optional().nullable(),
  lineItems: z.array(proposalLineItemSchema).min(1, "At least one line item is required"),
  metadata: z.object({
    notes: cleanTextSchema.max(100000).optional(),
    internalNotes: cleanTextSchema.max(100000).optional(),
    tags: z.array(cleanTextSchema).optional(),
    attachments: z.array(z.object({
      name: cleanTextSchema,
      url: cleanTextSchema.url().regex(/^https?:\/\/[^\s\/@]+(?:[\/?#][^\s]*)?$/i, "Use an HTTP or HTTPS URL without login credentials."),
      size: z.number().finite(),
    })).optional(),
  }).optional(),
});

export type CreateProposalInput = z.infer<typeof createProposalSchema>;

/**
 * Proposal Update Schema
 */
export const updateProposalSchema = z.object({
  title: cleanTextSchema.min(1).max(255).optional(),
  description: cleanTextSchema.max(100000).optional().nullable(),
  status: z.enum(["draft", "sent", "viewed", "accepted", "rejected", "expired"]).optional(),
  totalAmount: z.number().finite().positive().optional(),
  currency: z.enum(["USD", "EUR", "GBP", "CAD"]).optional(),
  validUntil: cleanTextSchema.datetime().optional().nullable(),
  terms: cleanTextSchema.max(100000).optional().nullable(),
  lineItems: z.array(proposalLineItemSchema).optional(),
  metadata: z.object({}).passthrough().optional(),
});

export type UpdateProposalInput = z.infer<typeof updateProposalSchema>;
