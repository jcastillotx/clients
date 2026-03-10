import { NextRequest, NextResponse } from "next/server";
import { db } from "@/lib/db";
import { calendarConnections } from "@/lib/db/schema/calendar-integrations";
import { encrypt } from "@/lib/encryption";
import { eq, and } from "drizzle-orm";

/**
 * GET /api/calendar/callback/microsoft
 * Handles the Microsoft Calendar OAuth callback.
 * Exchanges the authorization code for tokens and persists the connection.
 *
 * Required env vars:
 *   MICROSOFT_CALENDAR_CLIENT_ID
 *   MICROSOFT_CALENDAR_CLIENT_SECRET
 *   NEXT_PUBLIC_APP_URL
 */
export async function GET(req: NextRequest) {
  const appUrl = process.env.NEXT_PUBLIC_APP_URL ?? "http://localhost:3000";
  const redirectBase = `${appUrl}/settings/calendar`;

  const code = req.nextUrl.searchParams.get("code");
  const userId = req.nextUrl.searchParams.get("state");
  const error = req.nextUrl.searchParams.get("error");

  if (error || !code || !userId) {
    return NextResponse.redirect(`${redirectBase}?error=microsoft_oauth_denied`);
  }

  const UUID_REGEX = /^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i;
  if (!UUID_REGEX.test(userId)) {
    return NextResponse.json({ error: "Invalid state parameter" }, { status: 400 });
  }

  const clientId = process.env.MICROSOFT_CALENDAR_CLIENT_ID;
  const clientSecret = process.env.MICROSOFT_CALENDAR_CLIENT_SECRET;
  if (!clientId || !clientSecret) {
    return NextResponse.redirect(`${redirectBase}?error=not_configured`);
  }

  const redirectUri = `${appUrl}/api/calendar/callback/microsoft`;

  try {
    // Exchange code for tokens
    const tokenRes = await fetch("https://login.microsoftonline.com/common/oauth2/v2.0/token", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: new URLSearchParams({
        code,
        client_id: clientId,
        client_secret: clientSecret,
        redirect_uri: redirectUri,
        grant_type: "authorization_code",
        scope: "Calendars.Read offline_access User.Read",
      }),
    });

    const tokens = await tokenRes.json();
    if (!tokens.access_token) {
      console.error("[calendar/callback/microsoft] Token exchange failed:", tokens);
      return NextResponse.redirect(`${redirectBase}?error=token_exchange_failed`);
    }

    // Fetch the user's primary calendar name via Microsoft Graph
    const calRes = await fetch("https://graph.microsoft.com/v1.0/me/calendar", {
      headers: { Authorization: `Bearer ${tokens.access_token}` },
    });
    const calData = await calRes.json();
    const calendarName = calData.name ?? "Microsoft Calendar";

    const tokenExpiry = tokens.expires_in
      ? new Date(Date.now() + tokens.expires_in * 1000)
      : null;

    // Upsert the connection (one per user per provider)
    const existing = await db
      .select({ id: calendarConnections.id })
      .from(calendarConnections)
      .where(and(eq(calendarConnections.userId, userId), eq(calendarConnections.provider, "microsoft")))
      .limit(1);

    if (existing.length > 0) {
      await db
        .update(calendarConnections)
        .set({
          encryptedAccessToken: encrypt(tokens.access_token),
          encryptedRefreshToken: tokens.refresh_token ? encrypt(tokens.refresh_token) : undefined,
          tokenExpiry,
          calendarName,
          isActive: true,
          updatedAt: new Date(),
        })
        .where(eq(calendarConnections.id, existing[0].id));
    } else {
      await db.insert(calendarConnections).values({
        userId,
        provider: "microsoft",
        calendarId: "primary",
        calendarName,
        encryptedAccessToken: encrypt(tokens.access_token),
        encryptedRefreshToken: tokens.refresh_token ? encrypt(tokens.refresh_token) : null,
        tokenExpiry,
        isActive: true,
      });
    }

    return NextResponse.redirect(`${redirectBase}?connected=microsoft`);
  } catch (err) {
    console.error("[calendar/callback/microsoft] Unexpected error:", err);
    return NextResponse.redirect(`${redirectBase}?error=unexpected`);
  }
}
