import { z } from "zod";
import {
  calendarDateSchema,
  checkDateOrder,
  emailSchema,
  phoneSchema,
  cleanTextSchema,
} from "./fields";
const name = cleanTextSchema.min(1, "Enter a name or title.").max(255);
const notes = cleanTextSchema.max(20000).nullable().optional();
const id = z.string().uuid();
const date = calendarDateSchema.or(z.literal("")).nullable().optional();
const money = z.coerce.number().finite().nonnegative().max(100000000);
export const taskFormSchema = z.object({
  boardId: id,
  columnId: id,
  parentId: id.nullable().optional(),
  title: name,
  description: notes,
  priority: z.enum(["low", "normal", "high", "urgent"]).optional(),
  dueDate: date,
  estimatedHours: z.coerce.number().finite().positive().nullable().optional(),
  assignees: z.array(id).max(100).optional(),
  labels: z.array(id).max(100).optional(),
});
export const boardFormSchema = z.object({
  name,
  description: notes,
  color: z
    .string()
    .regex(/^#[a-f\d]{6}$/i)
    .optional(),
  teamId: id.nullable().optional(),
  isDefault: z.boolean().optional(),
});
export const roleFormSchema = z.object({
  name,
  description: notes,
  permissionIds: z.array(id).max(1000).optional(),
});
export const userEditSchema = z.object({
  name: name.optional(),
  email: emailSchema.optional(),
  phone: phoneSchema.nullable().optional(),
  password: z.string().min(8).max(1024).or(z.literal("")).optional(),
  client_id: id.nullable().optional(),
  is_active: z.boolean().optional(),
  roles: z.array(id).max(100).optional(),
});
export const maintenanceUsageSchema = z.object({
  hoursUsed: z.coerce.number().finite().positive().max(24),
  description: cleanTextSchema.min(1).max(20000),
  supportTicketId: id.nullable().optional(),
  taskCategory: cleanTextSchema.max(100).nullable().optional(),
  isBillable: z.boolean().optional(),
  billedAmount: money.nullable().optional(),
  invoiceId: id.nullable().optional(),
  workPerformedAt: date,
  metadata: z.record(z.unknown()).optional(),
});
export const messageFormSchema = z
  .object({
    conversationId: id,
    messageBody: cleanTextSchema.max(50000).nullable().optional(),
    type: z.enum(["text", "file"]).default("text"),
    attachments: z
      .array(
        z.object({
          path: z.string().min(1).max(2048),
          filename: name,
          mimeType: z.string().min(1).max(255),
          sizeBytes: z
            .number()
            .finite()
            .int()
            .positive()
            .max(100 * 1024 * 1024),
          disk: z.string().max(100).optional(),
        }),
      )
      .max(20)
      .default([]),
  })
  .refine((data) => Boolean(data.messageBody) || data.attachments.length > 0, {
    path: ["messageBody"],
    message: "Enter a message or attach a file.",
  });
export const contractFormSchema = z
  .object({
    title: name,
    description: notes,
    clientId: id,
    type: name,
    startDate: calendarDateSchema,
    endDate: date,
    value: money.nullable().optional(),
    currency: z.enum(["USD", "EUR", "GBP", "CAD", "AUD"]).default("USD"),
    billingCycle: z
      .enum(["monthly", "quarterly", "annually", "one-time"])
      .nullable()
      .optional(),
    terms: notes,
    autoRenew: z.boolean().optional(),
    noticeRequired: z.coerce
      .number()
      .finite()
      .int()
      .nonnegative()
      .max(3650)
      .nullable()
      .optional(),
    tags: z.array(cleanTextSchema.max(100)).max(100).optional(),
    customFields: z.record(z.unknown()).optional(),
  })
  .superRefine((data, context) =>
    checkDateOrder(data.startDate, data.endDate, "endDate", context),
  );

export const userCreateSchema = userEditSchema.extend({
  name,
  email: emailSchema,
  password: z.string().min(8).max(1024),
});
export const uploadFormFieldsSchema = z.object({
  clientId: id,
  parentDocumentId: id.nullable().optional(),
  requestId: id.nullable().optional(),
  name,
  description: notes,
  tags: z.array(cleanTextSchema.max(100)).max(100).nullable().optional(),
});
