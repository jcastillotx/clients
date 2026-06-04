import { NextResponse } from "next/server";
import { createClient } from "@/lib/supabase/server";
import { isUserAdmin } from "@/lib/rbac/check";
import { getMicrosoftEmailAuthorizeUrl } from "@/lib/auth/microsoft-email-oauth";
import { createSignedToken, getSigningSecret } from "@/lib/auth/signed-token";

const MICROSOFT_SCOPES = [
  "openid",
  "email",
  "profile",
  "offline_access",
  "https://graph.microsoft.com/Mail.Send",
  "https://graph.microsoft.com/User.Read",
].join(" ");

/**
 * GET /api/admin/email/connect/microsoft
 * Starts the Microsoft OAuth flow so an admin can connect a Microsoft 365
 * / Outlook mailbox for platform email sending (Mail.Send scope).
 *
 * Required env vars:
 *   MICROSOFT_EMAIL_CLIENT_ID (falls back to MICROSOFT_CLIENT_ID)
 *   NEXT_PUBLIC_APP_URL
 */
export async function GET() {
  const supabase = await createClient();
  const { data: { user } } = await supabase.auth.getUser();
  if (!user) return NextResponse.json({ error: "Unauthorized" }, { status: 401 });
  if (!(await isUserAdmin(user.id)))
    return NextResponse.json({ error: "Forbidden" }, { status: 403 });

  const clientId =
    process.env.MICROSOFT_EMAIL_CLIENT_ID || process.env.MICROSOFT_CLIENT_ID;
  if (!clientId) {
    return NextResponse.json(
      { error: "Microsoft email integration is not configured on this server." },
      { status: 501 },
    );
  }

  const appUrl = process.env.NEXT_PUBLIC_APP_URL ?? "http://localhost:3000";
  const redirectUri = `${appUrl}/api/admin/email/callback/microsoft`;
  const stateSecret = getSigningSecret("EMAIL_OAUTH_STATE_SECRET");
  if (!stateSecret) {
    return NextResponse.json(
      { error: "Email OAuth state secret is not configured." },
      { status: 501 },
    );
  }

  const state = createSignedToken(
    { userId: user.id, provider: "microsoft" },
    stateSecret,
    10 * 60,
  );

  const params = new URLSearchParams({
    client_id: clientId,
    redirect_uri: redirectUri,
    response_type: "code",
    scope: MICROSOFT_SCOPES,
    response_mode: "query",
    state,
  });

  return NextResponse.redirect(`${getMicrosoftEmailAuthorizeUrl()}?${params}`);
}
