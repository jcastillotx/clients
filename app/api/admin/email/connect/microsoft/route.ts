import { NextResponse } from "next/server";
import { createClient } from "@/lib/supabase/server";
import { isUserAdmin } from "@/lib/rbac/check";
import { getMicrosoftEmailAuthorizeUrl } from "@/lib/auth/microsoft-email-oauth";
import { createSignedToken, getSigningSecret } from "@/lib/auth/signed-token";
import {
  apiError,
  apiForbidden,
  apiUnauthorized,
} from "@/lib/api/response";

const MICROSOFT_SCOPES = [
  "openid",
  "email",
  "profile",
  "offline_access",
  "https://graph.microsoft.com/Mail.Send",
  "https://graph.microsoft.com/Mail.Send.Shared",
  "https://graph.microsoft.com/User.Read",
].join(" ");

export async function GET(request: Request) {
  const supabase = await createClient();
  const { data: { user } } = await supabase.auth.getUser();
  if (!user) return apiUnauthorized(request);
  if (!(await isUserAdmin(user.id))) return apiForbidden(request);

  const clientId =
    process.env.MICROSOFT_EMAIL_CLIENT_ID || process.env.MICROSOFT_CLIENT_ID;
  if (!clientId) {
    return apiError(request, {
      status: 501,
      code: "FEATURE_NOT_ENABLED",
      message: "Microsoft email integration is not configured on this server.",
    });
  }

  const appUrl = process.env.NEXT_PUBLIC_APP_URL ?? "http://localhost:3000";
  const redirectUri = `${appUrl}/api/admin/email/callback/microsoft`;
  const stateSecret = getSigningSecret("EMAIL_OAUTH_STATE_SECRET");
  if (!stateSecret) {
    return apiError(request, {
      status: 501,
      code: "FEATURE_NOT_ENABLED",
      message: "Email OAuth state secret is not configured.",
    });
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
