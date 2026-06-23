import { NextRequest } from "next/server";
import { z } from "zod";
import { createClient } from "@/lib/supabase/server";
import { resolveStaffAccess } from "@/lib/api/resolve-staff-access";
import { createReferralSchema } from "@/lib/validations/partners-kb";
import {
  apiForbidden,
  apiInternalError,
  apiSuccess,
  apiUnauthorized,
  apiValidationError,
} from "@/lib/api/response";

export async function GET(request: NextRequest) {
  try {
    const access = await resolveStaffAccess();
    if (!access) {
      return apiUnauthorized(request);
    }
    if (!access.isStaff) {
      return apiForbidden(request);
    }

    const supabase = await createClient();
    let query = supabase
      .from("referrals")
      .select(
        `
        *,
        partner:partners(id, company_name, code, commission_rate)
      `,
      )
      .order("referred_at", { ascending: false });

    const partnerId = request.nextUrl.searchParams.get("partnerId");
    if (partnerId) {
      query = query.eq("partner_id", partnerId);
    }

    const { data, error } = await query;
    if (error) {
      return apiInternalError(request, error.message);
    }

    return apiSuccess(request, data ?? []);
  } catch (error) {
    console.error("Error fetching referrals:", error);
    return apiInternalError(request, "Failed to fetch referrals");
  }
}

export async function POST(request: NextRequest) {
  try {
    const access = await resolveStaffAccess();
    if (!access) {
      return apiUnauthorized(request);
    }
    if (!access.isAdmin) {
      return apiForbidden(request);
    }

    const body = await request.json();
    const validated = createReferralSchema.parse(body);
    const supabase = await createClient();

    const { data: partner, error: partnerError } = await supabase
      .from("partners")
      .select("id, code")
      .eq("id", validated.partnerId)
      .maybeSingle();

    if (partnerError) {
      return apiInternalError(request, partnerError.message);
    }
    if (!partner) {
      return apiInternalError(request, "Partner not found");
    }

    const metadata = validated.notes ? { notes: validated.notes } : null;

    const { data, error } = await supabase
      .from("referrals")
      .insert({
        partner_id: validated.partnerId,
        referral_code: partner.code,
        referred_name: validated.referredName,
        referred_email: validated.referredEmail || null,
        referred_phone: validated.referredPhone || null,
        status: validated.status,
        metadata,
      })
      .select(
        `
        *,
        partner:partners(id, company_name, code)
      `,
      )
      .single();

    if (error) {
      return apiInternalError(request, error.message);
    }

    const { data: partnerRow } = await supabase
      .from("partners")
      .select("total_referrals")
      .eq("id", validated.partnerId)
      .maybeSingle();

    if (partnerRow) {
      await supabase
        .from("partners")
        .update({ total_referrals: (partnerRow.total_referrals ?? 0) + 1 })
        .eq("id", validated.partnerId);
    }

    return apiSuccess(request, data, { status: 201 });
  } catch (error) {
    if (error instanceof z.ZodError) {
      return apiValidationError(request, error);
    }
    console.error("Error creating referral:", error);
    return apiInternalError(request, "Failed to create referral");
  }
}
