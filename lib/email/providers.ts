/**
 * Shared email provider implementations.
 * Used by sendEmail() in client.ts and the test route in /api/admin/email/test.
 */

import { createAdminClientIfAvailable } from "@/lib/supabase/server";
import { decrypt } from "@/lib/encryption";
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
    case "office365":
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

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

export function buildFrom(cfg: EmailConfig): string {
  const email = cfg.from_email ?? "noreply@example.com";
  const name = cfg.from_name ?? "KRE8IV";
  return `"${name}" <${email}>`;
}
