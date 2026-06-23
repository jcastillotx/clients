import { NextRequest } from "next/server";
import { z } from "zod";
import { createClient } from "@/lib/supabase/server";
import { resolveStaffAccess } from "@/lib/api/resolve-staff-access";
import { updateReferralSchema } from "@/lib/validations/partners-kb";
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
    const validated = updateReferralSchema.parse(body);
    const supabase = await createClient();

    const patch: Record<string, unknown> = {};
    if (validated.status !== undefined) {
      patch.status = validated.status;
      if (validated.status === "converted") {
        patch.converted_at = new Date().toISOString();
      }
    }
    if (validated.referredName !== undefined) patch.referred_name = validated.referredName;
    if (validated.referredEmail !== undefined) patch.referred_email = validated.referredEmail || null;
    if (validated.referredPhone !== undefined) patch.referred_phone = validated.referredPhone;
    if (validated.commissionAmount !== undefined) {
      patch.commission_amount = validated.commissionAmount;
    }
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
      .from("referrals")
      .update(patch)
      .eq("id", id)
      .select(
        `
        *,
        partner:partners(id, company_name, code)
      `,
      )
      .maybeSingle();

    if (error) {
      return apiInternalError(request, error.message);
    }
    if (!data) {
      return apiNotFound(request, "Referral not found");
    }

    return apiSuccess(request, data);
  } catch (error) {
    if (error instanceof z.ZodError) {
      return apiValidationError(request, error);
    }
    console.error("Error updating referral:", error);
    return apiInternalError(request, "Failed to update referral");
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
    const { error } = await supabase.from("referrals").delete().eq("id", id);

    if (error) {
      return apiInternalError(request, error.message);
    }

    return apiSuccess(request, { id });
  } catch (error) {
    console.error("Error deleting referral:", error);
    return apiInternalError(request, "Failed to delete referral");
  }
}
