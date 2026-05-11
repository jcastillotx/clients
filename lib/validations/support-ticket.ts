import { z } from "zod";

const websiteSupportPlatformSchema = z.enum([
  "WordPress",
  "Elementor",
  "Divi",
  "WooCommerce",
  "Custom Theme",
  "Custom Plugin",
  "Form Plugin",
  "Unknown",
]);

const websiteSupportAffectedAreaSchema = z.enum([
  "forms",
  "checkout",
  "login",
  "users",
  "payments",
  "seo",
  "security",
  "database",
  "dns",
  "smtp",
  "woocommerce",
]);

const websiteSupportIntakeSchema = z.object({
  isWebsiteSupport: z.boolean(),
  clientName: z.string().nullable().optional(),
  websiteUrl: z.string().nullable().optional(),
  stagingUrl: z.string().nullable().optional(),
  affectedPageUrl: z.string().nullable().optional(),
  requestedChange: z.string().nullable().optional(),
  problemDescription: z.string().nullable().optional(),
  deviceAffected: z.string().nullable().optional(),
  browserAffected: z.string().nullable().optional(),
  urgency: z.string().nullable().optional(),
  businessImpact: z.string().nullable().optional(),
  platformBuilder: websiteSupportPlatformSchema.nullable().optional(),
  affectedAreas: z.array(websiteSupportAffectedAreaSchema).optional(),
  areasNotToChange: z.string().nullable().optional(),
});

/**
 * Validation schema for creating a support ticket
 */
export const createSupportTicketSchema = z.object({
  subject: z.string().min(3, "Subject must be at least 3 characters").max(255),
  description: z.string().min(10, "Description must be at least 10 characters"),
  category: z.enum(["technical", "billing", "general", "feature_request", "bug_report", "security", "performance"]),
  priority: z.enum(["low", "medium", "high", "urgent"]).default("medium"),
  assignedTo: z.string().uuid().optional(),
  metadata: z
    .object({
      tags: z.array(z.string()).optional(),
      customFields: z
        .record(z.any())
        .and(
          z.object({
            websiteSupport: websiteSupportIntakeSchema.optional(),
          }),
        )
        .optional(),
      attachments: z
        .array(
          z.object({
            name: z.string(),
            url: z.string().url(),
            type: z.string(),
            size: z.number(),
          }),
        )
        .optional(),
    })
    .optional(),
});

/**
 * Validation schema for updating a support ticket
 */
export const updateSupportTicketSchema = z.object({
  subject: z.string().min(3).max(255).optional(),
  description: z.string().min(10).optional(),
  category: z
    .enum(["technical", "billing", "general", "feature_request", "bug_report", "security", "performance"])
    .optional(),
  status: z.enum(["open", "in_progress", "waiting_on_client", "waiting_on_vendor", "resolved", "closed"]).optional(),
  priority: z.enum(["low", "medium", "high", "urgent"]).optional(),
  assignedTo: z.string().uuid().nullable().optional(),
  estimatedHours: z.number().min(0).optional(),
  actualHours: z.number().min(0).optional(),
  metadata: z
    .object({
      tags: z.array(z.string()).optional(),
      customFields: z.record(z.any()).optional(),
      attachments: z
        .array(
          z.object({
            name: z.string(),
            url: z.string().url(),
            type: z.string(),
            size: z.number(),
          }),
        )
        .optional(),
    })
    .optional(),
});

/**
 * Validation schema for creating a ticket comment
 */
export const createTicketCommentSchema = z.object({
  comment: z.string().min(1, "Comment cannot be empty"),
  isInternal: z.boolean().default(false),
  attachments: z
    .array(
      z.object({
        name: z.string(),
        url: z.string().url(),
        type: z.string(),
        size: z.number(),
      }),
    )
    .optional(),
});

export type CreateSupportTicketInput = z.infer<typeof createSupportTicketSchema>;
export type UpdateSupportTicketInput = z.infer<typeof updateSupportTicketSchema>;
export type CreateTicketCommentInput = z.infer<typeof createTicketCommentSchema>;
