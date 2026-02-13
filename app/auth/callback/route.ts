import { NextResponse } from "next/server";
import { getAuthBaseUrl } from "@/lib/supabase/redirect-url";

/**
 * Legacy callback route - redirects to the canonical /auth/confirm handler.
 * Preserves all query params so code/token_hash/type/next are forwarded.
 * This avoids duplicate token exchange logic and potential race conditions.
 */
export async function GET(request: Request) {
  const requestUrl = new URL(request.url);
  // Use the canonical base URL (custom domain) instead of requestUrl.origin
  // which may resolve to an internal *.vercel.app hostname.
  const confirmUrl = new URL("/auth/confirm", getAuthBaseUrl());
  requestUrl.searchParams.forEach((value, key) => {
    confirmUrl.searchParams.set(key, value);
  });
  return NextResponse.redirect(confirmUrl);
}
