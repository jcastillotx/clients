import { NextRequest, NextResponse } from "next/server";
import { createClient } from "@/lib/supabase/server";
import { createAdminClientIfAvailable } from "@/lib/supabase/admin";
import { isUserAdmin } from "@/lib/rbac/check";
import { decrypt } from "@/lib/encryption";
import { z } from "zod";

const testSchema = z.object({
  to: z.string().email(),
});

interface SettingRow {
  key: string;
  value: string;
  is_encrypted: boolean;
}

/**
 * POST /api/admin/email/test
 * Sends a test email using the currently saved provider settings.
 */
export async function POST(req: NextRequest) {
  const supabase = await createClient();
  const { data: { user } } = await supabase.auth.getUser();
  if (!user) return NextResponse.json({ error: "Unauthorized" }, { status: 401 });
  if (!(await isUserAdmin(user.id))) return NextResponse.json({ error: "Forbidden" }, { status: 403 });

  const body = await req.json();
  const parsed = testSchema.safeParse(body);
  if (!parsed.success) {
    return NextResponse.json({ error: "Provide a valid 'to' email address" }, { status: 400 });
  }

  const adminClient = createAdminClientIfAvailable();
  if (!adminClient) return NextResponse.json({ error: "Admin client not configured" }, { status: 503 });

  // Load all email settings
  const { data: rows } = await adminClient
    .from("system_settings")
    .select("key, value, is_encrypted")
    .eq("category", "email");

  const cfg: Record<string, string> = {};
  for (const row of (rows ?? []) as SettingRow[]) {
    try {
      cfg[row.key] = row.is_encrypted ? decrypt(row.value) : row.value;
    } catch {
      // ignore decrypt failures
    }
  }

  const provider = cfg["provider"] ?? "resend";

  try {
    switch (provider) {
      case "resend":
        await sendViaResend(cfg, parsed.data.to);
        break;
      case "sendgrid":
        await sendViaSendGrid(cfg, parsed.data.to);
        break;
      case "mailgun":
        await sendViaMailgun(cfg, parsed.data.to);
        break;
      case "gmail":
      case "office365":
      case "smtp":
        await sendViaSmtp(cfg, parsed.data.to);
        break;
      default:
        return NextResponse.json({ error: `Unknown provider: ${provider}` }, { status: 400 });
    }

    return NextResponse.json({ success: true, provider, to: parsed.data.to });
  } catch (err) {
    console.error("[admin/email/test] Send failed:", err);
    return NextResponse.json(
      { error: err instanceof Error ? err.message : "Failed to send test email" },
      { status: 500 },
    );
  }
}

// ---------------------------------------------------------------------------
// Provider senders
// ---------------------------------------------------------------------------

async function sendViaResend(cfg: Record<string, string>, to: string) {
  const apiKey = cfg["api_key"];
  if (!apiKey) throw new Error("Resend API key is not configured.");

  const res = await fetch("https://api.resend.com/emails", {
    method: "POST",
    headers: {
      Authorization: `Bearer ${apiKey}`,
      "Content-Type": "application/json",
    },
    body: JSON.stringify({
      from: buildFrom(cfg),
      to: [to],
      subject: "Test email from KRE8IV",
      html: testEmailHtml(),
    }),
  });

  if (!res.ok) {
    const data = await res.json().catch(() => ({}));
    throw new Error((data as { message?: string }).message ?? `Resend API error ${res.status}`);
  }
}

async function sendViaSendGrid(cfg: Record<string, string>, to: string) {
  const apiKey = cfg["api_key"];
  if (!apiKey) throw new Error("SendGrid API key is not configured.");

  const res = await fetch("https://api.sendgrid.com/v3/mail/send", {
    method: "POST",
    headers: {
      Authorization: `Bearer ${apiKey}`,
      "Content-Type": "application/json",
    },
    body: JSON.stringify({
      personalizations: [{ to: [{ email: to }] }],
      from: { email: cfg["from_email"] ?? "noreply@example.com", name: cfg["from_name"] ?? "KRE8IV" },
      subject: "Test email from KRE8IV",
      content: [{ type: "text/html", value: testEmailHtml() }],
    }),
  });

  if (!res.ok) {
    throw new Error(`SendGrid API error ${res.status}`);
  }
}

async function sendViaMailgun(cfg: Record<string, string>, to: string) {
  const apiKey = cfg["api_key"];
  const domain = cfg["mailgun_domain"];
  if (!apiKey || !domain) throw new Error("Mailgun API key and domain are required.");

  const form = new URLSearchParams({
    from: buildFrom(cfg),
    to,
    subject: "Test email from KRE8IV",
    html: testEmailHtml(),
  });

  const res = await fetch(`https://api.mailgun.net/v3/${domain}/messages`, {
    method: "POST",
    headers: {
      Authorization: `Basic ${Buffer.from(`api:${apiKey}`).toString("base64")}`,
      "Content-Type": "application/x-www-form-urlencoded",
    },
    body: form,
  });

  if (!res.ok) {
    const data = await res.json().catch(() => ({}));
    throw new Error((data as { message?: string }).message ?? `Mailgun API error ${res.status}`);
  }
}

async function sendViaSmtp(cfg: Record<string, string>, to: string) {
  // Dynamically import nodemailer — only used server-side for SMTP
  let nodemailer: typeof import("nodemailer");
  try {
    nodemailer = await import("nodemailer");
  } catch {
    throw new Error(
      "nodemailer is not installed. Run: pnpm add nodemailer @types/nodemailer",
    );
  }

  const host = cfg["smtp_host"];
  const port = parseInt(cfg["smtp_port"] ?? "587", 10);
  const encryption = cfg["smtp_encryption"] ?? "starttls";

  if (!host) throw new Error("SMTP host is not configured.");

  const transporter = nodemailer.createTransport({
    host,
    port,
    secure: encryption === "ssl",
    requireTLS: encryption === "starttls",
    auth: cfg["smtp_user"]
      ? { user: cfg["smtp_user"], pass: cfg["smtp_password"] ?? "" }
      : undefined,
    tls: { rejectUnauthorized: true },
  });

  await transporter.sendMail({
    from: buildFrom(cfg),
    to,
    subject: "Test email from KRE8IV",
    html: testEmailHtml(),
  });
}

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function buildFrom(cfg: Record<string, string>): string {
  const email = cfg["from_email"] ?? "noreply@example.com";
  const name = cfg["from_name"] ?? "KRE8IV";
  return `"${name}" <${email}>`;
}

function testEmailHtml(): string {
  return `
    <div style="font-family:sans-serif;max-width:480px;margin:0 auto;padding:32px 24px;">
      <h2 style="margin:0 0 8px;font-size:20px;color:#111;">Test email</h2>
      <p style="margin:0 0 16px;color:#555;font-size:15px;">
        Your email provider is configured correctly. This message was sent from
        <strong>KRE8IV</strong>.
      </p>
      <hr style="border:none;border-top:1px solid #e5e7eb;margin:24px 0;">
      <p style="margin:0;color:#9ca3af;font-size:12px;">
        Sent via the KRE8IV admin email settings test.
      </p>
    </div>
  `.trim();
}
