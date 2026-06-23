import { NextRequest } from "next/server";
import { z } from "zod";
import { createClient } from "@/lib/supabase/server";
import { resolveStaffAccess } from "@/lib/api/resolve-staff-access";
import { slugify } from "@/lib/api/slug";
import { kbArticleSchema } from "@/lib/validations/partners-kb";
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

    const supabase = await createClient();
    const categoryId = request.nextUrl.searchParams.get("categoryId");

    let query = supabase
      .from("knowledge_base_articles")
      .select(
        `
        *,
        category:knowledge_base_categories(id, name, slug)
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
        excerpt: row.excerpt,
        categoryId: row.category_id,
        categoryName: category?.name ?? "Uncategorized",
        viewCount: row.view_count,
        helpfulCount: row.helpful_count,
        isPublished: row.is_published,
        createdAt: row.created_at,
      };
    });

    return apiSuccess(request, rows);
  } catch (error) {
    console.error("Error fetching KB articles:", error);
    return apiInternalError(request, "Failed to fetch articles");
  }
}

export async function POST(request: NextRequest) {
  try {
    const access = await resolveStaffAccess();
    if (!access) {
      return apiUnauthorized(request);
    }
    if (!access.isStaff) {
      return apiForbidden(request);
    }

    const body = await request.json();
    const validated = kbArticleSchema.parse(body);
    const slug = validated.slug?.trim() || slugify(validated.title);
    const supabase = await createClient();

    const { data, error } = await supabase
      .from("knowledge_base_articles")
      .insert({
        category_id: validated.categoryId,
        title: validated.title,
        slug,
        excerpt: validated.excerpt || null,
        content: validated.content,
        video_url: validated.videoUrl || null,
        is_published: validated.isPublished,
        published_at: validated.isPublished ? new Date().toISOString() : null,
        created_by: access.userId,
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
    console.error("Error creating KB article:", error);
    return apiInternalError(request, "Failed to create article");
  }
}
