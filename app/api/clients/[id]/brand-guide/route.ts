import { NextRequest } from "next/server";
import { z } from "zod";
import { resolveStaffAccess } from "@/lib/api/resolve-staff-access";
import {
  apiForbidden,
  apiInternalError,
  apiNotFound,
  apiSuccess,
  apiUnauthorized,
  apiValidationError,
} from "@/lib/api/response";
import {
  makeClientBrandGuideSlug,
  saveClientBrandGuideSchema,
} from "@/lib/brand/client-brand-guide";
import {
  createAdminClientIfAvailable,
  createClient,
} from "@/lib/supabase/server";

type RouteContext = { params: Promise<{ id: string }> };

async function getAuthorizedContext(request: NextRequest, clientId: string) {
  const access = await resolveStaffAccess();

  if (!access) {
    return { error: apiUnauthorized(request) };
  }

  if (!access.isStaff) {
    return { error: apiForbidden(request) };
  }

  const supabase = await createClient();
  const dbClient = createAdminClientIfAvailable() ?? supabase;
  const { data: client, error } = await dbClient
    .from("clients")
    .select("id, company_name, logo_url")
    .eq("id", clientId)
    .is("deleted_at", null)
    .maybeSingle();

  if (error) {
    return { error: apiInternalError(request, error.message) };
  }

  if (!client) {
    return { error: apiNotFound(request, "Client not found") };
  }

  return { access, client, dbClient };
}

export async function GET(request: NextRequest, context: RouteContext) {
  try {
    const { id } = await context.params;
    const authorized = await getAuthorizedContext(request, id);
    if ("error" in authorized) return authorized.error;

    const { data, error } = await authorized.dbClient
      .from("brand_guides")
      .select("*")
      .eq("client_id", id)
      .order("updated_at", { ascending: false })
      .limit(1)
      .maybeSingle();

    if (error) return apiInternalError(request, error.message);

    return apiSuccess(request, data, { extra: { guide: data } });
  } catch (error) {
    console.error("Error fetching client brand guide:", error);
    return apiInternalError(request, "Failed to fetch client brand guide");
  }
}

export async function PUT(request: NextRequest, context: RouteContext) {
  try {
    const { id } = await context.params;
    const authorized = await getAuthorizedContext(request, id);
    if ("error" in authorized) return authorized.error;

    const validated = saveClientBrandGuideSchema.parse(await request.json());
    const { access, client, dbClient } = authorized;
    const now = new Date().toISOString();

    const { data: existing, error: lookupError } = await dbClient
      .from("brand_guides")
      .select("id")
      .eq("client_id", id)
      .order("updated_at", { ascending: false })
      .limit(1)
      .maybeSingle();

    if (lookupError) return apiInternalError(request, lookupError.message);

    const values = {
      status: validated.status,
      cover_image: validated.content.logoUrl || null,
      meta: validated.content,
      published_at: validated.status === "published" ? now : null,
      updated_at: now,
    };

    const mutation = existing
      ? dbClient
          .from("brand_guides")
          .update(values)
          .eq("id", existing.id)
          .select()
          .single()
      : dbClient
          .from("brand_guides")
          .insert({
            ...values,
            client_id: id,
            slug: makeClientBrandGuideSlug(client.company_name, id),
            created_by: access.userId,
          })
          .select()
          .single();

    const { data, error } = await mutation;
    if (error) return apiInternalError(request, error.message);

    return apiSuccess(request, data, { extra: { guide: data } });
  } catch (error) {
    if (error instanceof z.ZodError) {
      return apiValidationError(request, error);
    }

    console.error("Error saving client brand guide:", error);
    return apiInternalError(request, "Failed to save client brand guide");
  }
}
