import { NextRequest } from "next/server";
import { z } from "zod";
import { createClient } from "@/lib/supabase/server";
import { resolveStaffAccess } from "@/lib/api/resolve-staff-access";
import { slugify } from "@/lib/api/slug";
import { kbCategorySchema } from "@/lib/validations/partners-kb";
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
    const { data: categories, error } = await supabase
      .from("knowledge_base_categories")
      .select("*")
      .order("position", { ascending: true });

    if (error) {
      return apiInternalError(request, error.message);
    }

    const { data: articles } = await supabase.from("knowledge_base_articles").select("id, category_id");

    const countByCategory = new Map<string, number>();
    for (const article of articles ?? []) {
      countByCategory.set(article.category_id, (countByCategory.get(article.category_id) ?? 0) + 1);
    }

    const rows = (categories ?? []).map((category) => ({
      ...category,
      articleCount: countByCategory.get(category.id) ?? 0,
    }));

    return apiSuccess(request, rows);
  } catch (error) {
    console.error("Error fetching KB categories:", error);
    return apiInternalError(request, "Failed to fetch categories");
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
    const validated = kbCategorySchema.parse(body);
    const slug = validated.slug?.trim() || slugify(validated.name);
    const supabase = await createClient();

    const { data, error } = await supabase
      .from("knowledge_base_categories")
      .insert({
        name: validated.name,
        slug,
        description: validated.description || null,
        parent_id: validated.parentId || null,
        icon: validated.icon || "book",
        position: validated.position ?? 0,
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
    console.error("Error creating KB category:", error);
    return apiInternalError(request, "Failed to create category");
  }
}
