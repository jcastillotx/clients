import { NextRequest, NextResponse } from "next/server";
import { db } from "@/lib/db";
import { calendarConnections } from "@/lib/db/schema/calendar-integrations";
import { encrypt } from "@/lib/encryption";
import { eq, and } from "drizzle-orm";
import { getSigningSecret, verifySignedToken } from "@/lib/auth/signed-token";
import {
  hasCompleteCalendarOAuthCredentials,
  resolveCalendarOAuthCredentialsForUser,
} from "@/lib/calendar/oauth-credentials";

/**
 * GET /api/calendar/callback/google
 * Handles the Google Calendar OAuth callback.
 * Exchanges the authorization code for tokens and persists the connection.
 *
 * Required env vars:
 *   GOOGLE_CALENDAR_CLIENT_ID
 *   GOOGLE_CALENDAR_CLIENT_SECRET
 *   NEXT_PUBLIC_APP_URL
 */
export async function GET(req: NextRequest) {
  const appUrl = process.env.NEXT_PUBLIC_APP_URL ?? req.nextUrl.origin;
  const redirectBase = `${appUrl}/settings/calendar`;

  const code = req.nextUrl.searchParams.get("code");
  const stateToken = req.nextUrl.searchParams.get("state");
  const error = req.nextUrl.searchParams.get("error");

  if (error || !code || !stateToken) {
    return NextResponse.redirect(`${redirectBase}?error=google_oauth_denied`);
  }

  const stateSecret = getSigningSecret("CALENDAR_OAUTH_STATE_SECRET");
  if (!stateSecret) {
    return NextResponse.redirect(
      `${redirectBase}?error=oauth_state_not_configured`,
    );
  }

  const statePayload = verifySignedToken(stateToken, stateSecret);
  const userId =
    typeof statePayload?.userId === "string" ? statePayload.userId : null;
  const provider =
    typeof statePayload?.provider === "string" ? statePayload.provider : null;
  const credentialClientId =
    typeof statePayload?.credentialClientId === "string"
      ? statePayload.credentialClientId
      : null;

  const UUID_REGEX =
    /^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i;
  if (!userId || !UUID_REGEX.test(userId) || provider !== "google") {
    return NextResponse.redirect(`${redirectBase}?error=invalid_state`);
  }

  const credentials = await resolveCalendarOAuthCredentialsForUser(
    userId,
    "google",
    credentialClientId,
  );
  if (!hasCompleteCalendarOAuthCredentials(credentials)) {
    return NextResponse.redirect(`${redirectBase}?error=google_not_configured`);
  }

  const redirectUri = `${appUrl}/api/calendar/callback/google`;

  try {
    // Exchange code for tokens
    const tokenRes = await fetch("https://oauth2.googleapis.com/token", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: new URLSearchParams({
        code,
        client_id: credentials.client_id ?? "",
        client_secret: credentials.client_secret ?? "",
        redirect_uri: redirectUri,
        grant_type: "authorization_code",
      }),
    });

    const tokens = await tokenRes.json();
    if (!tokens.access_token) {
      console.error(
        "[calendar/callback/google] Token exchange failed:",
        tokens,
      );
      return NextResponse.redirect(
        `${redirectBase}?error=token_exchange_failed`,
      );
    }

    // Fetch the user's primary calendar name
    const calRes = await fetch(
      "https://www.googleapis.com/calendar/v3/calendars/primary",
      {
        headers: { Authorization: `Bearer ${tokens.access_token}` },
      },
    );
    const calData = await calRes.json();
    const calendarName = calData.summary ?? "Google Calendar";

    const tokenExpiry = tokens.expires_in
      ? new Date(Date.now() + tokens.expires_in * 1000)
      : null;

    // Upsert the connection (one per user per provider)
    const existing = await db
      .select({ id: calendarConnections.id })
      .from(calendarConnections)
      .where(
        and(
          eq(calendarConnections.userId, userId),
          eq(calendarConnections.provider, "google"),
        ),
      )
      .limit(1);

    if (existing.length > 0) {
      await db
        .update(calendarConnections)
        .set({
          encryptedAccessToken: encrypt(tokens.access_token),
          encryptedRefreshToken: tokens.refresh_token
            ? encrypt(tokens.refresh_token)
            : undefined,
          tokenExpiry,
          calendarName,
          isActive: true,
          updatedAt: new Date(),
        })
        .where(eq(calendarConnections.id, existing[0].id));
    } else {
      await db.insert(calendarConnections).values({
        userId,
        provider: "google",
        calendarId: "primary",
        calendarName,
        encryptedAccessToken: encrypt(tokens.access_token),
        encryptedRefreshToken: tokens.refresh_token
          ? encrypt(tokens.refresh_token)
          : null,
        tokenExpiry,
        isActive: true,
      });
    }

    return NextResponse.redirect(`${redirectBase}?connected=google`);
  } catch (err) {
    console.error("[calendar/callback/google] Unexpected error:", err);
    return NextResponse.redirect(`${redirectBase}?error=unexpected`);
  }
}
