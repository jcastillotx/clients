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

const createMentionSchema = z.object({
  platform: z.string().min(1, "Platform is required"),
  mention_text: z.string().min(1, "Mention text is required"),
  sentiment: z.enum(["positive", "neutral", "negative"]).optional().nullable(),
  author: z.string().optional().nullable(),
  url: z.string().url().optional().nullable(),
  posted_at: z.string().datetime().optional().nullable(),
  meta: z
    .object({
      reach: z.number().optional(),
      engagement: z.number().optional(),
      followers: z.number().optional(),
      likes: z.number().optional(),
      shares: z.number().optional(),
      comments: z.number().optional(),
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
    const sentiment = searchParams.get("sentiment");
    const responded = searchParams.get("responded");
    const platform = searchParams.get("platform");
    const clientIdParam = searchParams.get("client_id");
    const limit = Math.min(100, Math.max(1, parseInt(searchParams.get("limit") ?? "20", 10)));
    const offset = Math.max(0, parseInt(searchParams.get("offset") ?? "0", 10));

    let query = supabase
      .from("brand_mentions")
      .select("*", { count: "exact" })
      .order("created_at", { ascending: false })
      .range(offset, offset + limit - 1);

    // Scope by client
    if (clientIdParam) {
      query = query.eq("client_id", clientIdParam);
    } else if (!access.isAdmin && access.clientId) {
      query = query.eq("client_id", access.clientId);
    }

    if (sentiment) {
      query = query.eq("sentiment", sentiment);
    }

    if (platform) {
      query = query.eq("platform", platform);
    }

    if (responded === "true") {
      query = query.not("responded_at", "is", null);
    } else if (responded === "false") {
      query = query.is("responded_at", null);
    }

    const { data, error, count } = await query;
    if (error) return apiInternalError(req, error.message);

    const rows = data ?? [];
    const total = count ?? null;
    return apiSuccess(req, rows, {
      pagination: {
        total,
        limit,
        offset,
        hasMore: total !== null ? offset + rows.length < total : rows.length === limit,
      },
      extra: { mentions: rows },
    });
  } catch (err) {
    console.error("Error fetching brand mentions:", err);
    return apiInternalError(req, "Failed to fetch brand mentions");
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
    const validated = createMentionSchema.parse(body);

    const supabase = await createClient();
    const clientId = access.clientId ?? (body.client_id as string | undefined) ?? null;

    const { data, error } = await supabase
      .from("brand_mentions")
      .insert({
        client_id: clientId,
        platform: validated.platform,
        mention_text: validated.mention_text,
        sentiment: validated.sentiment ?? null,
        author: validated.author ?? null,
        url: validated.url ?? null,
        posted_at: validated.posted_at ?? null,
        meta: validated.meta ?? null,
      })
      .select()
      .single();

    if (error) return apiInternalError(req, error.message);

    return apiSuccess(req, data, { status: 201, extra: { mention: data } });
  } catch (err) {
    if (err instanceof z.ZodError) return apiValidationError(req, err);
    console.error("Error creating brand mention:", err);
    return apiInternalError(req, "Failed to create brand mention");
  }
}
