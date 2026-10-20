import { NextRequest } from "next/server";
import { createClient } from "@/lib/supabase/server";
import { isUserAdmin } from "@/lib/rbac/check";
import { loadEmailConfig, sendViaConfiguredProvider } from "@/lib/email/providers";
import { getEmailBrandContext, wrapBrandedEmail } from "@/lib/email/branding";
import { z } from "zod";
import {
  apiError,
  apiForbidden,
  apiInternalError,
  apiSuccess,
  apiUnauthorized,
  apiValidationError,
} from "@/lib/api/response";

const testSchema = z.object({
  to: z.string().email(),
});

/**
 * POST /api/admin/email/test
 * Sends a test email using the currently saved provider settings.
 */
export async function POST(req: NextRequest) {
  const supabase = await createClient();
  const {
    data: { user },
  } = await supabase.auth.getUser();

  if (!user) {
    return apiUnauthorized(req);
  }

  if (!(await isUserAdmin(user.id))) {
    return apiForbidden(req);
  }

  const body = await req.json();
  const parsed = testSchema.safeParse(body);
  if (!parsed.success) {
    return apiValidationError(req, parsed.error);
  }

  const cfg = await loadEmailConfig();
  if (!cfg) {
    return apiError(req, {
      status: 503,
      code: "SERVICE_UNAVAILABLE",
      message: "Email provider is not configured in admin settings",
    });
  }

  const provider = cfg.provider ?? "resend";

  try {
    const brand = await getEmailBrandContext();
    await sendViaConfiguredProvider(
      {
        to: parsed.data.to,
        subject: `Test email from ${brand.brand_name}`,
        html: wrapBrandedEmail(testEmailHtml(), brand),
      },
      cfg,
    );

    return apiSuccess(
      req,
      { provider, to: parsed.data.to },
      { extra: { success: true, provider, to: parsed.data.to } },
    );
  } catch (err) {
    console.error("[admin/email/test] Send failed:", err);
    return apiInternalError(
      req,
      err instanceof Error ? err.message : "Failed to send test email",
    );
  }
}

function testEmailHtml(): string {
  return `
    <div style="font-family:sans-serif;">
      <h1 style="margin:0 0 12px;color:#111827;">Email provider test</h1>
      <p style="color:#374151;line-height:1.5;">
        This confirms the configured email provider can send messages from the client portal.
      </p>
      <p style="color:#6b7280;font-size:12px;margin-top:24px;">
        Sent at ${new Date().toISOString()}
      </p>
    </div>
  `;
}
