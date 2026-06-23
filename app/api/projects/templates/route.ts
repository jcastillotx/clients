import { NextRequest } from "next/server";
import { db, isDatabaseConfigurationError } from "@/lib/db";
import { projectTaskTemplates } from "@/lib/db/schema/project-templates";
import { eq, isNull, desc, and } from "drizzle-orm";
import { requireAuthenticatedUser } from "@/lib/auth/route-guards";
import { builtInProjectTemplates } from "@/lib/templates/project-templates";
import {
  apiError,
  apiInternalError,
  apiSuccess,
} from "@/lib/api/response";

/**
 * GET /api/projects/templates
 */
export async function GET(request: NextRequest) {
  try {
    const guard = await requireAuthenticatedUser(request);
    if ("error" in guard) {
      return guard.error;
    }

    const category = request.nextUrl.searchParams.get("category");

    let dbTemplates: (typeof projectTaskTemplates.$inferSelect)[] = [];
    try {
      const conditions = [isNull(projectTaskTemplates.deletedAt), eq(projectTaskTemplates.isActive, true)];

      if (category) {
        conditions.push(eq(projectTaskTemplates.category, category as (typeof projectTaskTemplates.category.enumValues)[number]));
      }

      dbTemplates = await db
        .select()
        .from(projectTaskTemplates)
        .where(and(...conditions))
        .orderBy(desc(projectTaskTemplates.createdAt));
    } catch (dbError) {
      console.error("Error fetching custom project templates from DB (built-in templates still available):", dbError);
    }

    const dbNames = new Set(dbTemplates.map((t) => t.name));
    const builtInNotInDb = builtInProjectTemplates
      .filter((t) => !dbNames.has(t.name))
      .filter((t) => !category || t.category === category)
      .map((t) => ({
        id: `builtin-${t.name.toLowerCase().replace(/\s+/g, "-")}`,
        ...t,
        isActive: true,
        createdBy: null,
        createdAt: new Date(),
        updatedAt: new Date(),
        deletedAt: null,
      }));

    const templates = [...builtInNotInDb, ...dbTemplates];
    return apiSuccess(request, templates);
  } catch (error) {
    console.error("Error fetching project templates:", error);
    if (isDatabaseConfigurationError(error)) {
      return apiError(request, {
        status: 503,
        code: "SERVICE_UNAVAILABLE",
        message: "Failed to fetch templates",
      });
    }
    return apiInternalError(
      request,
      error instanceof Error ? error.message : "Failed to fetch templates",
    );
  }
}

/**
 * POST /api/projects/templates
 */
export async function POST(request: NextRequest) {
  try {
    const guard = await requireAuthenticatedUser(request);
    if ("error" in guard) {
      return guard.error;
    }

    const body = await request.json();
    const { name, description, category, icon, color, estimatedHours, phases, metadata } = body;

    if (!name || !phases || !Array.isArray(phases)) {
      return apiError(request, {
        status: 400,
        code: "BAD_REQUEST",
        message: "Name and phases are required",
      });
    }

    const [template] = await db
      .insert(projectTaskTemplates)
      .values({
        name,
        description,
        category: category || "general",
        icon,
        color,
        estimatedHours,
        phases,
        metadata,
        isSystem: false,
        createdBy: guard.user.id,
      })
      .returning();

    return apiSuccess(request, template, { status: 201 });
  } catch (error) {
    console.error("Error creating project template:", error);
    return apiInternalError(request, "Failed to create template");
  }
}
