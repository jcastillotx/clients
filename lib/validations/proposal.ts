import { z } from "zod";

/**
 * Proposal Line Item Schema
 */
const proposalLineItemSchema = z.object({
  id: z.string(),
  description: z.string().min(1, "Description is required"),
  quantity: z.number().positive("Quantity must be positive"),
  unitPrice: z.number().nonnegative("Unit price must be non-negative"),
  amount: z.number().nonnegative("Amount must be non-negative"),
  category: z.string().optional(),
});

/**
 * Proposal Creation Schema
 */
export const createProposalSchema = z.object({
  clientId: z.string().uuid("Invalid client ID"),
  title: z.string().min(1, "Title is required").max(255),
  description: z.string().optional().nullable(),
  totalAmount: z.number().positive("Total amount must be positive"),
  currency: z.enum(["USD", "EUR", "GBP", "CAD"]).default("USD"),
  validUntil: z.string().datetime().optional().nullable(),
  terms: z.string().optional().nullable(),
  lineItems: z.array(proposalLineItemSchema).min(1, "At least one line item is required"),
  metadata: z.object({
    notes: z.string().optional(),
    internalNotes: z.string().optional(),
    tags: z.array(z.string()).optional(),
    attachments: z.array(z.object({
      name: z.string(),
      url: z.string().url(),
      size: z.number(),
    })).optional(),
  }).optional(),
});

export type CreateProposalInput = z.infer<typeof createProposalSchema>;

/**
 * Proposal Update Schema
 */
export const updateProposalSchema = z.object({
  title: z.string().min(1).max(255).optional(),
  description: z.string().optional().nullable(),
  status: z.enum(["draft", "sent", "viewed", "accepted", "rejected", "expired"]).optional(),
  totalAmount: z.number().positive().optional(),
  currency: z.enum(["USD", "EUR", "GBP", "CAD"]).optional(),
  validUntil: z.string().datetime().optional().nullable(),
  terms: z.string().optional().nullable(),
  lineItems: z.array(proposalLineItemSchema).optional(),
  metadata: z.object({}).passthrough().optional(),
});

export type UpdateProposalInput = z.infer<typeof updateProposalSchema>;
