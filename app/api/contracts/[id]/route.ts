import { createClient } from "@/lib/supabase/server";
import {
  apiError,
  apiForbidden,
  apiInternalError,
  apiNotFound,
  apiSuccess,
  apiUnauthorized,
} from "@/lib/api/response";
import { hasPermission } from "@/lib/rbac/permissions";
import { canAccessClient, resolveRouteAccess } from "@/lib/auth/route-access";
import { z } from "zod";

const updateSchema = z.object({
  title: z.string().min(1).optional(),
  description: z.string().nullable().optional(),
  type: z.string().optional(),
  status: z.string().optional(),
  startDate: z.string().nullable().optional(),
  endDate: z.string().nullable().optional(),
  signedDate: z.string().nullable().optional(),
  value: z.number().int().nullable().optional(),
  currency: z.string().optional(),
  billingCycle: z.string().nullable().optional(),
  documentId: z.string().uuid().nullable().optional(),
  clientSignedBy: z.string().nullable().optional(),
  clientSignedAt: z.string().nullable().optional(),
  terms: z.record(z.unknown()).nullable().optional(),
  autoRenew: z.boolean().optional(),
  noticeRequired: z.number().int().nullable().optional(),
  tags: z.array(z.string()).nullable().optional(),
  customFields: z.record(z.unknown()).nullable().optional(),
});

interface RouteContext {
  params: Promise<{ id: string }>;
}

export async function GET(request: Request, context: RouteContext) {
  try {
    const { id } = await context.params;
    const supabase = await createClient();
    const {
      data: { user },
      error: authError,
    } = await supabase.auth.getUser();

    if (authError || !user) {
      return apiUnauthorized(request);
    }

    const access = await resolveRouteAccess(supabase, user);

    const { data: contract, error } = await supabase
      .from("contracts")
      .select(
        `
        *,
        client:clients(id, company_name),
        document:documents(id, name, file_name, storage_path)
      `,
      )
      .eq("id", id)
      .is("deleted_at", null)
      .maybeSingle();

    if (error) throw error;

    if (!contract) {
      return apiNotFound(request, "Contract not found");
    }

    // Enforce client-level access
    const clientId = (contract as unknown as { client_id: string }).client_id;
    if (!canAccessClient(access, clientId)) {
      return apiForbidden(request);
    }

    return apiSuccess(request, contract, { extra: { contract } });
  } catch (error) {
    console.error("Error fetching contract:", error);
    return apiInternalError(
      request,
      error instanceof Error ? error.message : "Failed to fetch contract",
    );
  }
}

export async function PATCH(request: Request, context: RouteContext) {
  try {
    const { id } = await context.params;
    const supabase = await createClient();
    const {
      data: { user },
      error: authError,
    } = await supabase.auth.getUser();

    if (authError || !user) {
      return apiUnauthorized(request);
    }

    const access = await resolveRouteAccess(supabase, user);

    const canUpdate = await hasPermission("contracts.update");
    if (!canUpdate) {
      return apiForbidden(request, "Permission denied");
    }

    // Fetch existing contract to validate access
    const { data: existing, error: fetchError } = await supabase
      .from("contracts")
      .select("id, client_id")
      .eq("id", id)
      .is("deleted_at", null)
      .maybeSingle();

    if (fetchError) throw fetchError;

    if (!existing) {
      return apiNotFound(request, "Contract not found");
    }

    if (!canAccessClient(access, (existing as unknown as { client_id: string }).client_id)) {
      return apiForbidden(request);
    }

    const body = await request.json();
    const parsed = updateSchema.safeParse(body);

    if (!parsed.success) {
      return apiError(request, {
        status: 400,
        code: "BAD_REQUEST",
        message: parsed.error.issues[0]?.message ?? "Invalid request body",
      });
    }

    const {
      title,
      description,
      type,
      status,
      startDate,
      endDate,
      signedDate,
      value,
      currency,
      billingCycle,
      documentId,
      clientSignedBy,
      clientSignedAt,
      terms,
      autoRenew,
      noticeRequired,
      tags,
      customFields,
    } = parsed.data;

    const updates: Record<string, unknown> = { updated_at: new Date().toISOString() };

    if (title !== undefined) updates.title = title;
    if (description !== undefined) updates.description = description;
    if (type !== undefined) updates.type = type;
    if (status !== undefined) updates.status = status;
    if (startDate !== undefined) updates.start_date = startDate;
    if (endDate !== undefined) updates.end_date = endDate;
    if (signedDate !== undefined) updates.signed_date = signedDate;
    if (value !== undefined) updates.value = value;
    if (currency !== undefined) updates.currency = currency;
    if (billingCycle !== undefined) updates.billing_cycle = billingCycle;
    if (documentId !== undefined) updates.document_id = documentId;
    if (clientSignedBy !== undefined) updates.client_signed_by = clientSignedBy;
    if (clientSignedAt !== undefined) updates.client_signed_at = clientSignedAt;
    if (terms !== undefined) updates.terms = terms;
    if (autoRenew !== undefined) updates.auto_renew = autoRenew;
    if (noticeRequired !== undefined) updates.notice_required = noticeRequired;
    if (tags !== undefined) updates.tags = tags;
    if (customFields !== undefined) updates.custom_fields = customFields;

    const { data: contract, error } = await supabase
      .from("contracts")
      .update(updates)
      .eq("id", id)
      .select()
      .single();

    if (error) throw error;

    return apiSuccess(request, contract, { extra: { contract } });
  } catch (error) {
    console.error("Error updating contract:", error);
    return apiInternalError(
      request,
      error instanceof Error ? error.message : "Failed to update contract",
    );
  }
}

export async function DELETE(request: Request, context: RouteContext) {
  try {
    const { id } = await context.params;
    const supabase = await createClient();
    const {
      data: { user },
      error: authError,
    } = await supabase.auth.getUser();

    if (authError || !user) {
      return apiUnauthorized(request);
    }

    const access = await resolveRouteAccess(supabase, user);

    const canDelete = await hasPermission("contracts.delete");
    if (!canDelete) {
      return apiForbidden(request, "Permission denied");
    }

    // Fetch existing contract to validate access
    const { data: existing, error: fetchError } = await supabase
      .from("contracts")
      .select("id, client_id")
      .eq("id", id)
      .is("deleted_at", null)
      .maybeSingle();

    if (fetchError) throw fetchError;

    if (!existing) {
      return apiNotFound(request, "Contract not found");
    }

    if (!canAccessClient(access, (existing as unknown as { client_id: string }).client_id)) {
      return apiForbidden(request);
    }

    // Soft delete
    const { error } = await supabase
      .from("contracts")
      .update({ deleted_at: new Date().toISOString() })
      .eq("id", id);

    if (error) throw error;

    return apiSuccess(request, null, { extra: { deleted: true } });
  } catch (error) {
    console.error("Error deleting contract:", error);
    return apiInternalError(
      request,
      error instanceof Error ? error.message : "Failed to delete contract",
    );
  }
}
