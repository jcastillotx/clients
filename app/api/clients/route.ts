import { z } from "zod";
import { sendClientWelcomeEmail } from "@/lib/email/send-welcome-email";
import {
  buildPaginationMeta,
  parsePaginationSearchParams,
} from "@/lib/api/pagination";
import {
  apiError,
  apiForbidden,
  apiInternalError,
  apiSuccess,
  apiUnauthorized,
} from "@/lib/api/response";
import { createClient } from "@/lib/supabase/server";
import {
  hasAnyRole,
  hasPermission,
  Permissions,
  Roles,
} from "@/lib/rbac/permissions";
import { inngest } from "@/lib/inngest/client";
import { db } from "@/lib/db";
import { clients, type NewClient } from "@/lib/db/schema";

export async function GET(request: Request) {
  try {
    const supabase = await createClient();
    const {
      data: { user },
      error: authError,
    } = await supabase.auth.getUser();

    if (authError || !user) {
      return apiUnauthorized(request, "Authentication required");
    }

    const { searchParams } = new URL(request.url);
    const includeInactive = searchParams.get("includeInactive") === "true";
    const pagination = parsePaginationSearchParams(searchParams);

    let query = supabase
      .from("clients")
      .select("id, company_name, status", { count: "exact" })
      .is("deleted_at", null)
      .order("company_name")
      .range(
        pagination.offset,
        pagination.offset + pagination.limit - 1,
      );
    if (!includeInactive) {
      query = query.eq("status", "active");
    }

    const { data, error, count } = await query;
    if (error) {
      throw error;
    }

    const rows = data ?? [];

    return apiSuccess(request, rows, {
      pagination: buildPaginationMeta(pagination, count, rows.length),
    });
  } catch (error) {
    console.error("Error listing clients:", error);
    return apiInternalError(
      request,
      error instanceof Error ? error.message : "Failed to fetch clients",
    );
  }
}

export async function POST(request: Request) {
  try {
    const supabase = await createClient();
    const {
      data: { user },
    } = await supabase.auth.getUser();

    if (!user) {
      return apiUnauthorized(request, "Authentication required");
    }

    const metadataRole = String(
      user.user_metadata?.role ?? user.user_metadata?.app_role ?? "",
    ).toLowerCase();
    const hasManagementMetadataRole =
      user.user_metadata?.is_super_admin === true ||
      metadataRole === Roles.SUPER_ADMIN ||
      metadataRole === Roles.ADMIN ||
      metadataRole === Roles.ACCOUNT_MANAGER;

    const accessOptions = { supabase, userId: user.id };
    const [canCreateClients, hasManagementRoleDb] = await Promise.all([
      hasPermission(Permissions.CLIENTS_CREATE, accessOptions),
      hasAnyRole(
        [Roles.SUPER_ADMIN, Roles.ADMIN, Roles.ACCOUNT_MANAGER],
        accessOptions,
      ),
    ]);
    const hasManagementRole = hasManagementRoleDb || hasManagementMetadataRole;

    if (!(canCreateClients || hasManagementRole)) {
      return apiForbidden(request);
    }

    const parsed = z.object({
      company_name: z.string().trim().min(1),
      email: z.string().trim().email(),
      status: z.enum(["active", "inactive", "pending", "suspended"]).optional(),
      primary_contact_id: z.string().uuid().nullable().optional(),
      domain: z.string().nullable().optional(),
      industry: z.string().nullable().optional(),
      logo_url: z.string().nullable().optional(),
      phone: z.string().nullable().optional(),
      address: z.string().nullable().optional(),
      city: z.string().nullable().optional(),
      state: z.string().nullable().optional(),
      zip_code: z.string().nullable().optional(),
      country: z.string().nullable().optional(),
      website: z.string().nullable().optional(),
    }).safeParse(await request.json());
    if (!parsed.success) {
      return apiError(request, {
        status: 400,
        code: "BAD_REQUEST",
        message: "Provide a company name, valid email address, and valid client fields",
      });
    }
    const body = parsed.data;

    const insertData: NewClient = {
      companyName: body.company_name,
      email: body.email,
      domain: body.domain || null,
      industry: body.industry || null,
      logoUrl: body.logo_url || null,
      status: body.status || "active",
      primaryContactId: body.primary_contact_id || null,
      phone: body.phone || null,
      address: body.address || null,
      city: body.city || null,
      state: body.state || null,
      zipCode: body.zip_code || null,
      country: body.country || "US",
      website: body.website || null,
    };

    if (!insertData.companyName) {
      return apiError(request, {
        status: 400,
        code: "BAD_REQUEST",
        message: "Company name is required",
      });
    }

    if (!insertData.email) {
      return apiError(request, {
        status: 400,
        code: "BAD_REQUEST",
        message: "Email is required",
      });
    }

    const [newClient] = await db.insert(clients).values(insertData).returning();

    if (!newClient) {
      throw new Error("Failed to create client record");
    }

    try {
      await inngest.send({
        name: "client.created",
        data: {
          clientId: newClient.id,
          companyName: newClient.companyName,
        },
      });
    } catch (eventError) {
      console.error("Error sending client.created event:", eventError);
    }

    const welcomeEmail = await sendClientWelcomeEmail(newClient);
    return apiSuccess(request, { ...newClient, welcome_email_sent: welcomeEmail.success }, {
      status: 201,
      extra: { client: newClient },
    });
  } catch (error) {
    console.error("Error creating client:", error);
    return apiInternalError(
      request,
      error instanceof Error ? error.message : "Failed to create client",
    );
  }
}
