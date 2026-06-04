import { NextRequest, NextResponse } from "next/server";
import { createClient, createAdminClientIfAvailable } from "@/lib/supabase/server";
import { isUserAdmin } from "@/lib/rbac/check";
import { encrypt } from "@/lib/encryption";
import { getSigningSecret, verifySignedToken } from "@/lib/auth/signed-token";

const REDIRECT_BASE = "/admin/email";
const UUID_REGEX =
  /^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i;

/**
 * GET /api/admin/email/callback/google
 * Handles Google OAuth callback for email provider connection.
 * Exchanges the authorization code for tokens and persists them in
 * system_settings (category = 'email').
 */
export async function GET(req: NextRequest) {
  const appUrl = process.env.NEXT_PUBLIC_APP_URL ?? "http://localhost:3000";
  const back = (status: string) =>
    NextResponse.redirect(`${appUrl}${REDIRECT_BASE}?${status}`);

  const code = req.nextUrl.searchParams.get("code");
  const stateToken = req.nextUrl.searchParams.get("state");
  const errorParam = req.nextUrl.searchParams.get("error");

  if (errorParam || !code || !stateToken) return back("error=google_oauth_denied");

  const stateSecret = getSigningSecret("EMAIL_OAUTH_STATE_SECRET");
  if (!stateSecret) return back("error=not_configured");

  const statePayload = verifySignedToken(stateToken, stateSecret);
  const userId =
    typeof statePayload?.userId === "string" ? statePayload.userId : null;
  const provider =
    typeof statePayload?.provider === "string" ? statePayload.provider : null;
  if (!userId || !UUID_REGEX.test(userId) || provider !== "google")
    return back("error=invalid_state");

  // Verify the current session matches the admin that initiated the flow
  const supabase = await createClient();
  const { data: { user } } = await supabase.auth.getUser();
  if (!user || user.id !== userId) return back("error=session_mismatch");
  if (!(await isUserAdmin(user.id))) return back("error=forbidden");

  const clientId =
    process.env.GOOGLE_EMAIL_CLIENT_ID || process.env.GOOGLE_CLIENT_ID;
  const clientSecret =
    process.env.GOOGLE_EMAIL_CLIENT_SECRET || process.env.GOOGLE_CLIENT_SECRET;
  if (!clientId || !clientSecret) return back("error=not_configured");

  const adminClient = createAdminClientIfAvailable();
  if (!adminClient) return back("error=admin_client_unavailable");

  const redirectUri = `${appUrl}/api/admin/email/callback/google`;

  try {
    const tokenRes = await fetch("https://oauth2.googleapis.com/token", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: new URLSearchParams({
        code,
        client_id: clientId,
        client_secret: clientSecret,
        redirect_uri: redirectUri,
        grant_type: "authorization_code",
      }),
    });
    const tokens = await tokenRes.json();
    if (!tokens.access_token) {
      console.error("[admin/email/callback/google] Token exchange failed:", tokens);
      return back("error=token_exchange_failed");
    }

    const profileRes = await fetch(
      "https://openidconnect.googleapis.com/v1/userinfo",
      { headers: { Authorization: `Bearer ${tokens.access_token}` } },
    );
    const profile = await profileRes.json();
    const accountEmail = typeof profile.email === "string" ? profile.email : "";

    const tokenExpiryIso = tokens.expires_in
      ? new Date(Date.now() + tokens.expires_in * 1000).toISOString()
      : "";

    const writes: Array<{ key: string; value: string; encrypted: boolean }> = [
      { key: "provider", value: "gmail", encrypted: false },
      { key: "oauth_provider", value: "google", encrypted: false },
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
        console.error(`[admin/email/callback/google] Failed to save setting ${key}:`, error);
        return back("error=setting_save_failed");
      }
    }

    return back("connected=google");
  } catch (err) {
    console.error("[admin/email/callback/google] Unexpected error:", err);
    return back("error=unexpected");
  }
}
