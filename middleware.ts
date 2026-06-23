import { NextRequest } from "next/server";
import {
  REQUEST_ID_HEADER,
  resolveRequestId,
  withRequestIdHeader,
} from "@/lib/api/request-context";
import { updateSession } from "@/lib/supabase/middleware";

export async function middleware(request: NextRequest) {
  const requestId = resolveRequestId(request);
  const requestHeaders = new Headers(request.headers);
  requestHeaders.set(REQUEST_ID_HEADER, requestId);

  const forwardedRequest = new NextRequest(request, {
    headers: requestHeaders,
  });

  const response = await updateSession(forwardedRequest);
  withRequestIdHeader(response.headers, requestId);
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
