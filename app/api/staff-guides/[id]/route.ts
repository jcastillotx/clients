import { NextRequest } from "next/server";
import { z } from "zod";
import { createClient } from "@/lib/supabase/server";
import { resolveStaffAccess } from "@/lib/api/resolve-staff-access";
import { staffGuideSchema } from "@/lib/validations/partners-kb";
import {
  apiError,
  apiForbidden,
  apiInternalError,
  apiNotFound,
  apiSuccess,
  apiUnauthorized,
  apiValidationError,
} from "@/lib/api/response";
import { slugify } from "@/lib/api/slug";

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
    const { data, error } = await supabase
      .from("staff_guides")
      .select(
        `
        *,
        category:staff_guide_categories(id, name, slug)
      `,
      )
      .eq("id", id)
      .maybeSingle();

    if (error) {
      return apiInternalError(request, error.message);
    }
    if (!data) {
      return apiNotFound(request, "Guide not found");
    }

    const category = Array.isArray(data.category) ? data.category[0] : data.category;

    return apiSuccess(request, {
      id: data.id,
      title: data.title,
      slug: data.slug,
      summary: data.summary,
      content: data.content,
      categoryId: data.category_id,
      categoryName: category?.name ?? "Uncategorized",
      serviceTier: data.service_tier,
      price: data.price,
      commitment: data.commitment,
      viewCount: data.view_count,
      isInternal: data.is_internal,
      isPublished: data.is_published,
      createdAt: data.created_at,
      updatedAt: data.updated_at,
    });
  } catch (error) {
    console.error("Error fetching staff guide:", error);
    return apiInternalError(request, "Failed to fetch guide");
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
    const { error } = await supabase.from("staff_guides").delete().eq("id", id);

    if (error) {
      return apiInternalError(request, error.message);
    }

    return apiSuccess(request, { id });
  } catch (error) {
    console.error("Error deleting staff guide:", error);
    return apiInternalError(request, "Failed to delete guide");
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
    const validated = staffGuideSchema.partial().parse(body);
    const supabase = await createClient();

    const patch: Record<string, unknown> = {};
    if (validated.categoryId !== undefined) patch.category_id = validated.categoryId;
    if (validated.title !== undefined) patch.title = validated.title;
    if (validated.slug !== undefined) patch.slug = validated.slug;
    if (validated.title !== undefined && validated.slug === undefined) {
      patch.slug = slugify(validated.title);
    }
    if (validated.summary !== undefined) patch.summary = validated.summary;
    if (validated.content !== undefined) patch.content = validated.content;
    if (validated.serviceTier !== undefined) patch.service_tier = validated.serviceTier;
    if (validated.price !== undefined) patch.price = validated.price;
    if (validated.commitment !== undefined) patch.commitment = validated.commitment;
    if (validated.isInternal !== undefined) patch.is_internal = validated.isInternal;
    if (validated.isPublished !== undefined) {
      patch.is_published = validated.isPublished;
      if (validated.isPublished) {
        patch.published_at = new Date().toISOString();
      }
    }

    if (Object.keys(patch).length === 0) {
      return apiError(request, {
        status: 400,
        code: "BAD_REQUEST",
        message: "No fields to update",
      });
    }

    const { data, error } = await supabase
      .from("staff_guides")
      .update(patch)
      .eq("id", id)
      .select("*")
      .maybeSingle();

    if (error) {
      return apiInternalError(request, error.message);
    }
    if (!data) {
      return apiNotFound(request, "Guide not found");
    }

    return apiSuccess(request, data);
  } catch (error) {
    if (error instanceof z.ZodError) {
      return apiValidationError(request, error);
    }
    console.error("Error updating staff guide:", error);
    return apiInternalError(request, "Failed to update guide");
  }
}
