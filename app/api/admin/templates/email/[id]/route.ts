import { createClient } from "@/lib/supabase/server";
import { extractVariables } from "@/lib/templates/template-engine";
import { EMAIL_TEMPLATE_TYPES } from "@/lib/email/catalog";
import { requirePermission } from "@/lib/auth/route-guards";
import { z } from "zod";
import {
  apiInternalError,
  apiNotFound,
  apiValidationError,
  apiSuccess,
} from "@/lib/api/response";

const updateSchema = z.object({
  name: z.string().trim().min(1).max(120).optional(),
  description: z.string().max(500).nullable().optional(),
  type: z.string().refine((value) => EMAIL_TEMPLATE_TYPES.includes(value), "Unsupported email template type").optional(),
  subject: z.string().trim().min(1).max(300).optional(),
  htmlContent: z.string().min(1).max(200_000).optional(),
  textContent: z.string().max(100_000).nullable().optional(),
  isDefault: z.boolean().optional(),
  isActive: z.boolean().optional(),
});

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

    const parsed = updateSchema.safeParse(await request.json());
    if (!parsed.success) return apiValidationError(request, parsed.error);
    const { name, description, type, subject, htmlContent, textContent, isDefault, isActive } = parsed.data;

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
