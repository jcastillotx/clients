import { z } from "zod";
import { calendarDateSchema, cleanTextSchema } from "./fields";

import { recurringIntervals } from "@/lib/invoices/recurring";

export const discountTypeSchema = z.enum(["none", "percentage", "fixed"]);
export const billingTypeSchema = z.enum(["single", "recurring"]);
export const recurringIntervalSchema = z.enum(recurringIntervals);

export const invoiceLineItemSchema = z.object({
  description: cleanTextSchema.min(1, "Description is required"),
  details: cleanTextSchema.max(100000).optional(),
  quantity: z.coerce.number().finite().min(0.01, "Quantity must be positive"),
  unitPrice: z.coerce.number().finite().min(0, "Unit price must be positive"),
});

export const createInvoiceSchema = z
  .object({
    clientId: cleanTextSchema.uuid("Please select a client"),
    invoiceNumber: cleanTextSchema.min(1, "Invoice number is required"),
    dueDate: calendarDateSchema.or(z.literal("")).optional(),
    notes: cleanTextSchema.max(100000).optional(),
    billingType: billingTypeSchema.default("single"),
    recurringInterval: recurringIntervalSchema.optional(),
    taxRate: z.coerce.number().finite().min(0, "Tax rate cannot be negative").max(100, "Tax rate cannot exceed 100%"),
    discountType: discountTypeSchema.default("none"),
    discountValue: z.coerce.number().finite().min(0, "Discount cannot be negative"),
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

    if (data.billingType === "recurring" && !data.recurringInterval) {
      ctx.addIssue({
        code: z.ZodIssueCode.custom,
        message: "Select a recurring cycle",
        path: ["recurringInterval"],
      });
    }
  });

export type CreateInvoiceInput = z.infer<typeof createInvoiceSchema>;
export type InvoiceLineItemInput = z.infer<typeof invoiceLineItemSchema>;
