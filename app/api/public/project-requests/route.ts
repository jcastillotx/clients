import { NextRequest } from "next/server";
import { z } from "zod";
import { rateLimitExceededResponse } from "@/lib/api/rate-limit-response";
import {
  apiError,
  apiInternalError,
  apiSuccess,
  apiValidationError,
} from "@/lib/api/response";
import { dispatchNotification } from "@/lib/notifications/service";
import { getClientIp, limiters, rateLimitLimits } from "@/lib/rate-limit";
import { createAdminClientIfAvailable } from "@/lib/supabase/server";
import { assertTurnstileToken } from "@/lib/turnstile/verify";
import { createPublicProjectRequestSchema } from "@/lib/validations/project-request";

const normalizeDate = (value?: string | null) => {
  if (!value) {
    return null;
  }
  const parsed = new Date(value);
  if (Number.isNaN(parsed.getTime())) {
    return null;
  }
  return parsed.toISOString();
};

async function resolveIntakeOwnerId(
  adminClient: ReturnType<typeof createAdminClientIfAvailable>,
) {
  if (!adminClient) {
    return null;
  }

  if (process.env.PUBLIC_PROJECT_REQUEST_OWNER_ID) {
    const { data: configuredUser } = await adminClient
      .from("users")
      .select("id")
      .eq("id", process.env.PUBLIC_PROJECT_REQUEST_OWNER_ID)
      .maybeSingle();

    if (configuredUser?.id) {
      return configuredUser.id;
    }
  }

  const { data: superAdmin } = await adminClient
    .from("users")
    .select("id")
    .eq("is_super_admin", true)
    .limit(1)
    .maybeSingle();

  if (superAdmin?.id) {
    return superAdmin.id;
  }

  const { data: roleRows } = await adminClient
    .from("user_roles")
    .select("user_id, role:roles(name)");

  const intakeRoleUser = (roleRows || []).find(
    (row: {
      user_id?: string;
      role?: { name?: string } | Array<{ name?: string }>;
    }) => {
      const roleName = Array.isArray(row.role)
        ? String(row.role[0]?.name || "").toLowerCase()
        : String(row.role?.name || "").toLowerCase();
      return (
        roleName === "super_admin" ||
        roleName === "admin" ||
        roleName === "account_manager"
      );
    },
  );

  return intakeRoleUser?.user_id || null;
}

export async function POST(request: NextRequest) {
  try {
    const rateLimitResult = await limiters.publicIntake(getClientIp(request));
    if (!rateLimitResult.success) {
      return rateLimitExceededResponse(
        request,
        rateLimitResult,
        rateLimitLimits.publicIntake,
      );
    }

    const adminClient = createAdminClientIfAvailable();
    if (!adminClient) {
      return apiError(request, {
        status: 503,
        code: "SERVICE_UNAVAILABLE",
        message: "Public intake is not configured",
      });
    }

    const intakeOwnerId = await resolveIntakeOwnerId(adminClient);
    if (!intakeOwnerId) {
      return apiInternalError(
        request,
        "No intake owner is configured for project requests",
      );
    }

    const body = await request.json();
    const validated = createPublicProjectRequestSchema.parse(body);

    const captcha = await assertTurnstileToken(
      validated.turnstileToken,
      getClientIp(request),
    );
    if (!captcha.ok) {
      return apiError(request, {
        status: captcha.status,
        code: "BAD_REQUEST",
        message: captcha.error,
      });
    }

    const { turnstileToken, ...intakePayload } = validated;
    void turnstileToken;

    const { data: client, error: clientError } = await adminClient
      .from("clients")
      .insert({
        company_name: intakePayload.companyName,
        email: intakePayload.contactEmail,
        phone: intakePayload.contactPhone || null,
        website: intakePayload.website || null,
        domain: intakePayload.website || null,
        industry: intakePayload.industry || null,
        address: intakePayload.address || null,
        city: intakePayload.city || null,
        state: intakePayload.state || null,
        zip_code: intakePayload.zipCode || null,
        country: intakePayload.country || "United States",
        status: "pending",
      })
      .select("id")
      .single();

    if (clientError || !client) {
      return apiInternalError(
        request,
        clientError?.message || "Failed to create organization intake",
      );
    }

    const customFields = {
      ...(intakePayload.metadata || {}),
      type: "project",
      source: "public_project_request",
      executiveSummary: intakePayload.executiveSummary,
      desiredOutcome: intakePayload.desiredOutcome || null,
      budgetRange: intakePayload.budgetRange || null,
      requestedStartDate: normalizeDate(intakePayload.requestedStartDate),
      requestedLaunchDate: normalizeDate(intakePayload.requestedLaunchDate),
      publicIntake: {
        companyName: intakePayload.companyName,
        contactName: intakePayload.contactName,
        contactEmail: intakePayload.contactEmail,
        contactPhone: intakePayload.contactPhone || null,
        website: intakePayload.website || null,
        industry: intakePayload.industry || null,
        address: intakePayload.address || null,
        city: intakePayload.city || null,
        state: intakePayload.state || null,
        zipCode: intakePayload.zipCode || null,
        country: intakePayload.country || null,
        businessOverview: intakePayload.businessOverview || null,
      },
      review: {
        status: "awaiting_review",
        estimateAmount: null,
        estimateCurrency: "USD",
        estimatedHours: null,
        estimatedStartDate: null,
        estimatedEndDate: null,
        responseSummary: null,
        reviewNotes: null,
      },
    };

    const { data: projectRequest, error: requestError } = await adminClient
      .from("requests")
      .insert({
        client_id: client.id,
        title: intakePayload.title,
        description: intakePayload.description || null,
        priority: intakePayload.priority,
        status: "pending",
        due_date:
          normalizeDate(intakePayload.dueDate) ??
          normalizeDate(intakePayload.requestedLaunchDate),
        created_by: intakeOwnerId,
        assigned_to: intakeOwnerId,
        custom_fields: customFields,
      })
      .select("id, client_id")
      .single();

    if (requestError || !projectRequest) {
      return apiInternalError(
        request,
        requestError?.message || "Failed to create project request",
      );
    }

    await dispatchNotification({
      eventType: "project_request_created",
      clientId: projectRequest.client_id,
      subjectType: "request",
      subjectId: projectRequest.id,
      recipientUserIds: [intakeOwnerId],
      extraEmails: [intakePayload.contactEmail],
      data: {
        requestTitle: intakePayload.title,
        companyName: intakePayload.companyName,
      },
    });

    const result = {
      requestId: projectRequest.id,
      clientId: projectRequest.client_id,
    };

    return apiSuccess(request, result, { status: 201 });
  } catch (error) {
    if (error instanceof z.ZodError) {
      return apiValidationError(request, error);
    }

    console.error("Error creating public project request:", error);
    return apiInternalError(request, "Failed to create project request");
  }
}
