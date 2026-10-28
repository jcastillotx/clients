import { z } from "zod";
import { canAccessClient, resolveRouteAccess } from "@/lib/auth/route-access";
import { NextRequest } from "next/server";
import { db, isDatabaseConfigurationError } from "@/lib/db";
import { proposals, serviceTemplates } from "@/lib/db/schema/proposals";
import { eq } from "drizzle-orm";
import { createClient } from "@/lib/supabase/server";
import {
  apiError,
  apiNotFound,
  apiForbidden,
  apiValidationError,
  apiSuccess,
  apiUnauthorized,
} from "@/lib/api/response";

const requestSchema = z.object({
  clientId: z.string().uuid(),
  serviceTemplateId: z.string().uuid().optional(),
  title: z.string().trim().min(1).max(200).optional(),
  description: z.string().trim().min(1).max(10000).optional(),
  notes: z.string().trim().max(5000).optional(),
  currency: z.enum(["USD", "EUR", "GBP", "CAD"]).default("USD"),
}).refine((value) => value.serviceTemplateId || (value.title && value.description), {
  message: "Choose a service or describe your request.",
});

/**
 * POST /api/proposals/request
 * Client requests a service - creates a proposal from a template or custom request
 */
export async function POST(request: NextRequest) {
  try {
    const supabase = await createClient();
    const {
      data: { user },
      error: authError,
    } = await supabase.auth.getUser();

    if (authError || !user) {
      return apiUnauthorized(request, "Authentication required");
    }

    const parsed = requestSchema.safeParse(await request.json());
    if (!parsed.success) return apiValidationError(request, parsed.error);
    const body = parsed.data;
    const access = await resolveRouteAccess(supabase, user);
    if (!canAccessClient(access, body.clientId)) return apiForbidden(request);

    if (body.serviceTemplateId) {
      const [template] = await db
        .select()
        .from(serviceTemplates)
        .where(eq(serviceTemplates.id, body.serviceTemplateId))
        .limit(1);

      if (!template) {
        return apiNotFound(request, "Service template not found");
      }

      if (!template.isActive) {
        return apiError(request, {
          status: 400,
          code: "BAD_REQUEST",
          message: "This service is no longer available",
        });
      }

      const quoteRequired = template.metadata?.pricingMode === "quote" || Number(template.totalAmount) <= 0;
      const [proposal] = await db
        .insert(proposals)
        .values({
          clientId: body.clientId,
          serviceTemplateId: template.id,
          title: template.name,
          description: template.description,
          status: quoteRequired ? "draft" : "sent",
          totalAmount: template.totalAmount,
          currency: template.currency,
          terms: template.terms,
          lineItems: template.lineItems,
          sentAt: quoteRequired ? null : new Date(),
          createdBy: user.id,
          metadata: {
            isCustomRequest: quoteRequired,
            notes: body.notes || undefined,
          },
        })
        .returning();

      return apiSuccess(request, proposal, {
        status: 201,
        extra: {
          proposal,
          message: quoteRequired
            ? "Request received. We will scope the work and prepare a written quote."
            : "Service requested successfully. A proposal has been created for your review.",
        },
      });
    }

    if (!body.title?.trim()) {
      return apiError(request, {
        status: 400,
        code: "BAD_REQUEST",
        message: "Service title is required",
      });
    }
    if (!body.description?.trim()) {
      return apiError(request, {
        status: 400,
        code: "BAD_REQUEST",
        message: "Please describe what you need",
      });
    }

    const [proposal] = await db
      .insert(proposals)
      .values({
        clientId: body.clientId,
        title: body.title!,
        description: body.description,
        status: "draft",
        totalAmount: "0",
        currency: body.currency || "USD",
        lineItems: [],
        createdBy: user.id,
        metadata: {
          isCustomRequest: true,
          customRequestDescription: body.description,
          notes: body.notes || undefined,
        },
      })
      .returning();

    return apiSuccess(request, proposal, {
      status: 201,
      extra: {
        proposal,
        message:
          "Custom service request submitted. We will review and create a proposal for you.",
      },
    });
  } catch (error) {
    console.error("Error creating service request:", error);
    const status = isDatabaseConfigurationError(error) ? 503 : 500;
    return apiError(request, {
      status,
      code: status === 503 ? "SERVICE_UNAVAILABLE" : "INTERNAL_ERROR",
      message: "Unable to submit your request. Please try again.",
    });
  }
}
