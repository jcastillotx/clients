import { NextResponse } from "next/server";
import { createClient } from "@/lib/supabase/server";

const MICROSOFT_SCOPES = [
  "Calendars.Read",
  "offline_access",
  "User.Read",
].join(" ");

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

  if (!user) return NextResponse.json({ error: "Unauthorized" }, { status: 401 });

  const clientId = process.env.MICROSOFT_CALENDAR_CLIENT_ID;
  if (!clientId) {
    return NextResponse.json(
      { error: "Microsoft Calendar integration is not configured on this server." },
      { status: 501 },
    );
  }

  const appUrl = process.env.NEXT_PUBLIC_APP_URL ?? "http://localhost:3000";
  const redirectUri = `${appUrl}/api/calendar/callback/microsoft`;

  const params = new URLSearchParams({
    client_id: clientId,
    redirect_uri: redirectUri,
    response_type: "code",
    scope: MICROSOFT_SCOPES,
    response_mode: "query",
    // Embed user ID so the callback can associate the token
    state: user.id,
  });

  return NextResponse.redirect(
    `https://login.microsoftonline.com/common/oauth2/v2.0/authorize?${params}`,
  );
}
