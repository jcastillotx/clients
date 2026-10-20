import { NextRequest } from "next/server";
import { z } from "zod";
import { requirePermission } from "@/lib/auth/route-guards";
import { apiInternalError, apiSuccess, apiValidationError } from "@/lib/api/response";
import { EMAIL_TEMPLATE_TYPES, getEmailTemplateDefinition } from "@/lib/email/catalog";
import { getEmailBrandContext, wrapBrandedEmail } from "@/lib/email/branding";
import { renderTemplate } from "@/lib/templates/template-engine";

const previewSchema = z.object({
  type: z.string().refine((value) => EMAIL_TEMPLATE_TYPES.includes(value), "Unsupported email template type"),
  subject: z.string().max(300),
  htmlContent: z.string().max(200_000),
  textContent: z.string().max(100_000).nullable().optional(),
});

export async function POST(request: NextRequest) {
  try {
    const guard = await requirePermission("settings.manage", request);
    if ("error" in guard) return guard.error;
    const parsed = previewSchema.safeParse(await request.json());
    if (!parsed.success) return apiValidationError(request, parsed.error);
    const definition = getEmailTemplateDefinition(parsed.data.type);
    const brand = await getEmailBrandContext();
    const context = { ...brand, ...(definition?.sampleData ?? {}) };
    return apiSuccess(request, {
      subject: renderTemplate(parsed.data.subject, context),
      html: wrapBrandedEmail(renderTemplate(parsed.data.htmlContent, context), brand),
      text: parsed.data.textContent ? renderTemplate(parsed.data.textContent, context) : null,
    });
  } catch (error) {
    console.error("Error previewing email template:", error);
    return apiInternalError(request, error instanceof Error ? error.message : "Failed to preview template");
  }
}
