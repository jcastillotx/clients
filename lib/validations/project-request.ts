import { z } from "zod";

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

const isoDateField = z.string().min(1).optional().nullable();

export const createProjectRequestSchema = z.object({
  clientId: z.string().uuid().optional(),
  title: z.string().min(3, "Title must be at least 3 characters").max(200, "Title is too long"),
  executiveSummary: z
    .string()
    .min(20, "Executive summary must be at least 20 characters")
    .max(2000, "Executive summary is too long"),
  description: z.string().max(10000, "Description is too long").optional().nullable(),
  desiredOutcome: z.string().max(2000, "Desired outcome is too long").optional().nullable(),
  priority: z.enum(["low", "medium", "high"]).default("medium"),
  dueDate: isoDateField,
  requestedStartDate: isoDateField,
  requestedLaunchDate: isoDateField,
  budgetRange: z.string().max(255, "Budget range is too long").optional().nullable(),
  metadata: z.record(z.string(), z.unknown()).optional(),
});

export const createPublicProjectRequestSchema = createProjectRequestSchema.extend({
  companyName: z.string().min(2, "Company name must be at least 2 characters").max(255, "Company name is too long"),
  contactName: z.string().min(2, "Contact name must be at least 2 characters").max(255, "Contact name is too long"),
  contactEmail: z.string().email("A valid contact email is required"),
  contactPhone: z.string().max(50, "Phone number is too long").optional().nullable(),
  website: z.string().max(255, "Website is too long").optional().nullable(),
  industry: z.string().max(255, "Industry is too long").optional().nullable(),
  address: z.string().max(500, "Address is too long").optional().nullable(),
  city: z.string().max(255, "City is too long").optional().nullable(),
  state: z.string().max(255, "State is too long").optional().nullable(),
  zipCode: z.string().max(50, "ZIP/Postal code is too long").optional().nullable(),
  country: z.string().max(255, "Country is too long").optional().nullable(),
  businessOverview: z.string().max(4000, "Business overview is too long").optional().nullable(),
  turnstileToken: z.string().min(1).optional().nullable(),
});

export const updateProjectRequestSchema = z.object({
  title: z.string().min(3).max(200).optional(),
  description: z.string().max(10000).optional().nullable(),
  executiveSummary: z.string().min(20).max(2000).optional(),
  desiredOutcome: z.string().max(2000).optional().nullable(),
  priority: z.enum(["low", "medium", "high"]).optional(),
  status: z.enum(projectRequestLifecycleStatusEnum).optional(),
  assignedTo: z.string().uuid().nullable().optional(),
  dueDate: isoDateField,
  requestedStartDate: isoDateField,
  requestedLaunchDate: isoDateField,
  budgetRange: z.string().max(255).optional().nullable(),
  clientDecision: z.enum(["approved", "needs_changes", "declined"]).optional(),
  review: z
    .object({
      status: z.enum(projectReviewStatusEnum).optional(),
      estimateAmount: z.number().positive().optional().nullable(),
      estimateCurrency: z.enum(estimateCurrencyEnum).optional(),
      estimatedHours: z.number().positive().optional().nullable(),
      estimatedStartDate: isoDateField,
      estimatedEndDate: isoDateField,
      responseSummary: z.string().max(4000).optional().nullable(),
      reviewNotes: z.string().max(4000).optional().nullable(),
    })
    .optional(),
  metadata: z.record(z.string(), z.unknown()).optional(),
});

export const projectRequestFeedbackSchema = z.object({
  rating: z.number().int().min(1).max(5).optional(),
  message: z.string().min(1, "Feedback message is required").max(5000, "Feedback message is too long"),
});

export type CreateProjectRequestInput = z.infer<typeof createProjectRequestSchema>;
export type CreatePublicProjectRequestInput = z.infer<typeof createPublicProjectRequestSchema>;
export type UpdateProjectRequestInput = z.infer<typeof updateProjectRequestSchema>;
export type ProjectRequestFeedbackInput = z.infer<typeof projectRequestFeedbackSchema>;
