import {
  loadEmailConfig,
  sendViaConfiguredProvider,
  sendViaResend,
} from "./providers";

// Email sender configuration (Resend: prefer RESEND_FROM_EMAIL + RESEND_FROM_NAME)
function resolveFromAddress(): string {
  const email =
    process.env.EMAIL_FROM?.trim() ||
    process.env.RESEND_FROM_EMAIL?.trim() ||
    "noreply@yourdomain.com";
  const name =
    process.env.RESEND_FROM_NAME?.trim() || process.env.EMAIL_FROM_NAME?.trim();
  if (name) {
    return `${name} <${email}>`;
  }
  return email;
}

export const EMAIL_FROM = resolveFromAddress();
export const EMAIL_REPLY_TO =
  process.env.EMAIL_REPLY_TO?.trim() ||
  process.env.RESEND_REPLY_TO?.trim() ||
  "support@yourdomain.com";

// Common email types
export interface EmailOptions {
  to: string | string[];
  subject: string;
  html?: string;
  text?: string;
  cc?: string[];
  bcc?: string[];
  replyTo?: string;
  attachments?: Array<{
    filename: string;
    content: Buffer | string;
  }>;
}

// Send email with error handling.
// Uses the admin-configured provider from system_settings when available;
// falls back to the env-var Resend instance otherwise.
export async function sendEmail(options: EmailOptions) {
  try {
    const cfg = await loadEmailConfig();
    if (cfg) {
      await sendViaConfiguredProvider(options, cfg);
      return { success: true };
    }

    // Fallback: env-var Resend (original behaviour when no DB config is set)
    await sendViaResend(
      {
        provider: "resend",
        api_key: process.env.RESEND_API_KEY,
        from_email: EMAIL_FROM,
      },
      { ...options, replyTo: options.replyTo || EMAIL_REPLY_TO },
    );
    return { success: true };
  } catch (error) {
    console.error("Email send error:", error);
    return {
      success: false,
      error: error instanceof Error ? error.message : "Failed to send email",
    };
  }
}
