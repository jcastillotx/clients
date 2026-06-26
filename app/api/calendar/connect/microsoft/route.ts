import { NextResponse } from "next/server";
import type { NextRequest } from "next/server";
import { createClient } from "@/lib/supabase/server";
import { createSignedToken, getSigningSecret } from "@/lib/auth/signed-token";
import {
  redirectToCalendarSettings,
  redirectToLogin,
} from "@/lib/calendar/settings-redirect";
import {
  getMicrosoftCalendarTenant,
  hasCompleteCalendarOAuthCredentials,
  resolveCalendarOAuthCredentialsForUser,
} from "@/lib/calendar/oauth-credentials";

const MICROSOFT_SCOPES = ["Calendars.Read", "offline_access", "User.Read"].join(
  " ",
);

/**
 * GET /api/calendar/connect/microsoft
 * Initiates the Microsoft Calendar OAuth flow.
 *
 * Required env vars:
 *   MICROSOFT_CALENDAR_CLIENT_ID
 *   MICROSOFT_CALENDAR_CLIENT_SECRET (callback token exchange)
 *   CALENDAR_OAUTH_STATE_SECRET (or ENCRYPTION_KEY) for signed OAuth state
 *   NEXT_PUBLIC_APP_URL
 */
export async function GET(request: NextRequest) {
  const supabase = await createClient();
  const {
    data: { user },
  } = await supabase.auth.getUser();

  if (!user) {
    return redirectToLogin();
  }

  const credentials = await resolveCalendarOAuthCredentialsForUser(user.id, "microsoft");
  if (!hasCompleteCalendarOAuthCredentials(credentials)) {
    return redirectToCalendarSettings("microsoft_not_configured");
  }

  const appUrl = process.env.NEXT_PUBLIC_APP_URL ?? request.nextUrl.origin;
  const redirectUri = `${appUrl}/api/calendar/callback/microsoft`;
  const stateSecret = getSigningSecret("CALENDAR_OAUTH_STATE_SECRET");
  if (!stateSecret) {
    return redirectToCalendarSettings("oauth_state_not_configured");
  }

  const state = createSignedToken(
    {
      userId: user.id,
      provider: "microsoft",
      credentialClientId: credentials.clientScopeId,
    },
    stateSecret,
    10 * 60,
  );

  const params = new URLSearchParams({
    client_id: credentials.client_id ?? "",
    redirect_uri: redirectUri,
    response_type: "code",
    scope: MICROSOFT_SCOPES,
    response_mode: "query",
    state,
  });

  return NextResponse.redirect(
    `https://login.microsoftonline.com/${getMicrosoftCalendarTenant(credentials)}/oauth2/v2.0/authorize?${params}`,
  );
}
