import { NextRequest } from "next/server";
import { desc } from "drizzle-orm";
import { z } from "zod";

import { requireAdminUser } from "@/lib/auth/route-guards";
import {
  apiError,
  apiInternalError,
  apiSuccess,
  apiValidationError,
} from "@/lib/api/response";
import { db, isDatabaseConfigurationError } from "@/lib/db";
import { serviceTemplates } from "@/lib/db/schema/proposals";

const serviceTemplateLineItemSchema = z.object({
  description: z.string().trim().min(1, "Line item description is required"),
  quantity: z.coerce.number().positive("Quantity must be positive").default(1),
  unitPrice: z.coerce.number().nonnegative("Unit price must be non-negative").default(0),
  category: z.string().trim().optional(),
});

const createServiceTemplateSchema = z.object({
  name: z.string().trim().min(1, "Service name is required"),
  description: z.string().trim().optional(),
  category: z.string().trim().optional(),
  isActive: z.boolean().optional(),
  currency: z.enum(["USD", "EUR", "GBP", "CAD"]).default("USD"),
  lineItems: z.array(serviceTemplateLineItemSchema).min(1, "At least one line item is required"),
  terms: z.string().trim().optional(),
  metadata: z
    .object({
      features: z.array(z.string()).optional(),
      deliverables: z.array(z.string()).optional(),
      estimatedTimeline: z.string().optional(),
      notes: z.string().optional(),
    })
    .optional(),
});

/**
 * GET /api/admin/service-templates
 * List all service templates
 */
export async function GET(request: NextRequest) {
  try {
    const guard = await requireAdminUser(request);
    if ("error" in guard) {
      return guard.error;
    }

    const templates = await db
      .select()
      .from(serviceTemplates)
      .orderBy(desc(serviceTemplates.createdAt));

    return apiSuccess(request, templates, {
      extra: { count: templates.length },
    });
  } catch (error) {
    console.error("Error fetching service templates:", error);
    if (isDatabaseConfigurationError(error)) {
      return apiError(request, {
        status: 503,
        code: "SERVICE_UNAVAILABLE",
        message: "Failed to fetch templates",
      });
    }
    return apiInternalError(
      request,
      error instanceof Error ? error.message : "Failed to fetch templates",
    );
  }
}

/**
 * POST /api/admin/service-templates
 * Create a new service template (admin only)
 */
export async function POST(request: NextRequest) {
  try {
    const guard = await requireAdminUser(request);
    if ("error" in guard) {
      return guard.error;
    }

    const parseResult = createServiceTemplateSchema.safeParse(await request.json());
    if (!parseResult.success) {
      return apiValidationError(request, parseResult.error);
    }

    const body = parseResult.data;
    const totalAmount = body.lineItems.reduce(
      (sum, item) => sum + item.quantity * item.unitPrice,
      0,
    );

    const [template] = await db
      .insert(serviceTemplates)
      .values({
        name: body.name,
        description: body.description || null,
        category: body.category || null,
        isActive: body.isActive ?? true,
        currency: body.currency,
        lineItems: body.lineItems.map((item) => ({
          id: crypto.randomUUID(),
          description: item.description,
          quantity: item.quantity,
          unitPrice: item.unitPrice,
          amount: item.quantity * item.unitPrice,
          category: item.category || undefined,
        })),
        totalAmount: totalAmount.toString(),
        terms: body.terms || null,
        metadata: body.metadata || {},
        createdBy: guard.user.id,
      })
      .returning();

    return apiSuccess(request, template, {
      status: 201,
      extra: { message: "Service template created" },
    });
  } catch (error) {
    console.error("Error creating service template:", error);
    if (isDatabaseConfigurationError(error)) {
      return apiError(request, {
        status: 503,
        code: "SERVICE_UNAVAILABLE",
        message: "Failed to create template",
      });
    }
    return apiInternalError(
      request,
      error instanceof Error ? error.message : "Failed to create template",
    );
  }
}
