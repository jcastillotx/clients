import { createClient } from "@/lib/supabase/server";
import {
  apiError,
  apiForbidden,
  apiInternalError,
  apiSuccess,
  apiUnauthorized,
  apiValidationError,
} from "@/lib/api/response";
import { hasPermission } from "@/lib/rbac/permissions";
import { canAccessClient, resolveRouteAccess } from "@/lib/auth/route-access";
import { z } from "zod";

const querySchema = z.object({
  clientId: z.string().uuid().optional(),
  status: z.string().optional(),
});

export async function GET(request: Request) {
  try {
    const supabase = await createClient();
    const {
      data: { user },
      error: authError,
    } = await supabase.auth.getUser();

    if (authError || !user) {
      return apiUnauthorized(request);
    }

    const access = await resolveRouteAccess(supabase, user);
    const { searchParams } = new URL(request.url);
    const parsed = querySchema.safeParse({
      clientId: searchParams.get("clientId") || undefined,
      status: searchParams.get("status") || undefined,
    });

    if (!parsed.success) {
      return apiValidationError(request, parsed.error);
    }

    const { clientId, status } = parsed.data;

    if (clientId && !canAccessClient(access, clientId)) {
      return apiForbidden(request);
    }

    let query = supabase
      .from("contracts")
      .select(
        `
        *,
        client:clients(id, company_name),
        document:documents(id, name, file_name, storage_path)
      `,
      )
      .is("deleted_at", null)
      .order("created_at", { ascending: false });

    if (clientId) {
      query = query.eq("client_id", clientId);
    } else if (!access.isAdmin && access.clientId) {
      query = query.eq("client_id", access.clientId);
    }

    if (status) {
      query = query.eq("status", status);
    }

    const { data: contracts, error } = await query;

    if (error) throw error;

    const list = contracts || [];
    return apiSuccess(request, list, { extra: { contracts: list } });
  } catch (error) {
    console.error("Error fetching contracts:", error);
    return apiInternalError(
      request,
      error instanceof Error ? error.message : "Failed to fetch contracts",
    );
  }
}

export async function POST(request: Request) {
  try {
    const supabase = await createClient();
    const {
      data: { user },
      error: authError,
    } = await supabase.auth.getUser();

    if (authError || !user) {
      return apiUnauthorized(request);
    }

    const access = await resolveRouteAccess(supabase, user);

    const canCreate = await hasPermission("contracts.create");
    if (!canCreate) {
      return apiForbidden(request, "Permission denied");
    }

    const body = await request.json();
    const {
      title,
      description,
      clientId,
      type,
      startDate,
      endDate,
      value,
      currency,
      billingCycle,
      terms,
      autoRenew,
      noticeRequired,
      tags,
      customFields,
    } = body;

    if (!clientId) {
      return apiError(request, {
        status: 400,
        code: "BAD_REQUEST",
        message: "Client ID is required",
      });
    }

    if (!canAccessClient(access, clientId)) {
      return apiForbidden(request);
    }

    const { data: contractNumber } = await supabase.rpc(
      "generate_contract_number",
    );

    const { data: contract, error } = await supabase
      .from("contracts")
      .insert({
        title,
        description,
        contract_number: contractNumber,
        client_id: clientId,
        type,
        start_date: startDate,
        end_date: endDate,
        value,
        currency,
        billing_cycle: billingCycle,
        terms,
        auto_renew: autoRenew,
        notice_required: noticeRequired,
        tags,
        custom_fields: customFields,
      })
      .select()
      .single();

    if (error) throw error;

    return apiSuccess(request, contract, { status: 201, extra: { contract } });
  } catch (error) {
    console.error("Error creating contract:", error);
    return apiInternalError(
      request,
      error instanceof Error ? error.message : "Failed to create contract",
    );
  }
}
