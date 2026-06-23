import { NextRequest } from "next/server";
import { z } from "zod";
import { createClient } from "@/lib/supabase/server";
import { resolveStaffAccess } from "@/lib/api/resolve-staff-access";
import { updatePartnerSchema } from "@/lib/validations/partners-kb";
import {
  apiError,
  apiForbidden,
  apiInternalError,
  apiNotFound,
  apiSuccess,
  apiUnauthorized,
  apiValidationError,
} from "@/lib/api/response";

type RouteContext = { params: Promise<{ id: string }> };

export async function GET(request: NextRequest, context: RouteContext) {
  try {
    const { id } = await context.params;
    const access = await resolveStaffAccess();
    if (!access) {
      return apiUnauthorized(request);
    }
    if (!access.isStaff) {
      return apiForbidden(request);
    }

    const supabase = await createClient();
    const { data, error } = await supabase.from("partners").select("*").eq("id", id).maybeSingle();

    if (error) {
      return apiInternalError(request, error.message);
    }
    if (!data) {
      return apiNotFound(request, "Partner not found");
    }

    return apiSuccess(request, data);
  } catch (error) {
    console.error("Error fetching partner:", error);
    return apiInternalError(request, "Failed to fetch partner");
  }
}

export async function PATCH(request: NextRequest, context: RouteContext) {
  try {
    const { id } = await context.params;
    const access = await resolveStaffAccess();
    if (!access) {
      return apiUnauthorized(request);
    }
    if (!access.isAdmin) {
      return apiForbidden(request);
    }

    const body = await request.json();
    const validated = updatePartnerSchema.parse(body);
    const supabase = await createClient();

    const patch: Record<string, unknown> = {};
    if (validated.companyName !== undefined) patch.company_name = validated.companyName;
    if (validated.contactName !== undefined) patch.contact_name = validated.contactName;
    if (validated.email !== undefined) patch.email = validated.email;
    if (validated.phone !== undefined) patch.phone = validated.phone;
    if (validated.website !== undefined) patch.website = validated.website || null;
    if (validated.partnerType !== undefined) patch.partner_type = validated.partnerType;
    if (validated.status !== undefined) patch.status = validated.status;
    if (validated.commissionRate !== undefined) patch.commission_rate = validated.commissionRate;
    if (validated.code !== undefined) patch.code = validated.code;
    if (validated.notes !== undefined) {
      patch.metadata = validated.notes ? { notes: validated.notes } : null;
    }

    if (Object.keys(patch).length === 0) {
      return apiError(request, {
        status: 400,
        code: "BAD_REQUEST",
        message: "No fields to update",
      });
    }

    const { data, error } = await supabase
      .from("partners")
      .update(patch)
      .eq("id", id)
      .select("*")
      .maybeSingle();

    if (error) {
      return apiInternalError(request, error.message);
    }
    if (!data) {
      return apiNotFound(request, "Partner not found");
    }

    return apiSuccess(request, data);
  } catch (error) {
    if (error instanceof z.ZodError) {
      return apiValidationError(request, error);
    }
    console.error("Error updating partner:", error);
    return apiInternalError(request, "Failed to update partner");
  }
}

export async function DELETE(request: NextRequest, context: RouteContext) {
  try {
    const { id } = await context.params;
    const access = await resolveStaffAccess();
    if (!access) {
      return apiUnauthorized(request);
    }
    if (!access.isAdmin) {
      return apiForbidden(request);
    }

    const supabase = await createClient();
    const { error } = await supabase.from("partners").delete().eq("id", id);

    if (error) {
      return apiInternalError(request, error.message);
    }

    return apiSuccess(request, { id });
  } catch (error) {
    console.error("Error deleting partner:", error);
    return apiInternalError(request, "Failed to delete partner");
  }
}
