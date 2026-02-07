import { Resend } from "resend";

let _resend: Resend | null = null;

function getResend(): Resend {
  if (!_resend) {
    if (!process.env.RESEND_API_KEY) {
      throw new Error("RESEND_API_KEY is not set in environment variables");
    }
    _resend = new Resend(process.env.RESEND_API_KEY);
  }
  return _resend;
}

export const resend = new Proxy({} as Resend, {
  get(_target, prop) {
    return (getResend() as any)[prop];
  },
});

// Email sender configuration
export const EMAIL_FROM = process.env.EMAIL_FROM || "noreply@yourdomain.com";
export const EMAIL_REPLY_TO = process.env.EMAIL_REPLY_TO || "support@yourdomain.com";

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

// Send email with error handling
export async function sendEmail(options: EmailOptions) {
  try {
    const result = await resend.emails.send({
      from: EMAIL_FROM,
      to: Array.isArray(options.to) ? options.to : [options.to],
      subject: options.subject,
      html: options.html || "",
      text: options.text || "",
      cc: options.cc,
      bcc: options.bcc,
      reply_to: options.replyTo || EMAIL_REPLY_TO,
      attachments: options.attachments,
    });

    return { success: true, data: result };
  } catch (error) {
    console.error("Email send error:", error);
    return {
      success: false,
      error: error instanceof Error ? error.message : "Failed to send email",
    };
  }
}
