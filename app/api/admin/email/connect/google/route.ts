import { NextResponse } from "next/server";
import { createClient } from "@/lib/supabase/server";
import { isUserAdmin } from "@/lib/rbac/check";
import { createSignedToken, getSigningSecret } from "@/lib/auth/signed-token";
import {
  apiError,
  apiForbidden,
  apiUnauthorized,
} from "@/lib/api/response";

const GOOGLE_SCOPES = [
  "openid",
  "email",
  "profile",
  "https://www.googleapis.com/auth/gmail.send",
].join(" ");

export async function GET(request: Request) {
  const supabase = await createClient();
  const { data: { user } } = await supabase.auth.getUser();
  if (!user) return apiUnauthorized(request);
  if (!(await isUserAdmin(user.id))) return apiForbidden(request);

  const clientId =
    process.env.GOOGLE_EMAIL_CLIENT_ID || process.env.GOOGLE_CLIENT_ID;
  if (!clientId) {
    return apiError(request, {
      status: 501,
      code: "FEATURE_NOT_ENABLED",
      message: "Google email integration is not configured on this server.",
    });
  }

  const appUrl = process.env.NEXT_PUBLIC_APP_URL ?? "http://localhost:3000";
  const redirectUri = `${appUrl}/api/admin/email/callback/google`;
  const stateSecret = getSigningSecret("EMAIL_OAUTH_STATE_SECRET");
  if (!stateSecret) {
    return apiError(request, {
      status: 501,
      code: "FEATURE_NOT_ENABLED",
      message: "Email OAuth state secret is not configured.",
    });
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
