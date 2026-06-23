import { createClient } from "@/lib/supabase/server";
import { extractVariables } from "@/lib/templates/template-engine";
import { requirePermission } from "@/lib/auth/route-guards";
import {
  apiInternalError,
  apiNotFound,
  apiSuccess,
} from "@/lib/api/response";

export async function GET(
  request: Request,
  { params }: { params: Promise<{ id: string }> },
) {
  const { id } = await params;
  try {
    const guard = await requirePermission("settings.manage", request);
    if ("error" in guard) {
      return guard.error;
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
      return apiNotFound(request, "Template not found");
    }

    return apiSuccess(request, template, { extra: { template } });
  } catch (error) {
    console.error("Error fetching email template:", error);
    return apiInternalError(
      request,
      error instanceof Error ? error.message : "Failed to fetch template",
    );
  }
}

export async function PATCH(
  request: Request,
  { params }: { params: Promise<{ id: string }> },
) {
  const { id } = await params;
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

    const supabase = await createClient();

    const { data: currentTemplate } = await supabase
      .from("email_templates")
      .select("type")
      .eq("id", id)
      .single();

    if (isDefault && currentTemplate) {
      await supabase
        .from("email_templates")
        .update({ is_default: false })
        .eq("type", currentTemplate.type)
        .eq("is_default", true)
        .neq("id", id);
    }

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
      return apiNotFound(request, "Template not found");
    }

    return apiSuccess(request, template, { extra: { template } });
  } catch (error) {
    console.error("Error updating email template:", error);
    return apiInternalError(
      request,
      error instanceof Error ? error.message : "Failed to update template",
    );
  }
}

export async function DELETE(
  request: Request,
  { params }: { params: Promise<{ id: string }> },
) {
  const { id } = await params;
  try {
    const guard = await requirePermission("settings.manage", request);
    if ("error" in guard) {
      return guard.error;
    }

    const supabase = await createClient();

    const { error } = await supabase
      .from("email_templates")
      .update({ deleted_at: new Date().toISOString() })
      .eq("id", id)
      .is("deleted_at", null);

    if (error) throw error;

    return apiSuccess(request, { deleted: true }, { extra: { success: true } });
  } catch (error) {
    console.error("Error deleting email template:", error);
    return apiInternalError(
      request,
      error instanceof Error ? error.message : "Failed to delete template",
    );
  }
}
