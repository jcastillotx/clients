import { z } from "zod";

export const createPublicInvoiceCheckoutSchema = z.object({
  invoiceNumber: z.string().min(1, "Invoice number is required").max(100),
  paymentAmount: z.coerce.number().positive("Payment amount must be greater than 0"),
  email: z.string().email("Please enter a valid email"),
  businessName: z.string().min(1, "Business name is required").max(255),
  contactName: z.string().min(1, "Contact name is required").max(255),
  phone: z.string().max(50).optional().nullable(),
  businessInfo: z.string().max(2000).optional().nullable(),
});

export type CreatePublicInvoiceCheckoutInput = z.infer<typeof createPublicInvoiceCheckoutSchema>;
