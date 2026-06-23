import { NextRequest } from "next/server";
import { z } from "zod";
import { createClient } from "@/lib/supabase/server";
import { resolveStaffAccess } from "@/lib/api/resolve-staff-access";
import { uniqueCodeFromName } from "@/lib/api/slug";
import { createPartnerSchema } from "@/lib/validations/partners-kb";
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
    const { data, error } = await supabase
      .from("partners")
      .select("*")
      .order("created_at", { ascending: false });

    if (error) {
      return apiInternalError(request, error.message);
    }

    return apiSuccess(request, data ?? []);
  } catch (error) {
    console.error("Error fetching partners:", error);
    return apiInternalError(request, "Failed to fetch partners");
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
    const validated = createPartnerSchema.parse(body);
    const supabase = await createClient();

    const code = validated.code?.trim() || uniqueCodeFromName(validated.companyName);
    const metadata = validated.notes ? { notes: validated.notes } : null;

    const { data, error } = await supabase
      .from("partners")
      .insert({
        company_name: validated.companyName,
        contact_name: validated.contactName,
        email: validated.email,
        phone: validated.phone || null,
        website: validated.website || null,
        partner_type: validated.partnerType,
        status: validated.status,
        commission_rate: validated.commissionRate,
        code,
        metadata,
      })
      .select("*")
      .single();

    if (error) {
      return apiInternalError(request, error.message);
    }

    return apiSuccess(request, data, { status: 201 });
  } catch (error) {
    if (error instanceof z.ZodError) {
      return apiValidationError(request, error);
    }
    console.error("Error creating partner:", error);
    return apiInternalError(request, "Failed to create partner");
  }
}
