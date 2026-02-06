import { createClient } from "@/lib/supabase/server";
import { NextResponse } from "next/server";
import { hasPermission } from "@/lib/rbac/permissions";
import { extractVariables } from "@/lib/templates/template-engine";

export async function GET(request: Request, { params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;
  try {
    const canView = await hasPermission("settings.manage");
    if (!canView) {
      return NextResponse.json({ error: "Permission denied" }, { status: 403 });
    }

    const supabase = await createClient();

    const { data: template, error } = await supabase
      .from("email_templates")
      .select("*")
      .eq("id", id)
      .is("deleted_at", null)
      .single();

    if (error) throw error;

    if (!template) {
      return NextResponse.json({ error: "Template not found" }, { status: 404 });
    }

    return NextResponse.json({ template });
  } catch (error) {
    console.error("Error fetching email template:", error);
    return NextResponse.json(
      { error: error instanceof Error ? error.message : "Failed to fetch template" },
      { status: 500 },
    );
  }
}

export async function PATCH(request: Request, { params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;
  try {
    const canManage = await hasPermission("settings.manage");
    if (!canManage) {
      return NextResponse.json({ error: "Permission denied" }, { status: 403 });
    }

    const body = await request.json();
    const { name, description, type, subject, htmlContent, textContent, isDefault, isActive } = body;

    const supabase = await createClient();

    // Get current template to check type
    const { data: currentTemplate } = await supabase
      .from("email_templates")
      .select("type")
      .eq("id", id)
      .single();

    // If setting as default for this type, unset other defaults
    if (isDefault && currentTemplate) {
      await supabase
        .from("email_templates")
        .update({ is_default: false })
        .eq("type", currentTemplate.type)
        .eq("is_default", true)
        .neq("id", id);
    }

    // Extract available variables from the template
    const availableVariables = htmlContent ? extractVariables(htmlContent) : undefined;

    const updateData: any = {
      updated_at: new Date().toISOString(),
    };

    if (name !== undefined) updateData.name = name;
    if (description !== undefined) updateData.description = description;
    if (type !== undefined) updateData.type = type;
    if (subject !== undefined) updateData.subject = subject;
    if (htmlContent !== undefined) {
      updateData.html_content = htmlContent;
      updateData.available_variables = availableVariables;
    }
    if (textContent !== undefined) updateData.text_content = textContent;
    if (isDefault !== undefined) updateData.is_default = isDefault;
    if (isActive !== undefined) updateData.is_active = isActive;

    const { data: template, error } = await supabase
      .from("email_templates")
      .update(updateData)
      .eq("id", id)
      .is("deleted_at", null)
      .select()
      .single();

    if (error) throw error;

    if (!template) {
      return NextResponse.json({ error: "Template not found" }, { status: 404 });
    }

    return NextResponse.json({ template });
  } catch (error) {
    console.error("Error updating email template:", error);
    return NextResponse.json(
      { error: error instanceof Error ? error.message : "Failed to update template" },
      { status: 500 },
    );
  }
}

export async function DELETE(request: Request, { params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;
  try {
    const canManage = await hasPermission("settings.manage");
    if (!canManage) {
      return NextResponse.json({ error: "Permission denied" }, { status: 403 });
    }

    const supabase = await createClient();

    // Soft delete
    const { error } = await supabase
      .from("email_templates")
      .update({ deleted_at: new Date().toISOString() })
      .eq("id", id)
      .is("deleted_at", null);

    if (error) throw error;

    return NextResponse.json({ success: true });
  } catch (error) {
    console.error("Error deleting email template:", error);
    return NextResponse.json(
      { error: error instanceof Error ? error.message : "Failed to delete template" },
      { status: 500 },
    );
  }
}
