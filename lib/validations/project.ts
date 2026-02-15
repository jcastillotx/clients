import { z } from "zod";

/**
 * Project Creation Schema
 */
export const createProjectSchema = z.object({
  clientId: z.string().uuid("Invalid client ID"),
  name: z.string().min(1, "Project name is required").max(255),
  description: z.string().optional().nullable(),
  status: z.enum(["planning", "active", "on_hold", "completed", "cancelled"]).default("planning"),
  startDate: z.string().datetime().optional().nullable(),
  endDate: z.string().datetime().optional().nullable(),
  estimatedHours: z.number().positive().optional().nullable(),
  budgetAmount: z.number().positive().optional().nullable(),
  currency: z.enum(["USD", "EUR", "GBP", "CAD", "AUD"]).default("USD"),
  projectManagerId: z.string().uuid().optional().nullable(),
  teamMembers: z.array(z.object({
    userId: z.string().uuid(),
    name: z.string(),
    role: z.string(),
    hourlyRate: z.number().positive().optional(),
  })).optional(),
  metadata: z.object({
    tags: z.array(z.string()).optional(),
    priority: z.enum(["low", "medium", "high", "critical"]).optional(),
    repository: z.string().url().optional().or(z.literal("")),
    slackChannel: z.string().optional(),
    notes: z.string().optional(),
  }).optional(),
});

export type CreateProjectInput = z.infer<typeof createProjectSchema>;

/**
 * Project Update Schema
 */
export const updateProjectSchema = z.object({
  name: z.string().min(1).max(255).optional(),
  description: z.string().optional().nullable(),
  status: z.enum(["planning", "active", "on_hold", "completed", "cancelled"]).optional(),
  startDate: z.string().datetime().optional().nullable(),
  endDate: z.string().datetime().optional().nullable(),
  estimatedHours: z.number().positive().optional().nullable(),
  actualHours: z.number().nonnegative().optional().nullable(),
  budgetAmount: z.number().positive().optional().nullable(),
  spentAmount: z.number().nonnegative().optional().nullable(),
  currency: z.enum(["USD", "EUR", "GBP", "CAD", "AUD"]).optional(),
  projectManagerId: z.string().uuid().optional().nullable(),
  progressPercent: z.number().min(0).max(100).optional(),
  teamMembers: z.array(z.object({
    userId: z.string().uuid(),
    name: z.string(),
    role: z.string(),
    hourlyRate: z.number().positive().optional(),
  })).optional(),
  metadata: z.object({}).passthrough().optional(),
});

export type UpdateProjectInput = z.infer<typeof updateProjectSchema>;

/**
 * Project Budget Schema
 */
export const createProjectBudgetSchema = z.object({
  category: z.enum(["development", "design", "marketing", "infrastructure", "other"]),
  allocatedAmount: z.number().positive("Allocated amount must be positive"),
  currency: z.enum(["USD", "EUR", "GBP", "CAD", "AUD"]).default("USD"),
  notes: z.string().optional().nullable(),
});

export type CreateProjectBudgetInput = z.infer<typeof createProjectBudgetSchema>;

/**
 * Project Milestone Schema
 */
export const createProjectMilestoneSchema = z.object({
  title: z.string().min(1, "Title is required").max(255),
  description: z.string().optional().nullable(),
  dueDate: z.string().datetime().optional().nullable(),
  sortOrder: z.number().int().nonnegative().default(0),
  metadata: z.object({
    dependencies: z.array(z.string()).optional(),
    assignedTo: z.array(z.string().uuid()).optional(),
    notes: z.string().optional(),
  }).optional(),
});

export type CreateProjectMilestoneInput = z.infer<typeof createProjectMilestoneSchema>;
