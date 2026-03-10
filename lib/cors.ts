/**
 * CORS utility for Next.js API routes.
 *
 * Usage:
 *   import { withCors, corsHeaders } from "@/lib/cors";
 *
 *   // 1. Handle preflight in a route:
 *   export function OPTIONS() {
 *     return new Response(null, { status: 204, headers: corsHeaders() });
 *   }
 *
 *   // 2. Add CORS headers to any response:
 *   return NextResponse.json(data, { headers: corsHeaders(request) });
 */

import { NextRequest, NextResponse } from "next/server";

const ALLOWED_ORIGINS: string[] = [
  process.env.NEXT_PUBLIC_APP_URL ?? "http://localhost:3000",
  process.env.NEXT_PUBLIC_SITE_URL ?? "",
].filter(Boolean);

/**
 * Returns CORS headers for a given request origin.
 * Only allows configured origins; defaults to same-origin if unknown.
 */
export function corsHeaders(request?: NextRequest): Record<string, string> {
  const origin = request?.headers.get("origin") ?? "";
  const allowed = ALLOWED_ORIGINS.includes(origin) ? origin : ALLOWED_ORIGINS[0] ?? "";

  return {
    "Access-Control-Allow-Origin": allowed,
    "Access-Control-Allow-Methods": "GET, POST, PUT, PATCH, DELETE, OPTIONS",
    "Access-Control-Allow-Headers": "Content-Type, Authorization, X-Requested-With",
    "Access-Control-Max-Age": "86400",
    "Vary": "Origin",
  };
}

/**
 * Handle an OPTIONS preflight request for a route.
 */
export function handlePreflight(request: NextRequest): NextResponse {
  return new NextResponse(null, {
    status: 204,
    headers: corsHeaders(request),
  });
}
