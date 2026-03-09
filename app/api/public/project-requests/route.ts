import { NextRequest, NextResponse } from "next/server";
import { z } from "zod";
import { createAdminClientIfAvailable } from "@/lib/supabase/server";
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

async function resolveIntakeOwnerId(adminClient: ReturnType<typeof createAdminClientIfAvailable>) {
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

  const intakeRoleUser = (roleRows || []).find((row: any) => {
    const roleName = String(row?.role?.name || row?.role?.[0]?.name || "").toLowerCase();
    return roleName === "super_admin" || roleName === "admin" || roleName === "account_manager";
  });

  return intakeRoleUser?.user_id || null;
}

export async function POST(request: NextRequest) {
  try {
    const adminClient = createAdminClientIfAvailable();
    if (!adminClient) {
      return NextResponse.json({ error: "Public intake is not configured" }, { status: 503 });
    }

    const intakeOwnerId = await resolveIntakeOwnerId(adminClient);
    if (!intakeOwnerId) {
      return NextResponse.json({ error: "No intake owner is configured for project requests" }, { status: 500 });
    }

    const body = await request.json();
    const validated = createPublicProjectRequestSchema.parse(body);

    const { data: client, error: clientError } = await adminClient
      .from("clients")
      .insert({
        company_name: validated.companyName,
        email: validated.contactEmail,
        phone: validated.contactPhone || null,
        website: validated.website || null,
        domain: validated.website || null,
        industry: validated.industry || null,
        address: validated.address || null,
        city: validated.city || null,
        state: validated.state || null,
        zip_code: validated.zipCode || null,
        country: validated.country || "United States",
        status: "pending",
      })
      .select("id")
      .single();

    if (clientError || !client) {
      return NextResponse.json({ error: clientError?.message || "Failed to create organization intake" }, { status: 500 });
    }

    const customFields = {
      ...(validated.metadata || {}),
      type: "project",
      source: "public_project_request",
      executiveSummary: validated.executiveSummary,
      desiredOutcome: validated.desiredOutcome || null,
      budgetRange: validated.budgetRange || null,
      requestedStartDate: normalizeDate(validated.requestedStartDate),
      requestedLaunchDate: normalizeDate(validated.requestedLaunchDate),
      publicIntake: {
        companyName: validated.companyName,
        contactName: validated.contactName,
        contactEmail: validated.contactEmail,
        contactPhone: validated.contactPhone || null,
        website: validated.website || null,
        industry: validated.industry || null,
        address: validated.address || null,
        city: validated.city || null,
        state: validated.state || null,
        zipCode: validated.zipCode || null,
        country: validated.country || null,
        businessOverview: validated.businessOverview || null,
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
        title: validated.title,
        description: validated.description || null,
        priority: validated.priority,
        status: "pending",
        due_date: normalizeDate(validated.dueDate) ?? normalizeDate(validated.requestedLaunchDate),
        created_by: intakeOwnerId,
        assigned_to: intakeOwnerId,
        custom_fields: customFields,
      })
      .select("id, client_id")
      .single();

    if (requestError || !projectRequest) {
      return NextResponse.json({ error: requestError?.message || "Failed to create project request" }, { status: 500 });
    }

    return NextResponse.json(
      {
        data: {
          requestId: projectRequest.id,
          clientId: projectRequest.client_id,
        },
      },
      { status: 201 },
    );
  } catch (error) {
    if (error instanceof z.ZodError) {
      return NextResponse.json({ error: "Validation error", details: error.errors }, { status: 400 });
    }

    console.error("Error creating public project request:", error);
    return NextResponse.json({ error: "Failed to create project request" }, { status: 500 });
  }
}
