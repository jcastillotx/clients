import { z } from "zod";
import { calendarDateSchema, checkDateOrder, cleanTextSchema } from "./fields";

/**
 * Project Creation Schema
 */
export const createProjectSchema = z.object({
  clientId: cleanTextSchema.guid("Invalid client ID"),
  name: cleanTextSchema.min(1, "Project name is required").max(255),
  description: cleanTextSchema.max(100000).optional().nullable(),
  status: z.enum(["planning", "active", "on_hold", "completed", "cancelled"]).default("planning"),
  startDate: calendarDateSchema.or(z.literal("")).optional().nullable(),
  endDate: calendarDateSchema.or(z.literal("")).optional().nullable(),
  estimatedHours: z.number().finite().positive().optional().nullable(),
  budgetAmount: z.number().finite().nonnegative().optional().nullable(),
  currency: z.enum(["USD", "EUR", "GBP", "CAD", "AUD"]).default("USD"),
  projectManagerId: cleanTextSchema.guid().or(z.literal("")).optional().nullable().transform(value => value || null),
  teamMembers: z.array(z.object({
    userId: cleanTextSchema.guid(),
    name: cleanTextSchema,
    role: cleanTextSchema,
    hourlyRate: z.number().finite().positive().optional(),
  })).optional(),
  budgets: z.array(z.object({category: z.enum(["development", "design", "marketing", "infrastructure", "other"]), allocatedAmount: z.coerce.number().finite().nonnegative(), currency: z.enum(["USD", "EUR", "GBP", "CAD", "AUD"]).optional(), notes: cleanTextSchema.max(10000).optional()})).max(100).optional(),
  milestones: z.array(z.object({title: cleanTextSchema.min(1).max(255), description: cleanTextSchema.max(10000).optional(), dueDate: calendarDateSchema.or(z.literal("")).optional()})).max(100).optional(),
  metadata: z.object({
    tags: z.array(cleanTextSchema).optional(),
    priority: z.enum(["low", "medium", "high", "critical"]).optional(),
    repository: cleanTextSchema.url().regex(/^https?:\/\/[^\s\/@]+(?:[\/?#][^\s]*)?$/i, "Use an HTTP or HTTPS URL without login credentials.").optional().or(z.literal("")),
    slackChannel: cleanTextSchema.max(100000).optional(),
    notes: cleanTextSchema.max(100000).optional(),
  }).optional(),
}).superRefine((data, context) => checkDateOrder(data.startDate, data.endDate, "endDate", context));

export type CreateProjectInput = z.infer<typeof createProjectSchema>;

/**
 * Project Update Schema
 */
export const updateProjectSchema = z.object({
  name: cleanTextSchema.min(1).max(255).optional(),
  description: cleanTextSchema.max(100000).optional().nullable(),
  status: z.enum(["planning", "active", "on_hold", "completed", "cancelled"]).optional(),
  startDate: calendarDateSchema.or(z.literal("")).optional().nullable(),
  endDate: calendarDateSchema.or(z.literal("")).optional().nullable(),
  estimatedHours: z.number().finite().positive().optional().nullable(),
  actualHours: z.number().finite().nonnegative().optional().nullable(),
  budgetAmount: z.number().finite().nonnegative().optional().nullable(),
  spentAmount: z.number().finite().nonnegative().optional().nullable(),
  currency: z.enum(["USD", "EUR", "GBP", "CAD", "AUD"]).optional(),
  projectManagerId: cleanTextSchema.guid().or(z.literal("")).optional().nullable().transform(value => value || null),
  progressPercent: z.number().finite().min(0).max(100).optional(),
  teamMembers: z.array(z.object({
    userId: cleanTextSchema.guid(),
    name: cleanTextSchema,
    role: cleanTextSchema,
    hourlyRate: z.number().finite().positive().optional(),
  })).optional(),
  metadata: z.object({}).passthrough().optional(),
}).superRefine((data, context) => checkDateOrder(data.startDate, data.endDate, "endDate", context));

export type UpdateProjectInput = z.infer<typeof updateProjectSchema>;

/**
 * Project Budget Schema
 */
export const createProjectBudgetSchema = z.object({
  category: z.enum(["development", "design", "marketing", "infrastructure", "other"]),
  allocatedAmount: z.number().finite().positive("Allocated amount must be positive"),
  currency: z.enum(["USD", "EUR", "GBP", "CAD", "AUD"]).default("USD"),
  notes: cleanTextSchema.max(100000).optional().nullable(),
});

export type CreateProjectBudgetInput = z.infer<typeof createProjectBudgetSchema>;

/**
 * Project Milestone Schema
 */
export const createProjectMilestoneSchema = z.object({
  title: cleanTextSchema.min(1, "Title is required").max(255),
  description: cleanTextSchema.max(100000).optional().nullable(),
  dueDate: calendarDateSchema.or(z.literal("")).optional().nullable(),
  sortOrder: z.number().finite().int().nonnegative().default(0),
  metadata: z.object({
    dependencies: z.array(cleanTextSchema).optional(),
    assignedTo: z.array(cleanTextSchema.guid()).optional(),
    notes: cleanTextSchema.max(100000).optional(),
  }).optional(),
});

export type CreateProjectMilestoneInput = z.infer<typeof createProjectMilestoneSchema>;
