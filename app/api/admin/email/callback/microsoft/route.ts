import { NextRequest, NextResponse } from "next/server";
import { createClient, createAdminClientIfAvailable } from "@/lib/supabase/server";
import { isUserAdmin } from "@/lib/rbac/check";
import { encrypt } from "@/lib/encryption";
import { getMicrosoftEmailTokenUrl } from "@/lib/auth/microsoft-email-oauth";
import { getSigningSecret, verifySignedToken } from "@/lib/auth/signed-token";

const REDIRECT_BASE = "/admin/email";
const UUID_REGEX =
  /^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i;

const TOKEN_SCOPE =
  "openid email profile offline_access https://graph.microsoft.com/Mail.Send https://graph.microsoft.com/Mail.Send.Shared https://graph.microsoft.com/User.Read";

/**
 * GET /api/admin/email/callback/microsoft
 * Handles Microsoft OAuth callback for email provider connection.
 * Exchanges the authorization code for tokens and persists them in
 * system_settings (category = 'email').
 */
export async function GET(req: NextRequest) {
  const appUrl = process.env.NEXT_PUBLIC_APP_URL ?? "http://localhost:3000";
  const back = (status: string) =>
    NextResponse.redirect(`${appUrl}${REDIRECT_BASE}?${status}`);
  const microsoftErrorBack = () => {
    const params = new URLSearchParams({
      error: req.nextUrl.searchParams.get("error") || "microsoft_oauth_denied",
    });
    const description = req.nextUrl.searchParams.get("error_description");
    if (description) {
      params.set("error_description", description);
    }

    return back(params.toString());
  };

  const code = req.nextUrl.searchParams.get("code");
  const stateToken = req.nextUrl.searchParams.get("state");
  const errorParam = req.nextUrl.searchParams.get("error");

  if (errorParam) return microsoftErrorBack();
  if (!code || !stateToken) return back("error=microsoft_oauth_missing_code");

  const stateSecret = getSigningSecret("EMAIL_OAUTH_STATE_SECRET");
  if (!stateSecret) return back("error=not_configured");

  const statePayload = verifySignedToken(stateToken, stateSecret);
  const userId =
    typeof statePayload?.userId === "string" ? statePayload.userId : null;
  const provider =
    typeof statePayload?.provider === "string" ? statePayload.provider : null;
  if (!userId || !UUID_REGEX.test(userId) || provider !== "microsoft")
    return back("error=invalid_state");

  const supabase = await createClient();
  const { data: { user } } = await supabase.auth.getUser();
  if (!user || user.id !== userId) return back("error=session_mismatch");
  if (!(await isUserAdmin(user.id))) return back("error=forbidden");

  const clientId =
    process.env.MICROSOFT_EMAIL_CLIENT_ID || process.env.MICROSOFT_CLIENT_ID;
  const clientSecret =
    process.env.MICROSOFT_EMAIL_CLIENT_SECRET ||
    process.env.MICROSOFT_CLIENT_SECRET;
  if (!clientId || !clientSecret) return back("error=not_configured");

  const adminClient = createAdminClientIfAvailable();
  if (!adminClient) return back("error=admin_client_unavailable");

  const redirectUri = `${appUrl}/api/admin/email/callback/microsoft`;

  try {
    const tokenRes = await fetch(
      getMicrosoftEmailTokenUrl(),
      {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams({
          code,
          client_id: clientId,
          client_secret: clientSecret,
          redirect_uri: redirectUri,
          grant_type: "authorization_code",
          scope: TOKEN_SCOPE,
        }),
      },
    );
    const tokens = await tokenRes.json();
    if (!tokens.access_token) {
      console.error("[admin/email/callback/microsoft] Token exchange failed:", tokens);
      return back("error=token_exchange_failed");
    }

    const profileRes = await fetch("https://graph.microsoft.com/v1.0/me", {
      headers: { Authorization: `Bearer ${tokens.access_token}` },
    });
    const profile = await profileRes.json();
    const accountEmail =
      typeof profile.mail === "string" && profile.mail
        ? profile.mail
        : typeof profile.userPrincipalName === "string"
          ? profile.userPrincipalName
          : "";

    const tokenExpiryIso = tokens.expires_in
      ? new Date(Date.now() + tokens.expires_in * 1000).toISOString()
      : "";

    const writes: Array<{ key: string; value: string; encrypted: boolean }> = [
      { key: "provider", value: "office365", encrypted: false },
      { key: "oauth_provider", value: "microsoft", encrypted: false },
      { key: "oauth_account_email", value: accountEmail, encrypted: false },
      { key: "oauth_access_token", value: encrypt(tokens.access_token), encrypted: true },
      { key: "oauth_token_expiry", value: tokenExpiryIso, encrypted: false },
    ];
    if (tokens.refresh_token) {
      writes.push({
        key: "oauth_refresh_token",
        value: encrypt(tokens.refresh_token),
        encrypted: true,
      });
    }
    if (accountEmail) {
      writes.push({ key: "smtp_user", value: accountEmail, encrypted: false });
    }

    for (const { key, value, encrypted } of writes) {
      if (value === "") continue;
      const { error } = await adminClient.from("system_settings").upsert(
        {
          category: "email",
          key,
          value,
          is_encrypted: encrypted,
          updated_by: user.id,
          updated_at: new Date().toISOString(),
        },
        { onConflict: "category,key" },
      );

      if (error) {
        console.error(`[admin/email/callback/microsoft] Failed to save setting ${key}:`, error);
        return back("error=setting_save_failed");
      }
    }

    return back("connected=microsoft");
  } catch (err) {
    console.error("[admin/email/callback/microsoft] Unexpected error:", err);
    return back("error=unexpected");
  }
}
