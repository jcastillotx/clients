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

    return apiSuccess(request, templates ?? [], { extra: { templates: templates ?? [] } });
  } catch (error) {
    console.error("Error fetching email templates:", error);
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
    const {
      name,
      description,
      type,
      subject,
      htmlContent,
      textContent,
      isDefault,
      isActive,
    } = body;

    const availableVariables = extractVariables(htmlContent);

    const supabase = await createClient();

    if (isDefault) {
      await supabase
        .from("email_templates")
        .update({ is_default: false })
        .eq("type", type)
        .eq("is_default", true);
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

    return apiSuccess(request, template, {
      status: 201,
      extra: { template },
    });
  } catch (error) {
    console.error("Error creating email template:", error);
    return apiInternalError(
      request,
      error instanceof Error ? error.message : "Failed to create template",
    );
  }
}
