import { NextRequest } from "next/server";
import {
  REQUEST_ID_HEADER,
  resolveRequestId,
  withRequestIdHeader,
} from "@/lib/api/request-context";
import { updateSession } from "@/lib/supabase/middleware";
import { createContentSecurityPolicy } from "@/lib/auth/content-security-policy";

export async function middleware(request: NextRequest) {
  const requestId = resolveRequestId(request);
  const requestHeaders = new Headers(request.headers);
  requestHeaders.set(REQUEST_ID_HEADER, requestId);
  const nonce = btoa(
    String.fromCharCode(...crypto.getRandomValues(new Uint8Array(32))),
  );
  const policy = createContentSecurityPolicy(
    nonce,
    process.env.STAGING_ISOLATED === "1" ||
      Boolean(process.env.STAGING_PROVIDER_ACCEPTANCE),
    process.env.NODE_ENV === "development",
  );
  requestHeaders.set("x-nonce", nonce);
  requestHeaders.set("Content-Security-Policy", policy);

  const forwardedRequest = new NextRequest(request, {
    headers: requestHeaders,
  });

  const response = await updateSession(forwardedRequest);
  withRequestIdHeader(response.headers, requestId);
  response.headers.set("Content-Security-Policy", policy);
  // Never cache HTML carrying a per-request script nonce.
  response.headers.set("Cache-Control", "private, no-store");
  return response;
}

export const config = {
  matcher: [
    /*
     * Match all request paths except:
     * - _next/static (static files)
     * - _next/image (image optimization files)
     * - favicon.ico (favicon file)
     * - public folder
     */
    "/((?!_next/static|_next/image|favicon.ico|.*\\.(?:svg|png|jpg|jpeg|gif|webp)$).*)",
  ],
};
