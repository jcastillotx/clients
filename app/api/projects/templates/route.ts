import { NextRequest, NextResponse } from "next/server";
import { db } from "@/lib/db";
import { projectTaskTemplates } from "@/lib/db/schema/project-templates";
import { eq, isNull, desc, and } from "drizzle-orm";
import { createClient } from "@/lib/supabase/server";
import { builtInProjectTemplates } from "@/lib/templates/project-templates";

/**
 * GET /api/projects/templates
 * List all project task templates (DB + built-in)
 */
export async function GET(request: NextRequest) {
  try {
    const supabase = await createClient();
    const {
      data: { user },
    } = await supabase.auth.getUser();
    if (!user) {
      return NextResponse.json({ success: false, error: "Unauthorized" }, { status: 401 });
    }

    const category = request.nextUrl.searchParams.get("category");

    // Fetch custom templates from DB
    const conditions = [isNull(projectTaskTemplates.deletedAt), eq(projectTaskTemplates.isActive, true)];

    if (category) {
      conditions.push(eq(projectTaskTemplates.category, category as any));
    }

    const dbTemplates = await db
      .select()
      .from(projectTaskTemplates)
      .where(and(...conditions))
      .orderBy(desc(projectTaskTemplates.createdAt));

    // Merge with built-in templates (that aren't already in DB)
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

    return NextResponse.json({
      success: true,
      data: [...builtInNotInDb, ...dbTemplates],
    });
  } catch (error) {
    console.error("Error fetching project templates:", error);
    return NextResponse.json({ success: false, error: "Failed to fetch templates" }, { status: 500 });
  }
}

/**
 * POST /api/projects/templates
 * Create a new project task template
 */
export async function POST(request: NextRequest) {
  try {
    const supabase = await createClient();
    const {
      data: { user },
    } = await supabase.auth.getUser();
    if (!user) {
      return NextResponse.json({ success: false, error: "Unauthorized" }, { status: 401 });
    }

    const body = await request.json();
    const { name, description, category, icon, color, estimatedHours, phases, metadata } = body;

    if (!name || !phases || !Array.isArray(phases)) {
      return NextResponse.json({ success: false, error: "Name and phases are required" }, { status: 400 });
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
        createdBy: user.id,
      })
      .returning();

    return NextResponse.json({ success: true, data: template });
  } catch (error) {
    console.error("Error creating project template:", error);
    return NextResponse.json({ success: false, error: "Failed to create template" }, { status: 500 });
  }
}
