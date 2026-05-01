import { NextResponse } from "next/server";

/**
 * Redirect back to Calendar settings with an error query param (browser-friendly).
 * Used when OAuth prerequisites are missing instead of returning JSON from GET /api/calendar/connect/*.
 */
export function redirectToCalendarSettings(errorCode: string): NextResponse {
  const appUrl = process.env.NEXT_PUBLIC_APP_URL ?? "http://localhost:3000";
  return NextResponse.redirect(
    `${appUrl}/settings/calendar?error=${encodeURIComponent(errorCode)}`,
  );
}

export function redirectToLogin(): NextResponse {
  const appUrl = process.env.NEXT_PUBLIC_APP_URL ?? "http://localhost:3000";
  return NextResponse.redirect(`${appUrl}/login`);
}
