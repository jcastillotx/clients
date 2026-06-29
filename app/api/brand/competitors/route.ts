import { NextRequest } from "next/server";
import { z } from "zod";
import { createClient } from "@/lib/supabase/server";
import { resolveStaffAccess } from "@/lib/api/resolve-staff-access";
import {
  apiError,
  apiForbidden,
  apiInternalError,
  apiSuccess,
  apiUnauthorized,
  apiValidationError,
} from "@/lib/api/response";

const createCompetitorSchema = z.object({
  competitor_name: z.string().min(1, "Competitor name is required"),
  website_url: z.string().url().optional().nullable(),
  positioning: z.string().optional().nullable(),
  target_audience: z.string().optional().nullable(),
  key_differentiators: z.array(z.string()).optional().nullable(),
  is_active: z.boolean().optional().default(true),
  meta: z
    .object({
      socialLinks: z
        .object({
          facebook: z.string().optional(),
          twitter: z.string().optional(),
          linkedin: z.string().optional(),
          instagram: z.string().optional(),
        })
        .optional(),
      strengths: z.array(z.string()).optional(),
      weaknesses: z.array(z.string()).optional(),
      marketShare: z.number().optional(),
    })
    .optional()
    .nullable(),
});

export async function GET(req: NextRequest) {
  try {
    const access = await resolveStaffAccess();
    if (!access) return apiUnauthorized(req);
    if (!access.isStaff) return apiForbidden(req);

    const supabase = await createClient();
    const { searchParams } = req.nextUrl;
    const search = searchParams.get("search");
    const clientIdParam = searchParams.get("client_id");
    const isActiveParam = searchParams.get("is_active");

    let query = supabase
      .from("brand_competitors")
      .select("*")
      .order("created_at", { ascending: false });

    // Scope by client: admins can see all (or filter by client_id param), staff see their own
    if (clientIdParam) {
      query = query.eq("client_id", clientIdParam);
    } else if (!access.isAdmin && access.clientId) {
      query = query.eq("client_id", access.clientId);
    }

    if (isActiveParam !== null) {
      query = query.eq("is_active", isActiveParam === "true");
    }

    if (search) {
      query = query.ilike("competitor_name", `%${search}%`);
    }

    const { data, error } = await query;
    if (error) return apiInternalError(req, error.message);

    const rows = data ?? [];
    return apiSuccess(req, rows, { extra: { competitors: rows } });
  } catch (err) {
    console.error("Error fetching brand competitors:", err);
    return apiInternalError(req, "Failed to fetch brand competitors");
  }
}

export async function POST(req: NextRequest) {
  try {
    const access = await resolveStaffAccess();
    if (!access) return apiUnauthorized(req);
    if (!access.isStaff) return apiForbidden(req);

    if (!access.clientId && !access.isAdmin) {
      return apiError(req, {
        status: 400,
        code: "BAD_REQUEST",
        message: "User not associated with a client",
      });
    }

    const body = await req.json();
    const validated = createCompetitorSchema.parse(body);

    // For admin users creating without a client context, require explicit client_id in body
    const supabase = await createClient();
    const clientId = access.clientId ?? (body.client_id as string | undefined) ?? null;

    const { data, error } = await supabase
      .from("brand_competitors")
      .insert({
        client_id: clientId,
        competitor_name: validated.competitor_name,
        website_url: validated.website_url ?? null,
        positioning: validated.positioning ?? null,
        target_audience: validated.target_audience ?? null,
        key_differentiators: validated.key_differentiators ?? null,
        is_active: validated.is_active,
        meta: validated.meta ?? null,
      })
      .select()
      .single();

    if (error) return apiInternalError(req, error.message);

    return apiSuccess(req, data, { status: 201, extra: { competitor: data } });
  } catch (err) {
    if (err instanceof z.ZodError) return apiValidationError(req, err);
    console.error("Error creating brand competitor:", err);
    return apiInternalError(req, "Failed to create brand competitor");
  }
}
