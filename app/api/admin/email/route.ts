import { NextRequest } from "next/server";
import { createClient } from "@/lib/supabase/server";
import { createAdminClientIfAvailable } from "@/lib/supabase/server";
import { isUserAdmin } from "@/lib/rbac/check";
import { encrypt, decrypt, maskSecret } from "@/lib/encryption";
import {
  apiError,
  apiForbidden,
  apiInternalError,
  apiSuccess,
  apiUnauthorized,
} from "@/lib/api/response";

const EMAIL_KEYS = [
  "provider",
  "from_email",
  "from_name",
  "api_key",
  "mailgun_domain",
  "smtp_host",
  "smtp_port",
  "smtp_user",
  "smtp_password",
  "smtp_encryption",
  "oauth_provider",
  "oauth_account_email",
  "oauth_access_token",
  "oauth_refresh_token",
  "oauth_token_expiry",
] as const;

const SECRET_KEYS = new Set([
  "api_key",
  "smtp_password",
  "oauth_access_token",
  "oauth_refresh_token",
]);

interface SettingRow {
  key: string;
  value: string;
  is_encrypted: boolean;
}

/**
 * GET /api/admin/email
 * Returns current email provider settings with secrets masked.
 */
export async function GET(request: Request) {
  const supabase = await createClient();
  const { data: { user } } = await supabase.auth.getUser();
  if (!user) return apiUnauthorized(request);
  if (!(await isUserAdmin(user.id))) return apiForbidden(request);

  const adminClient = createAdminClientIfAvailable();
  if (!adminClient) {
    return apiError(request, {
      status: 503,
      code: "SERVICE_UNAVAILABLE",
      message: "Admin client not configured",
    });
  }

  const { data: rows, error: selectError } = await adminClient
    .from("system_settings")
    .select("key, value, is_encrypted")
    .eq("category", "email");

  if (selectError) {
    console.error("[admin/email] Failed to load settings:", selectError);
    return apiInternalError(request, "Failed to load email settings");
  }

  const settings: Record<string, string> = {};
  for (const row of (rows ?? []) as SettingRow[]) {
    try {
      const plain = row.is_encrypted ? decrypt(row.value) : row.value;
      settings[row.key] = SECRET_KEYS.has(row.key) ? maskSecret(plain) : plain;
    } catch {
      settings[row.key] = "••••••••";
    }
  }

  return apiSuccess(request, settings, { extra: settings });
}

/**
 * PUT /api/admin/email
 * Save email provider settings. Blank values are skipped (preserve existing).
 */
export async function PUT(req: NextRequest) {
  const supabase = await createClient();
  const { data: { user } } = await supabase.auth.getUser();
  if (!user) return apiUnauthorized(req);
  if (!(await isUserAdmin(user.id))) return apiForbidden(req);

  const adminClient = createAdminClientIfAvailable();
  if (!adminClient) {
    return apiError(req, {
      status: 503,
      code: "SERVICE_UNAVAILABLE",
      message: "Admin client not configured",
    });
  }

  const body = await req.json();

  for (const key of EMAIL_KEYS) {
    const raw: unknown = body[key];
    if (raw === undefined || raw === null) continue;
    const val = String(raw).trim();
    if (val === "" || val === "••••••••") continue;

    const isSecret = SECRET_KEYS.has(key);
    const stored = isSecret ? encrypt(val) : val;

    const { error } = await adminClient.from("system_settings").upsert(
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

    if (error) {
      console.error(`[admin/email] Failed to save setting ${key}:`, error);
      return apiInternalError(req, `Failed to save ${key.replace(/_/g, " ")}`);
    }
  }

  return apiSuccess(req, { saved: true }, { extra: { success: true } });
}
