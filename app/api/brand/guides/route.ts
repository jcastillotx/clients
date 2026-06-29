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

const createGuideSchema = z.object({
  slug: z.string().min(1, "Slug is required"),
  status: z.enum(["draft", "published"]).optional().default("draft"),
  cover_image: z.string().optional().nullable(),
  is_public: z.boolean().optional().default(false),
  password_protected: z.boolean().optional().default(false),
  password: z.string().optional().nullable(),
  meta: z.record(z.unknown()).optional().nullable(),
});

export async function GET(req: NextRequest) {
  try {
    const access = await resolveStaffAccess();
    if (!access) return apiUnauthorized(req);
    if (!access.isStaff) return apiForbidden(req);

    const supabase = await createClient();
    const { searchParams } = req.nextUrl;
    const status = searchParams.get("status");
    const clientIdParam = searchParams.get("client_id");

    let query = supabase
      .from("brand_guides")
      .select(`
        *,
        creator:users!brand_guides_created_by_fkey(id, name)
      `)
      .order("created_at", { ascending: false });

    // Scope by client
    if (clientIdParam) {
      query = query.eq("client_id", clientIdParam);
    } else if (!access.isAdmin && access.clientId) {
      query = query.eq("client_id", access.clientId);
    }

    if (status) {
      query = query.eq("status", status);
    }

    const { data, error } = await query;
    if (error) return apiInternalError(req, error.message);

    const rows = data ?? [];
    return apiSuccess(req, rows, { extra: { guides: rows } });
  } catch (err) {
    console.error("Error fetching brand guides:", err);
    return apiInternalError(req, "Failed to fetch brand guides");
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
    const validated = createGuideSchema.parse(body);

    const supabase = await createClient();
    const clientId = access.clientId ?? (body.client_id as string | undefined) ?? null;

    const { data, error } = await supabase
      .from("brand_guides")
      .insert({
        client_id: clientId,
        slug: validated.slug,
        status: validated.status,
        cover_image: validated.cover_image ?? null,
        is_public: validated.is_public,
        password_protected: validated.password_protected,
        password: validated.password ?? null,
        created_by: access.userId,
        meta: validated.meta ?? null,
      })
      .select()
      .single();

    if (error) {
      // Unique constraint on slug
      if (error.code === "23505") {
        return apiError(req, {
          status: 400,
          code: "BAD_REQUEST",
          message: "A brand guide with this slug already exists",
        });
      }
      return apiInternalError(req, error.message);
    }

    return apiSuccess(req, data, { status: 201, extra: { guide: data } });
  } catch (err) {
    if (err instanceof z.ZodError) return apiValidationError(req, err);
    console.error("Error creating brand guide:", err);
    return apiInternalError(req, "Failed to create brand guide");
  }
}
