import { NextResponse } from "next/server";
import { createClient } from "@/lib/supabase/server";
import { createSignedToken, getSigningSecret } from "@/lib/auth/signed-token";

const MICROSOFT_SCOPES = ["Calendars.Read", "offline_access", "User.Read"].join(
  " ",
);

/**
 * GET /api/calendar/connect/microsoft
 * Initiates the Microsoft Calendar OAuth flow.
 *
 * Required env vars:
 *   MICROSOFT_CALENDAR_CLIENT_ID
 *   NEXT_PUBLIC_APP_URL
 */
export async function GET() {
  const supabase = await createClient();
  const {
    data: { user },
  } = await supabase.auth.getUser();

  if (!user)
    return NextResponse.json({ error: "Unauthorized" }, { status: 401 });

  const clientId = process.env.MICROSOFT_CALENDAR_CLIENT_ID;
  if (!clientId) {
    return NextResponse.json(
      {
        error:
          "Microsoft Calendar integration is not configured on this server.",
      },
      { status: 501 },
    );
  }

  const appUrl = process.env.NEXT_PUBLIC_APP_URL ?? "http://localhost:3000";
  const redirectUri = `${appUrl}/api/calendar/callback/microsoft`;
  const stateSecret = getSigningSecret("CALENDAR_OAUTH_STATE_SECRET");
  if (!stateSecret) {
    return NextResponse.json(
      { error: "Calendar OAuth state secret is not configured." },
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

  return NextResponse.redirect(
    `https://login.microsoftonline.com/common/oauth2/v2.0/authorize?${params}`,
  );
}
