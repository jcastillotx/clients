import { NextRequest } from "next/server";
import {
  apiError,
  apiSuccess,
  apiUnauthorized,
} from "@/lib/api/response";

import { createClient } from "@/lib/supabase/server";
import { db } from "@/lib/db";
import { calendarConnections } from "@/lib/db/schema/calendar-integrations";
import { decrypt, encrypt } from "@/lib/encryption";
import { eq, and, inArray } from "drizzle-orm";
import type { UserAvailability } from "@/lib/db/schema/calendar-integrations";

/**
 * GET /api/calendar/availability?userIds=id1,id2&start=ISO&end=ISO
 * Returns free/busy availability for the requested users and time range.
 *
 * Calls Google Calendar Free/Busy API and Microsoft Graph calendarView.
 * Handles token refresh automatically.
 */
export async function GET(request: NextRequest) {
  const supabase = await createClient();
  const {
    data: { user },
  } = await supabase.auth.getUser();

  if (!user) return apiUnauthorized(request);

  const userIdsParam = request.nextUrl.searchParams.get("userIds");
  const start = request.nextUrl.searchParams.get("start");
  const end = request.nextUrl.searchParams.get("end");

  if (!userIdsParam || !start || !end) {
    return apiError(request, {
      status: 400,
      code: "BAD_REQUEST",
      message: "userIds, start, and end are required",
    });
  }

  const startDate = new Date(start);
  const endDate = new Date(end);
  if (isNaN(startDate.getTime()) || isNaN(endDate.getTime()) || startDate >= endDate) {
    return apiError(request, { status: 400, code: "BAD_REQUEST", message: "Invalid time range" });
  }

  const userIds = userIdsParam
    .split(",")
    .map((id) => id.trim())
    .filter(Boolean)
    .slice(0, 20); // cap at 20 users

  if (userIds.length === 0) {
    return apiSuccess(request, [], { extra: { availability: [] } });
  }

  // Fetch calendar connections for all requested users
  const connections = await db
    .select()
    .from(calendarConnections)
    .where(and(inArray(calendarConnections.userId, userIds), eq(calendarConnections.isActive, true)));

  // Fetch user names from DB
  const { data: userRows } = await supabase
    .from("users")
    .select("id, name")
    .in("id", userIds)
    .is("deleted_at", null);

  const userNameMap = new Map<string, string>(
    (userRows ?? []).map((u: { id: string; name: string }) => [u.id, u.name]),
  );

  // Build a map of userId → connections
  const connectionsByUser = new Map<string, typeof connections>();
  for (const conn of connections) {
    if (!connectionsByUser.has(conn.userId)) connectionsByUser.set(conn.userId, []);
    connectionsByUser.get(conn.userId)!.push(conn);
  }

  const results: UserAvailability[] = await Promise.all(
    userIds.map(async (uid) => {
      const name = userNameMap.get(uid) ?? uid;
      const userConnections = connectionsByUser.get(uid) ?? [];

      if (userConnections.length === 0) {
        return { userId: uid, name, status: "no_calendar" as const };
      }

      // Check each connection; return busy if any shows busy
      const busyBlocks: Array<{ start: string; end: string }> = [];

      for (const conn of userConnections) {
        try {
          const accessToken = await getValidAccessToken(conn);
          if (!accessToken) continue;

          if (conn.provider === "google") {
            const blocks = await fetchGoogleBusy(accessToken, start, end);
            busyBlocks.push(...blocks);
          } else if (conn.provider === "microsoft") {
            const blocks = await fetchMicrosoftBusy(accessToken, start, end);
            busyBlocks.push(...blocks);
          }
        } catch (err) {
          console.error(`[calendar/availability] Error checking ${conn.provider} for user ${uid}:`, err);
        }
      }

      if (busyBlocks.length > 0) {
        return { userId: uid, name, status: "busy" as const, busyBlocks };
      }

      return { userId: uid, name, status: "free" as const };
    }),
  );

  return apiSuccess(request, results, { extra: { availability: results } });
}

// ---------------------------------------------------------------------------
// Token refresh
// ---------------------------------------------------------------------------

async function getValidAccessToken(
  conn: typeof calendarConnections.$inferSelect,
): Promise<string | null> {
  const isExpired = conn.tokenExpiry ? conn.tokenExpiry <= new Date(Date.now() + 60_000) : false;

  if (!isExpired) {
    try {
      return decrypt(conn.encryptedAccessToken);
    } catch {
      return null;
    }
  }

  // Try to refresh
  if (!conn.encryptedRefreshToken) return null;

  try {
    const refreshToken = decrypt(conn.encryptedRefreshToken);
    const newTokens =
      conn.provider === "google"
        ? await refreshGoogleToken(refreshToken)
        : await refreshMicrosoftToken(refreshToken);

    if (!newTokens?.access_token) return null;

    const tokenExpiry = newTokens.expires_in
      ? new Date(Date.now() + newTokens.expires_in * 1000)
      : null;

    // Persist refreshed tokens
    await db
      .update(calendarConnections)
      .set({
        encryptedAccessToken: encrypt(newTokens.access_token),
        encryptedRefreshToken: newTokens.refresh_token
          ? encrypt(newTokens.refresh_token)
          : conn.encryptedRefreshToken,
        tokenExpiry,
        updatedAt: new Date(),
      })
      .where(eq(calendarConnections.id, conn.id));

    return newTokens.access_token;
  } catch (err) {
    console.error("[calendar/availability] Token refresh failed:", err);
    return null;
  }
}

async function refreshGoogleToken(refreshToken: string) {
  const res = await fetch("https://oauth2.googleapis.com/token", {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: new URLSearchParams({
      client_id: process.env.GOOGLE_CALENDAR_CLIENT_ID ?? "",
      client_secret: process.env.GOOGLE_CALENDAR_CLIENT_SECRET ?? "",
      refresh_token: refreshToken,
      grant_type: "refresh_token",
    }),
  });
  return res.json();
}

async function refreshMicrosoftToken(refreshToken: string) {
  const appUrl = process.env.NEXT_PUBLIC_APP_URL ?? "http://localhost:3000";
  const res = await fetch("https://login.microsoftonline.com/common/oauth2/v2.0/token", {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: new URLSearchParams({
      client_id: process.env.MICROSOFT_CALENDAR_CLIENT_ID ?? "",
      client_secret: process.env.MICROSOFT_CALENDAR_CLIENT_SECRET ?? "",
      redirect_uri: `${appUrl}/api/calendar/callback/microsoft`,
      refresh_token: refreshToken,
      grant_type: "refresh_token",
      scope: "Calendars.Read offline_access User.Read",
    }),
  });
  return res.json();
}

// ---------------------------------------------------------------------------
// Provider-specific free/busy queries
// ---------------------------------------------------------------------------

async function fetchGoogleBusy(
  accessToken: string,
  start: string,
  end: string,
): Promise<Array<{ start: string; end: string }>> {
  const res = await fetch("https://www.googleapis.com/calendar/v3/freeBusy", {
    method: "POST",
    headers: {
      Authorization: `Bearer ${accessToken}`,
      "Content-Type": "application/json",
    },
    body: JSON.stringify({
      timeMin: start,
      timeMax: end,
      items: [{ id: "primary" }],
    }),
  });

  if (!res.ok) return [];

  const data = await res.json();
  const busy: Array<{ start: string; end: string }> = data?.calendars?.primary?.busy ?? [];
  return busy;
}

async function fetchMicrosoftBusy(
  accessToken: string,
  start: string,
  end: string,
): Promise<Array<{ start: string; end: string }>> {
  const startEncoded = encodeURIComponent(start);
  const endEncoded = encodeURIComponent(end);

  const res = await fetch(
    `https://graph.microsoft.com/v1.0/me/calendarView?startDateTime=${startEncoded}&endDateTime=${endEncoded}&$select=subject,start,end,showAs&$filter=showAs ne 'free'`,
    {
      headers: {
        Authorization: `Bearer ${accessToken}`,
        "Content-Type": "application/json",
      },
    },
  );

  if (!res.ok) return [];

  const data = await res.json();
  const events: Array<{ start: { dateTime: string }; end: { dateTime: string } }> =
    data?.value ?? [];

  return events.map((e) => ({
    start: e.start.dateTime,
    end: e.end.dateTime,
  }));
}
