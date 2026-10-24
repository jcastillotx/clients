import { z } from "zod";
import {websiteSchema, cleanTextSchema } from "./fields";

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
  clientName: cleanTextSchema.nullable().optional(),
  websiteUrl: websiteSchema.nullable().optional(),
  stagingUrl: websiteSchema.nullable().optional(),
  affectedPageUrl: websiteSchema.nullable().optional(),
  requestedChange: cleanTextSchema.nullable().optional(),
  problemDescription: cleanTextSchema.nullable().optional(),
  deviceAffected: cleanTextSchema.nullable().optional(),
  browserAffected: cleanTextSchema.nullable().optional(),
  urgency: cleanTextSchema.nullable().optional(),
  businessImpact: cleanTextSchema.nullable().optional(),
  platformBuilder: websiteSupportPlatformSchema.nullable().optional(),
  affectedAreas: z.array(websiteSupportAffectedAreaSchema).optional(),
  areasNotToChange: cleanTextSchema.nullable().optional(),
});

/**
 * Validation schema for creating a support ticket
 */
export const createSupportTicketSchema = z.object({
  subject: cleanTextSchema.min(3, "Subject must be at least 3 characters").max(255),
  description: cleanTextSchema.min(10, "Description must be at least 10 characters").max(20000),
  category: z.enum(["technical", "billing", "general", "feature_request", "bug_report", "security", "performance"]),
  priority: z.enum(["low", "medium", "high", "urgent"]).default("medium"),
  assignedTo: cleanTextSchema.uuid().optional(),
  metadata: z
    .object({
      tags: z.array(cleanTextSchema).optional(),
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
            name: cleanTextSchema,
            url: cleanTextSchema.url().regex(/^https?:\/\/[^\s\/@]+(?:[\/?#][^\s]*)?$/i, "Use an HTTP or HTTPS URL without login credentials."),
            type: cleanTextSchema,
            size: z.number().finite().positive().max(50 * 1024 * 1024),
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
  subject: cleanTextSchema.min(3).max(255).optional(),
  description: cleanTextSchema.min(10).max(20000).optional(),
  category: z
    .enum(["technical", "billing", "general", "feature_request", "bug_report", "security", "performance"])
    .optional(),
  status: z.enum(["open", "in_progress", "waiting_on_client", "waiting_on_vendor", "resolved", "closed"]).optional(),
  priority: z.enum(["low", "medium", "high", "urgent"]).optional(),
  assignedTo: cleanTextSchema.uuid().nullable().optional(),
  estimatedHours: z.number().finite().min(0).optional(),
  actualHours: z.number().finite().min(0).optional(),
  metadata: z
    .object({
      tags: z.array(cleanTextSchema).optional(),
      customFields: z.record(z.any()).optional(),
      attachments: z
        .array(
          z.object({
            name: cleanTextSchema,
            url: cleanTextSchema.url().regex(/^https?:\/\/[^\s\/@]+(?:[\/?#][^\s]*)?$/i, "Use an HTTP or HTTPS URL without login credentials."),
            type: cleanTextSchema,
            size: z.number().finite().positive().max(50 * 1024 * 1024),
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
  comment: cleanTextSchema.min(1, "Comment cannot be empty").max(20000),
  isInternal: z.boolean().default(false),
  attachments: z
    .array(
      z.object({
        name: cleanTextSchema,
        url: cleanTextSchema.url().regex(/^https?:\/\/[^\s\/@]+(?:[\/?#][^\s]*)?$/i, "Use an HTTP or HTTPS URL without login credentials."),
        type: cleanTextSchema,
        size: z.number().finite().positive().max(50 * 1024 * 1024),
      }),
    )
    .optional(),
});

export type CreateSupportTicketInput = z.infer<typeof createSupportTicketSchema>;
export type UpdateSupportTicketInput = z.infer<typeof updateSupportTicketSchema>;
export type CreateTicketCommentInput = z.infer<typeof createTicketCommentSchema>;
