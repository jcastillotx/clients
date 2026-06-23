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

    const body = await request.json();

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

    return apiSuccess(request, newClient, {
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
