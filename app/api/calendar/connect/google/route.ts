import { NextResponse } from "next/server";
import { createClient } from "@/lib/supabase/server";
import { createSignedToken, getSigningSecret } from "@/lib/auth/signed-token";
import {
  redirectToCalendarSettings,
  redirectToLogin,
} from "@/lib/calendar/settings-redirect";

const GOOGLE_SCOPES = [
  "https://www.googleapis.com/auth/calendar.readonly",
  "https://www.googleapis.com/auth/calendar.freebusy",
].join(" ");

/**
 * GET /api/calendar/connect/google
 * Initiates the Google Calendar OAuth flow.
 *
 * Required env vars:
 *   GOOGLE_CALENDAR_CLIENT_ID
 *   GOOGLE_CALENDAR_CLIENT_SECRET (callback token exchange)
 *   CALENDAR_OAUTH_STATE_SECRET (or ENCRYPTION_KEY) for signed OAuth state
 *   NEXT_PUBLIC_APP_URL
 */
export async function GET() {
  const supabase = await createClient();
  const {
    data: { user },
  } = await supabase.auth.getUser();

  if (!user) {
    return redirectToLogin();
  }

  const clientId = process.env.GOOGLE_CALENDAR_CLIENT_ID;
  if (!clientId) {
    return redirectToCalendarSettings("google_not_configured");
  }

  const appUrl = process.env.NEXT_PUBLIC_APP_URL ?? "http://localhost:3000";
  const redirectUri = `${appUrl}/api/calendar/callback/google`;
  const stateSecret = getSigningSecret("CALENDAR_OAUTH_STATE_SECRET");
  if (!stateSecret) {
    return redirectToCalendarSettings("oauth_state_not_configured");
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
