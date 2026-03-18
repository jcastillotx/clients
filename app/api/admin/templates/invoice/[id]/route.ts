import { createClient } from "@/lib/supabase/server";
import { NextResponse } from "next/server";
import { extractVariables } from "@/lib/templates/template-engine";
import { requirePermission } from "@/lib/auth/route-guards";

export async function GET(
  request: Request,
  { params }: { params: Promise<{ id: string }> },
) {
  const { id } = await params;
  try {
    const guard = await requirePermission("settings.manage");
    if ("error" in guard) {
      return guard.error;
    }

    const supabase = await createClient();

    const { data: template, error } = await supabase
      .from("invoice_templates")
      .select("*")
      .eq("id", id)
      .is("deleted_at", null)
      .single();

    if (error) throw error;

    if (!template) {
      return NextResponse.json(
        { error: "Template not found" },
        { status: 404 },
      );
    }

    return NextResponse.json({ template });
  } catch (error) {
    console.error("Error fetching invoice template:", error);
    return NextResponse.json(
      {
        error:
          error instanceof Error ? error.message : "Failed to fetch template",
      },
      { status: 500 },
    );
  }
}

export async function PATCH(
  request: Request,
  { params }: { params: Promise<{ id: string }> },
) {
  const { id } = await params;
  try {
    const guard = await requirePermission("settings.manage");
    if ("error" in guard) {
      return guard.error;
    }

    const body = await request.json();
    const { name, description, htmlContent, cssContent, isDefault, isActive } =
      body;

    const supabase = await createClient();

    // If setting as default, unset other defaults first
    if (isDefault) {
      await supabase
        .from("invoice_templates")
        .update({ is_default: false })
        .eq("is_default", true)
        .neq("id", id);
    }

    // Extract available variables from the template
    const availableVariables = htmlContent
      ? extractVariables(htmlContent)
      : undefined;

    const updateData: {
      updated_at: string;
      [key: string]: unknown;
    } = {
      updated_at: new Date().toISOString(),
    };

    if (name !== undefined) updateData.name = name;
    if (description !== undefined) updateData.description = description;
    if (htmlContent !== undefined) {
      updateData.html_content = htmlContent;
      updateData.available_variables = availableVariables;
    }
    if (cssContent !== undefined) updateData.css_content = cssContent;
    if (isDefault !== undefined) updateData.is_default = isDefault;
    if (isActive !== undefined) updateData.is_active = isActive;

    const { data: template, error } = await supabase
      .from("invoice_templates")
      .update(updateData)
      .eq("id", id)
      .is("deleted_at", null)
      .select()
      .single();

    if (error) throw error;

    if (!template) {
      return NextResponse.json(
        { error: "Template not found" },
        { status: 404 },
      );
    }

    return NextResponse.json({ template });
  } catch (error) {
    console.error("Error updating invoice template:", error);
    return NextResponse.json(
      {
        error:
          error instanceof Error ? error.message : "Failed to update template",
      },
      { status: 500 },
    );
  }
}

export async function DELETE(
  request: Request,
  { params }: { params: Promise<{ id: string }> },
) {
  const { id } = await params;
  try {
    const guard = await requirePermission("settings.manage");
    if ("error" in guard) {
      return guard.error;
    }

    const supabase = await createClient();

    // Soft delete
    const { error } = await supabase
      .from("invoice_templates")
      .update({ deleted_at: new Date().toISOString() })
      .eq("id", id)
      .is("deleted_at", null);

    if (error) throw error;

    return NextResponse.json({ success: true });
  } catch (error) {
    console.error("Error deleting invoice template:", error);
    return NextResponse.json(
      {
        error:
          error instanceof Error ? error.message : "Failed to delete template",
      },
      { status: 500 },
    );
  }
}
