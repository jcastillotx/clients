import { createClient } from "@/lib/supabase/server";
import { NextResponse } from "next/server";
import { hasPermission } from "@/lib/rbac/permissions";
import { extractVariables } from "@/lib/templates/template-engine";

export async function GET(request: Request) {
  try {
    const canView = await hasPermission("settings.manage");
    if (!canView) {
      return NextResponse.json({ error: "Permission denied" }, { status: 403 });
    }

    const { searchParams } = new URL(request.url);
    const type = searchParams.get("type");

    const supabase = await createClient();

    let query = supabase
      .from("email_templates")
      .select("*")
      .is("deleted_at", null)
      .order("created_at", { ascending: false });

    if (type) {
      query = query.eq("type", type);
    }

    const { data: templates, error } = await query;

    if (error) throw error;

    return NextResponse.json({ templates });
  } catch (error) {
    console.error("Error fetching email templates:", error);
    return NextResponse.json(
      { error: error instanceof Error ? error.message : "Failed to fetch templates" },
      { status: 500 },
    );
  }
}

export async function POST(request: Request) {
  try {
    const canManage = await hasPermission("settings.manage");
    if (!canManage) {
      return NextResponse.json({ error: "Permission denied" }, { status: 403 });
    }

    const body = await request.json();
    const { name, description, type, subject, htmlContent, textContent, isDefault, isActive } = body;

    // Extract available variables from the template
    const availableVariables = extractVariables(htmlContent);

    const supabase = await createClient();

    // If setting as default for this type, unset other defaults
    if (isDefault) {
      await supabase.from("email_templates").update({ is_default: false }).eq("type", type).eq("is_default", true);
    }

    const { data: template, error } = await supabase
      .from("email_templates")
      .insert({
        name,
        description,
        type,
        subject,
        html_content: htmlContent,
        text_content: textContent,
        available_variables: availableVariables,
        is_default: isDefault || false,
        is_active: isActive !== false,
      })
      .select()
      .single();

    if (error) throw error;

    return NextResponse.json({ template }, { status: 201 });
  } catch (error) {
    console.error("Error creating email template:", error);
    return NextResponse.json(
      { error: error instanceof Error ? error.message : "Failed to create template" },
      { status: 500 },
    );
  }
}
