import { NextRequest, NextResponse } from "next/server";
import { createClient } from "@/lib/supabase/server";
import { createAdminClientIfAvailable } from "@/lib/supabase/server";
import { isUserAdmin } from "@/lib/rbac/check";
import { encrypt, decrypt, maskSecret } from "@/lib/encryption";

// Keys stored in system_settings under category = 'email'
const EMAIL_KEYS = [
  "provider",      // resend | gmail | office365 | sendgrid | mailgun | smtp
  "from_email",
  "from_name",
  // Resend / SendGrid / Mailgun
  "api_key",
  "mailgun_domain",
  // SMTP (generic, Gmail, Office 365)
  "smtp_host",
  "smtp_port",
  "smtp_user",
  "smtp_password",
  "smtp_encryption", // none | starttls | ssl
  // OAuth (Gmail / Office 365) — written by /api/admin/email/callback/{provider}
  "oauth_provider",         // google | microsoft
  "oauth_account_email",    // connected mailbox
  "oauth_access_token",     // encrypted
  "oauth_refresh_token",    // encrypted
  "oauth_token_expiry",     // ISO timestamp
] as const;

const SECRET_KEYS = new Set([
  "api_key",
  "smtp_password",
  "oauth_access_token",
  "oauth_refresh_token",
]);

type EmailKey = (typeof EMAIL_KEYS)[number];

interface SettingRow {
  key: string;
  value: string;
  is_encrypted: boolean;
}

/**
 * GET /api/admin/email
 * Returns current email provider settings with secrets masked.
 */
export async function GET() {
  const supabase = await createClient();
  const { data: { user } } = await supabase.auth.getUser();
  if (!user) return NextResponse.json({ error: "Unauthorized" }, { status: 401 });
  if (!(await isUserAdmin(user.id))) return NextResponse.json({ error: "Forbidden" }, { status: 403 });

  const adminClient = createAdminClientIfAvailable();
  if (!adminClient) return NextResponse.json({ error: "Admin client not configured" }, { status: 503 });

  const { data: rows } = await adminClient
    .from("system_settings")
    .select("key, value, is_encrypted")
    .eq("category", "email");

  const settings: Record<string, string> = {};
  for (const row of (rows ?? []) as SettingRow[]) {
    try {
      const plain = row.is_encrypted ? decrypt(row.value) : row.value;
      settings[row.key] = SECRET_KEYS.has(row.key) ? maskSecret(plain) : plain;
    } catch {
      settings[row.key] = "••••••••";
    }
  }

  return NextResponse.json(settings);
}

/**
 * PUT /api/admin/email
 * Save email provider settings. Blank values are skipped (preserve existing).
 */
export async function PUT(req: NextRequest) {
  const supabase = await createClient();
  const { data: { user } } = await supabase.auth.getUser();
  if (!user) return NextResponse.json({ error: "Unauthorized" }, { status: 401 });
  if (!(await isUserAdmin(user.id))) return NextResponse.json({ error: "Forbidden" }, { status: 403 });

  const adminClient = createAdminClientIfAvailable();
  if (!adminClient) return NextResponse.json({ error: "Admin client not configured" }, { status: 503 });

  const body = await req.json();

  for (const key of EMAIL_KEYS) {
    const raw: unknown = body[key];
    if (raw === undefined || raw === null) continue;
    const val = String(raw).trim();
    // Skip blank values — preserve existing secret rather than overwrite with empty
    if (val === "" || val === "••••••••") continue;

    const isSecret = SECRET_KEYS.has(key);
    const stored = isSecret ? encrypt(val) : val;

    await adminClient.from("system_settings").upsert(
      {
        category: "email",
        key,
        value: stored,
        is_encrypted: isSecret,
        updated_by: user.id,
        updated_at: new Date().toISOString(),
      },
      { onConflict: "category,key" },
    );
  }

  return NextResponse.json({ success: true });
}

/**
 * POST /api/admin/email/test  — handled by /test sub-route below.
 * Kept here so the route file structure is clear.
 */
