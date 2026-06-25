import { z } from "zod";

export const discountTypeSchema = z.enum(["none", "percentage", "fixed"]);

export const invoiceLineItemSchema = z.object({
  description: z.string().min(1, "Description is required"),
  details: z.string().optional(),
  quantity: z.coerce.number().min(0.01, "Quantity must be positive"),
  unitPrice: z.coerce.number().min(0, "Unit price must be positive"),
});

export const createInvoiceSchema = z
  .object({
    clientId: z.string().uuid("Please select a client"),
    invoiceNumber: z.string().min(1, "Invoice number is required"),
    dueDate: z.string().optional(),
    notes: z.string().optional(),
    taxRate: z.coerce.number().min(0, "Tax rate cannot be negative").max(100, "Tax rate cannot exceed 100%"),
    discountType: discountTypeSchema.default("none"),
    discountValue: z.coerce.number().min(0, "Discount cannot be negative"),
    items: z.array(invoiceLineItemSchema).min(1, "At least one item is required"),
  })
  .superRefine((data, ctx) => {
    if (data.discountType === "percentage" && data.discountValue > 100) {
      ctx.addIssue({
        code: z.ZodIssueCode.custom,
        message: "Percentage discount cannot exceed 100%",
        path: ["discountValue"],
      });
    }
  });

export type CreateInvoiceInput = z.infer<typeof createInvoiceSchema>;
export type InvoiceLineItemInput = z.infer<typeof invoiceLineItemSchema>;
