import { NextResponse } from "next/server";
import { createClient } from "@/lib/supabase/server";
import { isUserAdmin } from "@/lib/rbac/check";
import { createSignedToken, getSigningSecret } from "@/lib/auth/signed-token";

const GOOGLE_SCOPES = [
  "openid",
  "email",
  "profile",
  "https://www.googleapis.com/auth/gmail.send",
].join(" ");

/**
 * GET /api/admin/email/connect/google
 * Starts the Google OAuth flow so an admin can connect a Gmail account
 * for platform email sending (gmail.send scope).
 *
 * Required env vars:
 *   GOOGLE_EMAIL_CLIENT_ID (falls back to GOOGLE_CLIENT_ID)
 *   NEXT_PUBLIC_APP_URL
 */
export async function GET() {
  const supabase = await createClient();
  const { data: { user } } = await supabase.auth.getUser();
  if (!user) return NextResponse.json({ error: "Unauthorized" }, { status: 401 });
  if (!(await isUserAdmin(user.id)))
    return NextResponse.json({ error: "Forbidden" }, { status: 403 });

  const clientId =
    process.env.GOOGLE_EMAIL_CLIENT_ID || process.env.GOOGLE_CLIENT_ID;
  if (!clientId) {
    return NextResponse.json(
      { error: "Google email integration is not configured on this server." },
      { status: 501 },
    );
  }

  const appUrl = process.env.NEXT_PUBLIC_APP_URL ?? "http://localhost:3000";
  const redirectUri = `${appUrl}/api/admin/email/callback/google`;
  const stateSecret = getSigningSecret("EMAIL_OAUTH_STATE_SECRET");
  if (!stateSecret) {
    return NextResponse.json(
      { error: "Email OAuth state secret is not configured." },
      { status: 501 },
    );
  }

  const state = createSignedToken(
    { userId: user.id, provider: "google" },
    stateSecret,
    10 * 60,
  );

  const params = new URLSearchParams({
    client_id: clientId,
    redirect_uri: redirectUri,
    response_type: "code",
    scope: GOOGLE_SCOPES,
    access_type: "offline",
    prompt: "consent",
    state,
  });

  return NextResponse.redirect(
    `https://accounts.google.com/o/oauth2/v2/auth?${params}`,
  );
}
