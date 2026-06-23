import { createClient } from "@/lib/supabase/server";
import {
  apiForbidden,
  apiInternalError,
  apiNotFound,
  apiSuccess,
  apiUnauthorized,
} from "@/lib/api/response";
import { hasAnyRole, hasPermission, Permissions, Roles } from "@/lib/rbac/permissions";
import { db } from "@/lib/db";
import { clients } from "@/lib/db/schema";
import { eq } from "drizzle-orm";

export async function PATCH(request: Request, { params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;

  try {
    const supabase = await createClient();
    const {
      data: { user },
    } = await supabase.auth.getUser();

    if (!user) {
      return apiUnauthorized(request, "Authentication required");
    }

    const metadataRole = String(user.user_metadata?.role ?? user.user_metadata?.app_role ?? "").toLowerCase();
    const hasManagementMetadataRole =
      user.user_metadata?.is_super_admin === true ||
      metadataRole === Roles.SUPER_ADMIN ||
      metadataRole === Roles.ADMIN ||
      metadataRole === Roles.ACCOUNT_MANAGER;

    const accessOptions = { supabase, userId: user.id };
    const [canUpdateClients, hasManagementRoleDb] = await Promise.all([
      hasPermission(Permissions.CLIENTS_UPDATE, accessOptions),
      hasAnyRole([Roles.SUPER_ADMIN, Roles.ADMIN, Roles.ACCOUNT_MANAGER], accessOptions),
    ]);
    const hasManagementRole = hasManagementRoleDb || hasManagementMetadataRole;

    if (!(canUpdateClients || hasManagementRole)) {
      return apiForbidden(request);
    }

    const body = await request.json();

    const updateData: Record<string, unknown> = {};
    if (body.company_name !== undefined) updateData.companyName = body.company_name;
    if (body.email !== undefined) updateData.email = body.email;
    if (body.domain !== undefined) updateData.domain = body.domain;
    if (body.industry !== undefined) updateData.industry = body.industry;
    if (body.logo_url !== undefined) updateData.logoUrl = body.logo_url;
    if (body.status !== undefined) updateData.status = body.status;
    if (body.primary_contact_id !== undefined) updateData.primaryContactId = body.primary_contact_id;
    if (body.phone !== undefined) updateData.phone = body.phone;
    if (body.address !== undefined) updateData.address = body.address;
    if (body.city !== undefined) updateData.city = body.city;
    if (body.state !== undefined) updateData.state = body.state;
    if (body.zip_code !== undefined) updateData.zipCode = body.zip_code;
    if (body.country !== undefined) updateData.country = body.country;
    if (body.website !== undefined) updateData.website = body.website;

    updateData.updatedAt = new Date();

    const [updatedClient] = await db
      .update(clients)
      .set(updateData)
      .where(eq(clients.id, id))
      .returning();

    if (!updatedClient) {
      return apiNotFound(request, "Client not found");
    }

    return apiSuccess(request, updatedClient, { extra: { client: updatedClient } });
  } catch (error) {
    console.error("Error updating client:", error);
    return apiInternalError(
      request,
      error instanceof Error ? error.message : "Failed to update client",
    );
  }
}
