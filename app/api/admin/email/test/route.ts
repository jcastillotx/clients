import { NextRequest, NextResponse } from "next/server";
import { createClient } from "@/lib/supabase/server";
import { createAdminClientIfAvailable } from "@/lib/supabase/server";
import { isUserAdmin } from "@/lib/rbac/check";
import { decrypt, encrypt } from "@/lib/encryption";
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
        if (cfg["oauth_provider"] && cfg["oauth_access_token"]) {
          await sendViaOAuth(adminClient, cfg, parsed.data.to);
        } else {
          await sendViaSmtp(cfg, parsed.data.to);
        }
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
  const nodemailer = await import("nodemailer");

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
// OAuth senders (Gmail API / Microsoft Graph)
// ---------------------------------------------------------------------------

type AdminClient = NonNullable<ReturnType<typeof createAdminClientIfAvailable>>;

async function sendViaOAuth(
  adminClient: AdminClient,
  cfg: Record<string, string>,
  to: string,
) {
  const oauthProvider = cfg["oauth_provider"];
  const accessToken = await ensureFreshAccessToken(adminClient, cfg);

  if (oauthProvider === "google") {
    const rawMessage = buildRfc822Message(cfg, to);
    const encoded = Buffer.from(rawMessage)
      .toString("base64")
      .replace(/\+/g, "-")
      .replace(/\//g, "_")
      .replace(/=+$/g, "");

    const res = await fetch(
      "https://gmail.googleapis.com/gmail/v1/users/me/messages/send",
      {
        method: "POST",
        headers: {
          Authorization: `Bearer ${accessToken}`,
          "Content-Type": "application/json",
        },
        body: JSON.stringify({ raw: encoded }),
      },
    );
    if (!res.ok) {
      const data = await res.json().catch(() => ({}));
      throw new Error(
        (data as { error?: { message?: string } }).error?.message ??
          `Gmail API error ${res.status}`,
      );
    }
    return;
  }

  if (oauthProvider === "microsoft") {
    const res = await fetch("https://graph.microsoft.com/v1.0/me/sendMail", {
      method: "POST",
      headers: {
        Authorization: `Bearer ${accessToken}`,
        "Content-Type": "application/json",
      },
      body: JSON.stringify({
        message: {
          subject: "Test email from KRE8IV",
          body: { contentType: "HTML", content: testEmailHtml() },
          toRecipients: [{ emailAddress: { address: to } }],
          from: cfg["oauth_account_email"]
            ? { emailAddress: { address: cfg["oauth_account_email"] } }
            : undefined,
        },
        saveToSentItems: false,
      }),
    });
    if (!res.ok) {
      const data = await res.json().catch(() => ({}));
      throw new Error(
        (data as { error?: { message?: string } }).error?.message ??
          `Microsoft Graph error ${res.status}`,
      );
    }
    return;
  }

  throw new Error(`Unsupported OAuth provider: ${oauthProvider}`);
}

async function ensureFreshAccessToken(
  adminClient: AdminClient,
  cfg: Record<string, string>,
): Promise<string> {
  const accessToken = cfg["oauth_access_token"];
  if (!accessToken) throw new Error("No OAuth access token available.");

  const expiryIso = cfg["oauth_token_expiry"];
  const expiresAt = expiryIso ? new Date(expiryIso).getTime() : 0;
  // 60s safety margin
  if (expiresAt && expiresAt - 60_000 > Date.now()) return accessToken;

  const refreshToken = cfg["oauth_refresh_token"];
  if (!refreshToken) return accessToken; // stale token, but nothing to refresh

  const oauthProvider = cfg["oauth_provider"];
  let refreshed: { access_token?: string; expires_in?: number } | null = null;

  if (oauthProvider === "google") {
    const clientId = process.env.GOOGLE_EMAIL_CLIENT_ID || process.env.GOOGLE_CLIENT_ID;
    const clientSecret =
      process.env.GOOGLE_EMAIL_CLIENT_SECRET || process.env.GOOGLE_CLIENT_SECRET;
    if (!clientId || !clientSecret) return accessToken;

    const res = await fetch("https://oauth2.googleapis.com/token", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: new URLSearchParams({
        client_id: clientId,
        client_secret: clientSecret,
        grant_type: "refresh_token",
        refresh_token: refreshToken,
      }),
    });
    refreshed = await res.json().catch(() => null);
  } else if (oauthProvider === "microsoft") {
    const clientId =
      process.env.MICROSOFT_EMAIL_CLIENT_ID || process.env.MICROSOFT_CLIENT_ID;
    const clientSecret =
      process.env.MICROSOFT_EMAIL_CLIENT_SECRET ||
      process.env.MICROSOFT_CLIENT_SECRET;
    if (!clientId || !clientSecret) return accessToken;

    const res = await fetch(
      "https://login.microsoftonline.com/common/oauth2/v2.0/token",
      {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams({
          client_id: clientId,
          client_secret: clientSecret,
          grant_type: "refresh_token",
          refresh_token: refreshToken,
        }),
      },
    );
    refreshed = await res.json().catch(() => null);
  }

  if (!refreshed?.access_token) return accessToken;

  const newExpiryIso = refreshed.expires_in
    ? new Date(Date.now() + refreshed.expires_in * 1000).toISOString()
    : "";

  await adminClient.from("system_settings").upsert(
    [
      {
        category: "email",
        key: "oauth_access_token",
        value: encrypt(refreshed.access_token),
        is_encrypted: true,
        updated_at: new Date().toISOString(),
      },
      ...(newExpiryIso
        ? [
            {
              category: "email",
              key: "oauth_token_expiry",
              value: newExpiryIso,
              is_encrypted: false,
              updated_at: new Date().toISOString(),
            },
          ]
        : []),
    ],
    { onConflict: "category,key" },
  );

  return refreshed.access_token;
}

function buildRfc822Message(cfg: Record<string, string>, to: string): string {
  const from =
    cfg["oauth_account_email"] || cfg["from_email"] || "noreply@example.com";
  const fromName = cfg["from_name"] || "KRE8IV";
  const html = testEmailHtml();
  return [
    `From: "${fromName}" <${from}>`,
    `To: ${to}`,
    `Subject: Test email from KRE8IV`,
    `MIME-Version: 1.0`,
    `Content-Type: text/html; charset=UTF-8`,
    ``,
    html,
  ].join("\r\n");
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
