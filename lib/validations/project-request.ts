import { z } from "zod";
import { phoneSchema, websiteSchema, emailSchema, calendarDateSchema, checkDateOrder, checkUpcomingDates, countrySchema, checkPostalCode, cleanTextSchema } from "./fields";

export const projectRequestLifecycleStatusEnum = [
  "pending",
  "in_progress",
  "awaiting_approval",
  "approved",
  "on_hold",
  "rejected",
  "completed",
  "cancelled",
] as const;

export const projectReviewStatusEnum = [
  "awaiting_review",
  "in_review",
  "estimated",
  "approved",
  "needs_changes",
  "declined",
] as const;

export const estimateCurrencyEnum = ["USD", "EUR", "GBP", "CAD", "AUD"] as const;

const isoDateField = calendarDateSchema.optional().nullable();

const projectRequestFields = z.object({
  clientId: cleanTextSchema.guid().optional(),
  title: cleanTextSchema.min(3, "Title must be at least 3 characters").max(200, "Title is too long"),
  executiveSummary: z
    .string()
    .trim()
    .regex(/^[^\u0000-\u0008\u000b\u000c\u000e-\u001f\u007f]*$/, "Remove invalid control characters.")
    .min(20, "Executive summary must be at least 20 characters")
    .max(2000, "Executive summary is too long"),
  description: cleanTextSchema.max(10000, "Description is too long").optional().nullable(),
  desiredOutcome: cleanTextSchema.max(2000, "Desired outcome is too long").optional().nullable(),
  priority: z.enum(["low", "medium", "high"]).default("medium"),
  dueDate: isoDateField,
  requestedStartDate: isoDateField,
  requestedLaunchDate: isoDateField,
  budgetRange: cleanTextSchema.max(255, "Budget range is too long").optional().nullable(),
  metadata: z.record(z.string(), z.unknown()).optional(),
});

export const createProjectRequestSchema = projectRequestFields.superRefine((data, context) => checkDateOrder(data.requestedStartDate, data.requestedLaunchDate || data.dueDate, "requestedLaunchDate", context));

export const createPublicProjectRequestSchema = projectRequestFields.extend({
  companyName: cleanTextSchema.min(2, "Company name must be at least 2 characters").max(255, "Company name is too long"),
  contactName: cleanTextSchema.min(2, "Contact name must be at least 2 characters").max(255, "Contact name is too long"),
  contactEmail: emailSchema,
  contactPhone: phoneSchema.optional().nullable(),
  website: websiteSchema.optional().nullable(),
  industry: cleanTextSchema.max(255, "Industry is too long").optional().nullable(),
  address: cleanTextSchema.max(500, "Address is too long").optional().nullable(),
  city: cleanTextSchema.max(255, "City is too long").optional().nullable(),
  state: cleanTextSchema.max(255, "State is too long").optional().nullable(),
  zipCode: cleanTextSchema.max(50, "ZIP/Postal code is too long").optional().nullable(),
  country: countrySchema.optional().nullable(),
  businessOverview: cleanTextSchema.max(4000, "Business overview is too long").optional().nullable(),
  turnstileToken: cleanTextSchema.min(1).max(2048).optional().nullable(),
  contactFax: cleanTextSchema.max(500).optional(),
}).superRefine((data, context) => { checkUpcomingDates(data, context); checkPostalCode(data, context); });

export const updateProjectRequestSchema = z.object({
  title: cleanTextSchema.min(3).max(200).optional(),
  description: cleanTextSchema.max(10000).optional().nullable(),
  executiveSummary: cleanTextSchema.min(20).max(2000).optional(),
  desiredOutcome: cleanTextSchema.max(2000).optional().nullable(),
  priority: z.enum(["low", "medium", "high"]).optional(),
  status: z.enum(projectRequestLifecycleStatusEnum).optional(),
  assignedTo: cleanTextSchema.guid().nullable().optional(),
  dueDate: isoDateField,
  requestedStartDate: isoDateField,
  requestedLaunchDate: isoDateField,
  budgetRange: cleanTextSchema.max(255).optional().nullable(),
  clientDecision: z.enum(["approved", "needs_changes", "declined"]).optional(),
  review: z
    .object({
      status: z.enum(projectReviewStatusEnum).optional(),
      estimateAmount: z.number().finite().positive().optional().nullable(),
      estimateCurrency: z.enum(estimateCurrencyEnum).optional(),
      estimatedHours: z.number().finite().positive().optional().nullable(),
      estimatedStartDate: isoDateField,
      estimatedEndDate: isoDateField,
      responseSummary: cleanTextSchema.max(4000).optional().nullable(),
      reviewNotes: cleanTextSchema.max(4000).optional().nullable(),
    })
    .optional(),
  metadata: z.record(z.string(), z.unknown()).optional(),
}).superRefine((data, context) => {
  checkDateOrder(data.requestedStartDate, data.requestedLaunchDate || data.dueDate, "requestedLaunchDate", context);
  if (data.review) checkDateOrder(data.review.estimatedStartDate, data.review.estimatedEndDate, "review", context);
});

export const projectRequestFeedbackSchema = z.object({
  rating: z.number().finite().int().min(1).max(5).optional(),
  message: cleanTextSchema.min(1, "Feedback message is required").max(5000, "Feedback message is too long"),
});

export type CreateProjectRequestInput = z.infer<typeof createProjectRequestSchema>;
export type CreatePublicProjectRequestInput = z.infer<typeof createPublicProjectRequestSchema>;
export type UpdateProjectRequestInput = z.infer<typeof updateProjectRequestSchema>;
export type ProjectRequestFeedbackInput = z.infer<typeof projectRequestFeedbackSchema>;
