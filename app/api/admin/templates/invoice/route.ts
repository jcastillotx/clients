import { createClient } from "@/lib/supabase/server";
import { extractVariables } from "@/lib/templates/template-engine";
import { requirePermission } from "@/lib/auth/route-guards";
import {
  apiInternalError,
  apiSuccess,
} from "@/lib/api/response";

export async function GET(request: Request) {
  try {
    const guard = await requirePermission("settings.manage", request);
    if ("error" in guard) {
      return guard.error;
    }

    const supabase = await createClient();

    const { data: templates, error } = await supabase
      .from("invoice_templates")
      .select("*")
      .is("deleted_at", null)
      .order("created_at", { ascending: false });

    if (error) throw error;

    return apiSuccess(request, templates ?? [], { extra: { templates: templates ?? [] } });
  } catch (error) {
    console.error("Error fetching invoice templates:", error);
    return apiInternalError(
      request,
      error instanceof Error ? error.message : "Failed to fetch templates",
    );
  }
}

export async function POST(request: Request) {
  try {
    const guard = await requirePermission("settings.manage", request);
    if ("error" in guard) {
      return guard.error;
    }

    const body = await request.json();
    const { name, description, htmlContent, cssContent, isDefault, isActive } =
      body;

    const availableVariables = extractVariables(htmlContent);

    const supabase = await createClient();

    if (isDefault) {
      await supabase
        .from("invoice_templates")
        .update({ is_default: false })
        .eq("is_default", true);
    }

    const { data: template, error } = await supabase
      .from("invoice_templates")
      .insert({
        name,
        description,
        html_content: htmlContent,
        css_content: cssContent,
        available_variables: availableVariables,
        is_default: isDefault || false,
        is_active: isActive !== false,
      })
      .select()
      .single();

    if (error) throw error;

    return apiSuccess(request, template, {
      status: 201,
      extra: { template },
    });
  } catch (error) {
    console.error("Error creating invoice template:", error);
    return apiInternalError(
      request,
      error instanceof Error ? error.message : "Failed to create template",
    );
  }
}
