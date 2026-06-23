import { NextRequest } from "next/server";
import { z } from "zod";
import { createClient } from "@/lib/supabase/server";
import { resolveStaffAccess } from "@/lib/api/resolve-staff-access";
import { slugify } from "@/lib/api/slug";
import { staffGuideSchema } from "@/lib/validations/partners-kb";
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
    const categoryId = request.nextUrl.searchParams.get("categoryId");

    let query = supabase
      .from("staff_guides")
      .select(
        `
        *,
        category:staff_guide_categories(id, name, slug)
      `,
      )
      .order("created_at", { ascending: false });

    if (categoryId) {
      query = query.eq("category_id", categoryId);
    }

    const { data, error } = await query;
    if (error) {
      return apiInternalError(request, error.message);
    }

    const rows = (data ?? []).map((row) => {
      const category = Array.isArray(row.category) ? row.category[0] : row.category;
      return {
        id: row.id,
        title: row.title,
        slug: row.slug,
        summary: row.summary,
        categoryId: row.category_id,
        categoryName: category?.name ?? "Uncategorized",
        serviceTier: row.service_tier,
        price: row.price,
        viewCount: row.view_count,
        isInternal: row.is_internal,
        isPublished: row.is_published,
        createdAt: row.created_at,
      };
    });

    return apiSuccess(request, rows);
  } catch (error) {
    console.error("Error fetching staff guides:", error);
    return apiInternalError(request, "Failed to fetch guides");
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
    const validated = staffGuideSchema.parse(body);
    const slug = validated.slug?.trim() || slugify(validated.title);
    const supabase = await createClient();

    const { data, error } = await supabase
      .from("staff_guides")
      .insert({
        category_id: validated.categoryId,
        title: validated.title,
        slug,
        summary: validated.summary || null,
        content: validated.content,
        service_tier: validated.serviceTier || null,
        price: validated.price ?? null,
        commitment: validated.commitment || null,
        is_internal: validated.isInternal,
        is_published: validated.isPublished,
        published_at: validated.isPublished ? new Date().toISOString() : null,
        author_id: access.userId,
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
    console.error("Error creating staff guide:", error);
    return apiInternalError(request, "Failed to create guide");
  }
}
