/**
 * Shared email provider implementations.
 * Used by sendEmail() in client.ts and the test route in /api/admin/email/test.
 */

import { createAdminClientIfAvailable } from "@/lib/supabase/server";
import { decrypt, encrypt } from "@/lib/encryption";
import { getMicrosoftEmailTokenUrl } from "@/lib/auth/microsoft-email-oauth";
import type { EmailOptions } from "./client";

// ---------------------------------------------------------------------------
// Config type & loader
// ---------------------------------------------------------------------------

export interface EmailConfig {
  provider: string;
  api_key?: string;
  from_email?: string;
  from_name?: string;
  smtp_host?: string;
  smtp_port?: string;
  smtp_encryption?: string;
  smtp_user?: string;
  smtp_password?: string;
  mailgun_domain?: string;
  oauth_provider?: string;
  oauth_account_email?: string;
  oauth_access_token?: string;
  oauth_refresh_token?: string;
  oauth_token_expiry?: string;
  [key: string]: string | undefined;
}

interface CacheEntry {
  config: EmailConfig | null;
  loadedAt: number;
}

const CACHE_TTL_MS = 5 * 60 * 1000; // 5 minutes
let _cache: CacheEntry | null = null;

interface SettingRow {
  key: string;
  value: string;
  is_encrypted: boolean;
}

/**
 * Load email provider config from system_settings.
 * Returns null when the admin DB client is unavailable (triggers Resend fallback).
 * Results are cached for 5 minutes to avoid a DB hit on every email.
 */
export async function loadEmailConfig(): Promise<EmailConfig | null> {
  const now = Date.now();
  if (_cache && now - _cache.loadedAt < CACHE_TTL_MS) {
    return _cache.config;
  }

  const adminClient = createAdminClientIfAvailable();
  if (!adminClient) {
    _cache = { config: null, loadedAt: now };
    return null;
  }

  try {
    const { data: rows } = await adminClient
      .from("system_settings")
      .select("key, value, is_encrypted")
      .eq("category", "email");

    if (!rows || rows.length === 0) {
      _cache = { config: null, loadedAt: now };
      return null;
    }

    const raw: Record<string, string> = {};
    for (const row of rows as SettingRow[]) {
      try {
        raw[row.key] = row.is_encrypted ? decrypt(row.value) : row.value;
      } catch {
        // ignore decrypt failures — treat as missing
      }
    }

    // If no provider is set, fall back to Resend via env vars
    if (!raw["provider"]) {
      _cache = { config: null, loadedAt: now };
      return null;
    }

    _cache = { config: raw as EmailConfig, loadedAt: now };
    return _cache.config;
  } catch {
    _cache = { config: null, loadedAt: now };
    return null;
  }
}

// ---------------------------------------------------------------------------
// Dispatcher
// ---------------------------------------------------------------------------

/**
 * Send an email using the admin-configured provider.
 */
export async function sendViaConfiguredProvider(
  options: EmailOptions,
  cfg: EmailConfig,
): Promise<void> {
  const provider = cfg.provider ?? "resend";

  switch (provider) {
    case "resend":
      await sendViaResend(cfg, options);
      break;
    case "sendgrid":
      await sendViaSendGrid(cfg, options);
      break;
    case "mailgun":
      await sendViaMailgun(cfg, options);
      break;
    case "gmail":
      if (cfg.oauth_provider === "google" && cfg.oauth_access_token) {
        await sendViaGmailApi(cfg, options);
        break;
      }
      await sendViaSMTP(cfg, options);
      break;
    case "office365":
      if (cfg.oauth_provider === "microsoft" && cfg.oauth_access_token) {
        await sendViaMicrosoftGraph(cfg, options);
        break;
      }
      await sendViaSMTP(cfg, options);
      break;
    case "smtp":
      await sendViaSMTP(cfg, options);
      break;
    default:
      throw new Error(`Unknown email provider: ${provider}`);
  }
}

// ---------------------------------------------------------------------------
// Provider implementations
// ---------------------------------------------------------------------------

export async function sendViaResend(cfg: EmailConfig, options: EmailOptions): Promise<void> {
  const apiKey = cfg.api_key;
  if (!apiKey) throw new Error("Resend API key is not configured.");

  const res = await fetch("https://api.resend.com/emails", {
    method: "POST",
    headers: {
      Authorization: `Bearer ${apiKey}`,
      "Content-Type": "application/json",
    },
    body: JSON.stringify({
      from: buildFrom(cfg),
      to: Array.isArray(options.to) ? options.to : [options.to],
      subject: options.subject,
      html: options.html ?? "",
      text: options.text ?? "",
      cc: options.cc,
      bcc: options.bcc,
      reply_to: options.replyTo,
    }),
  });

  if (!res.ok) {
    const data = await res.json().catch(() => ({}));
    throw new Error((data as { message?: string }).message ?? `Resend API error ${res.status}`);
  }
}

export async function sendViaSendGrid(cfg: EmailConfig, options: EmailOptions): Promise<void> {
  const apiKey = cfg.api_key;
  if (!apiKey) throw new Error("SendGrid API key is not configured.");

  const res = await fetch("https://api.sendgrid.com/v3/mail/send", {
    method: "POST",
    headers: {
      Authorization: `Bearer ${apiKey}`,
      "Content-Type": "application/json",
    },
    body: JSON.stringify({
      personalizations: [
        {
          to: (Array.isArray(options.to) ? options.to : [options.to]).map((e) => ({ email: e })),
          cc: options.cc?.map((e) => ({ email: e })),
          bcc: options.bcc?.map((e) => ({ email: e })),
        },
      ],
      from: { email: cfg.from_email ?? "noreply@example.com", name: cfg.from_name ?? "KRE8IV" },
      reply_to: options.replyTo ? { email: options.replyTo } : undefined,
      subject: options.subject,
      content: [{ type: "text/html", value: options.html ?? options.text ?? "" }],
    }),
  });

  if (!res.ok) {
    throw new Error(`SendGrid API error ${res.status}`);
  }
}

export async function sendViaMailgun(cfg: EmailConfig, options: EmailOptions): Promise<void> {
  const apiKey = cfg.api_key;
  const domain = cfg.mailgun_domain;
  if (!apiKey || !domain) throw new Error("Mailgun API key and domain are required.");

  const to = Array.isArray(options.to) ? options.to.join(",") : options.to;
  const form = new URLSearchParams({
    from: buildFrom(cfg),
    to,
    subject: options.subject,
    html: options.html ?? options.text ?? "",
  });
  if (options.cc?.length) form.set("cc", options.cc.join(","));
  if (options.bcc?.length) form.set("bcc", options.bcc.join(","));

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

export async function sendViaSMTP(cfg: EmailConfig, options: EmailOptions): Promise<void> {
  const nodemailer = await import("nodemailer");

  const host = cfg.smtp_host;
  const port = parseInt(cfg.smtp_port ?? "587", 10);
  const encryption = cfg.smtp_encryption ?? "starttls";

  if (!host) throw new Error("SMTP host is not configured.");

  const transporter = nodemailer.createTransport({
    host,
    port,
    secure: encryption === "ssl",
    requireTLS: encryption === "starttls",
    auth: cfg.smtp_user
      ? { user: cfg.smtp_user, pass: cfg.smtp_password ?? "" }
      : undefined,
    tls: { rejectUnauthorized: true },
  });

  await transporter.sendMail({
    from: buildFrom(cfg),
    to: Array.isArray(options.to) ? options.to.join(", ") : options.to,
    subject: options.subject,
    html: options.html,
    text: options.text,
    cc: options.cc,
    bcc: options.bcc,
    replyTo: options.replyTo,
    attachments: options.attachments?.map((a) => ({
      filename: a.filename,
      content: a.content,
    })),
  });
}

export async function sendViaGmailApi(cfg: EmailConfig, options: EmailOptions): Promise<void> {
  const accessToken = await getGoogleAccessToken(cfg);
  const raw = buildRawMimeMessage(cfg, options);

  const res = await fetch("https://gmail.googleapis.com/gmail/v1/users/me/messages/send", {
    method: "POST",
    headers: {
      Authorization: `Bearer ${accessToken}`,
      "Content-Type": "application/json",
    },
    body: JSON.stringify({ raw }),
  });

  if (!res.ok) {
    const data = await res.json().catch(() => ({}));
    throw new Error(readProviderError(data) ?? `Gmail API error ${res.status}`);
  }
}

export async function sendViaMicrosoftGraph(cfg: EmailConfig, options: EmailOptions): Promise<void> {
  const accessToken = await getMicrosoftAccessToken(cfg);

  const res = await fetch("https://graph.microsoft.com/v1.0/me/sendMail", {
    method: "POST",
    headers: {
      Authorization: `Bearer ${accessToken}`,
      "Content-Type": "application/json",
    },
    body: JSON.stringify({
      message: buildGraphMessage(cfg, options),
      saveToSentItems: true,
    }),
  });

  if (!res.ok) {
    const data = await res.json().catch(() => ({}));
    throw new Error(readProviderError(data) ?? `Microsoft Graph error ${res.status}`);
  }
}

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

export function buildFrom(cfg: EmailConfig): string {
  const email = cfg.from_email ?? "noreply@example.com";
  const name = cfg.from_name ?? "KRE8IV";
  return `"${name}" <${email}>`;
}

function isAccessTokenFresh(expiresAt: string | undefined): boolean {
  if (!expiresAt) {
    return false;
  }

  const expiryMs = Date.parse(expiresAt);
  if (!Number.isFinite(expiryMs)) {
    return false;
  }

  return expiryMs > Date.now() + 60_000;
}

async function getGoogleAccessToken(cfg: EmailConfig): Promise<string> {
  if (cfg.oauth_access_token && isAccessTokenFresh(cfg.oauth_token_expiry)) {
    return cfg.oauth_access_token;
  }

  if (!cfg.oauth_refresh_token) {
    if (cfg.oauth_access_token) {
      return cfg.oauth_access_token;
    }

    throw new Error("Google OAuth access token is not configured.");
  }

  const clientId = process.env.GOOGLE_EMAIL_CLIENT_ID || process.env.GOOGLE_CLIENT_ID;
  const clientSecret = process.env.GOOGLE_EMAIL_CLIENT_SECRET || process.env.GOOGLE_CLIENT_SECRET;
  if (!clientId || !clientSecret) {
    throw new Error("Google email OAuth client credentials are not configured.");
  }

  const token = await refreshOAuthToken("https://oauth2.googleapis.com/token", {
    client_id: clientId,
    client_secret: clientSecret,
    refresh_token: cfg.oauth_refresh_token,
    grant_type: "refresh_token",
  });

  await persistRefreshedOAuthToken(token);

  return token.accessToken;
}

async function getMicrosoftAccessToken(cfg: EmailConfig): Promise<string> {
  if (cfg.oauth_access_token && isAccessTokenFresh(cfg.oauth_token_expiry)) {
    return cfg.oauth_access_token;
  }

  if (!cfg.oauth_refresh_token) {
    if (cfg.oauth_access_token) {
      return cfg.oauth_access_token;
    }

    throw new Error("Microsoft OAuth access token is not configured.");
  }

  const clientId = process.env.MICROSOFT_EMAIL_CLIENT_ID || process.env.MICROSOFT_CLIENT_ID;
  const clientSecret = process.env.MICROSOFT_EMAIL_CLIENT_SECRET || process.env.MICROSOFT_CLIENT_SECRET;
  if (!clientId || !clientSecret) {
    throw new Error("Microsoft email OAuth client credentials are not configured.");
  }

  const token = await refreshOAuthToken(getMicrosoftEmailTokenUrl(), {
    client_id: clientId,
    client_secret: clientSecret,
    refresh_token: cfg.oauth_refresh_token,
    grant_type: "refresh_token",
    scope: "openid email profile offline_access https://graph.microsoft.com/Mail.Send https://graph.microsoft.com/Mail.Send.Shared https://graph.microsoft.com/User.Read",
  });

  await persistRefreshedOAuthToken(token);

  return token.accessToken;
}

interface RefreshedOAuthToken {
  accessToken: string;
  expiresAt: string;
}

async function refreshOAuthToken(
  endpoint: string,
  params: Record<string, string>,
): Promise<RefreshedOAuthToken> {
  const res = await fetch(endpoint, {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: new URLSearchParams(params),
  });

  const data = await res.json().catch(() => ({}));
  if (!res.ok || !isObjectRecord(data) || typeof data.access_token !== "string") {
    throw new Error(readProviderError(data) ?? `OAuth token refresh failed with status ${res.status}`);
  }

  const expiresIn = typeof data.expires_in === "number" ? data.expires_in : 3600;

  return {
    accessToken: data.access_token,
    expiresAt: new Date(Date.now() + expiresIn * 1000).toISOString(),
  };
}

async function persistRefreshedOAuthToken(token: RefreshedOAuthToken): Promise<void> {
  const adminClient = createAdminClientIfAvailable();
  if (!adminClient) {
    return;
  }

  const now = new Date().toISOString();
  const writes = [
    { key: "oauth_access_token", value: encrypt(token.accessToken), encrypted: true },
    { key: "oauth_token_expiry", value: token.expiresAt, encrypted: false },
  ];

  for (const { key, value, encrypted } of writes) {
    await adminClient.from("system_settings").upsert(
      {
        category: "email",
        key,
        value,
        is_encrypted: encrypted,
        updated_at: now,
      },
      { onConflict: "category,key" },
    );
  }
}

function buildRawMimeMessage(cfg: EmailConfig, options: EmailOptions): string {
  const lines = [
    `From: ${buildFrom(cfg)}`,
    `To: ${formatAddressList(options.to)}`,
  ];

  if (options.cc?.length) {
    lines.push(`Cc: ${formatAddressList(options.cc)}`);
  }

  if (options.bcc?.length) {
    lines.push(`Bcc: ${formatAddressList(options.bcc)}`);
  }

  if (options.replyTo) {
    lines.push(`Reply-To: ${options.replyTo}`);
  }

  lines.push(`Subject: ${encodeMimeHeader(options.subject)}`, "MIME-Version: 1.0");

  const attachments = options.attachments ?? [];
  if (attachments.length === 0) {
    const contentType = options.html ? "text/html" : "text/plain";
    lines.push(`Content-Type: ${contentType}; charset="UTF-8"`, "", options.html ?? options.text ?? "");

    return base64UrlEncode(lines.join("\r\n"));
  }

  const boundary = `kre8iv-${crypto.randomUUID()}`;
  lines.push(`Content-Type: multipart/mixed; boundary="${boundary}"`, "");
  lines.push(`--${boundary}`);
  lines.push(`Content-Type: ${options.html ? "text/html" : "text/plain"}; charset="UTF-8"`, "");
  lines.push(options.html ?? options.text ?? "");

  for (const attachment of attachments) {
    lines.push(`--${boundary}`);
    lines.push("Content-Type: application/octet-stream");
    lines.push("Content-Transfer-Encoding: base64");
    lines.push(`Content-Disposition: attachment; filename="${escapeHeaderValue(attachment.filename)}"`, "");
    lines.push(toBase64(attachment.content));
  }

  lines.push(`--${boundary}--`);

  return base64UrlEncode(lines.join("\r\n"));
}

function buildGraphMessage(cfg: EmailConfig, options: EmailOptions) {
  const fromAddress = cfg.from_email?.trim();
  const fromName = cfg.from_name?.trim();

  return {
    subject: options.subject,
    body: {
      contentType: options.html ? "HTML" : "Text",
      content: options.html ?? options.text ?? "",
    },
    from: fromAddress
      ? {
          emailAddress: {
            address: fromAddress,
            ...(fromName ? { name: fromName } : {}),
          },
        }
      : undefined,
    toRecipients: buildGraphRecipients(options.to),
    ccRecipients: options.cc ? buildGraphRecipients(options.cc) : undefined,
    bccRecipients: options.bcc ? buildGraphRecipients(options.bcc) : undefined,
    replyTo: options.replyTo ? buildGraphRecipients(options.replyTo) : undefined,
    attachments: options.attachments?.map((attachment) => ({
      "@odata.type": "#microsoft.graph.fileAttachment",
      name: attachment.filename,
      contentBytes: toBase64(attachment.content),
    })),
  };
}

function buildGraphRecipients(recipients: string | string[]) {
  return (Array.isArray(recipients) ? recipients : [recipients]).map((address) => ({
    emailAddress: { address },
  }));
}

function formatAddressList(recipients: string | string[]): string {
  return (Array.isArray(recipients) ? recipients : [recipients]).join(", ");
}

function encodeMimeHeader(value: string): string {
  return /[^\x20-\x7E]/.test(value) ? `=?UTF-8?B?${toBase64(value)}?=` : value;
}

function escapeHeaderValue(value: string): string {
  return value.replace(/"/g, '\\"');
}

function toBase64(value: Buffer | string): string {
  return Buffer.from(value).toString("base64");
}

function base64UrlEncode(value: string): string {
  return toBase64(value).replace(/\+/g, "-").replace(/\//g, "_").replace(/=+$/u, "");
}

function readProviderError(data: unknown): string | null {
  if (!isObjectRecord(data)) {
    return null;
  }

  const message = data.message;
  if (typeof message === "string") {
    return message;
  }

  const error = data.error;
  if (typeof error === "string") {
    return error;
  }

  if (isObjectRecord(error) && typeof error.message === "string") {
    return error.message;
  }

  return null;
}

function isObjectRecord(value: unknown): value is Record<string, unknown> {
  return Boolean(value) && typeof value === "object";
}
