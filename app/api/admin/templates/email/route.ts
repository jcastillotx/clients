import { createClient } from "@/lib/supabase/server";
import { extractVariables } from "@/lib/templates/template-engine";
import { EMAIL_TEMPLATE_TYPES } from "@/lib/email/catalog";
import { requirePermission } from "@/lib/auth/route-guards";
import { z } from "zod";
import {
  apiInternalError,
  apiValidationError,
  apiSuccess,
} from "@/lib/api/response";

const templateSchema = z.object({
  name: z.string().trim().min(1).max(120),
  description: z.string().max(500).nullable().optional(),
  type: z.string().refine((value) => EMAIL_TEMPLATE_TYPES.includes(value), "Unsupported email template type"),
  subject: z.string().trim().min(1).max(300),
  htmlContent: z.string().min(1).max(200_000),
  textContent: z.string().max(100_000).nullable().optional(),
  isDefault: z.boolean().optional().default(false),
  isActive: z.boolean().optional().default(true),
});

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

    const parsed = templateSchema.safeParse(await request.json());
    if (!parsed.success) return apiValidationError(request, parsed.error);
    const { name, description, type, subject, htmlContent, textContent, isDefault, isActive } = parsed.data;

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
