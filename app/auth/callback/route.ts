import { NextResponse } from "next/server";

/**
 * Legacy callback route - redirects to the canonical /auth/confirm handler.
 * Preserves all query params so code/token_hash/type/next are forwarded.
 * This avoids duplicate token exchange logic and potential race conditions.
 */
export async function GET(request: Request) {
  const requestUrl = new URL(request.url);
  const confirmUrl = new URL("/auth/confirm", requestUrl.origin);
  requestUrl.searchParams.forEach((value, key) => {
    confirmUrl.searchParams.set(key, value);
  });
  return NextResponse.redirect(confirmUrl);
}
