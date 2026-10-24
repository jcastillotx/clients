import { z } from "zod";
import { phoneSchema, cleanTextSchema } from "./fields";

export const createPublicInvoiceCheckoutSchema = z.object({
  invoiceNumber: cleanTextSchema.min(1, "Invoice number is required").max(100),
  paymentAmount: z.coerce.number().finite().positive("Payment amount must be greater than 0").max(100000000).multipleOf(0.01, "Use at most two decimal places for payment amounts."),
  email: cleanTextSchema.max(254).email("Please enter a valid email"),
  businessName: cleanTextSchema.min(1, "Business name is required").max(255),
  contactName: cleanTextSchema.min(1, "Contact name is required").max(255),
  phone: phoneSchema.optional().nullable(),
  businessInfo: cleanTextSchema.max(2000).optional().nullable(),
  turnstileToken: cleanTextSchema.min(1).max(2048).optional().nullable(),
});

export type CreatePublicInvoiceCheckoutInput = z.infer<typeof createPublicInvoiceCheckoutSchema>;

export const lookupPublicInvoiceSchema = z.object({
  invoice: cleanTextSchema.min(1, "Invoice number is required").max(100),
});

export type LookupPublicInvoiceInput = z.infer<typeof lookupPublicInvoiceSchema>;
