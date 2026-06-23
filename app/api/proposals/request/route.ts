import { NextRequest } from "next/server";
import { db, isDatabaseConfigurationError } from "@/lib/db";
import { proposals, serviceTemplates } from "@/lib/db/schema/proposals";
import { eq } from "drizzle-orm";
import { createClient } from "@/lib/supabase/server";
import {
  apiError,
  apiNotFound,
  apiSuccess,
  apiUnauthorized,
} from "@/lib/api/response";

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

    const body = await request.json();

    if (!body.clientId) {
      return apiError(request, {
        status: 400,
        code: "BAD_REQUEST",
        message: "Client ID is required",
      });
    }

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

      const [proposal] = await db
        .insert(proposals)
        .values({
          clientId: body.clientId,
          serviceTemplateId: template.id,
          title: template.name,
          description: template.description,
          status: "sent",
          totalAmount: template.totalAmount,
          currency: template.currency,
          terms: template.terms,
          lineItems: template.lineItems,
          sentAt: new Date(),
          createdBy: user.id,
          metadata: {
            notes: body.notes || null,
          },
        })
        .returning();

      return apiSuccess(request, proposal, {
        status: 201,
        extra: {
          proposal,
          message:
            "Service requested successfully. A proposal has been created for your review.",
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
        title: body.title,
        description: body.description,
        status: "draft",
        totalAmount: "0",
        currency: body.currency || "USD",
        lineItems: [],
        createdBy: user.id,
        metadata: {
          isCustomRequest: true,
          customRequestDescription: body.description,
          notes: body.notes || null,
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
      message: error instanceof Error ? error.message : "Failed to submit request",
    });
  }
}
