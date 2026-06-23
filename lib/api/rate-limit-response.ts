import { NextResponse } from "next/server";
import type { RateLimitResult } from "@/lib/rate-limit";
import { apiError } from "@/lib/api/response";

export function rateLimitExceededResponse(
  request: Request,
  result: RateLimitResult,
  limit: number,
): NextResponse {
  const retryAfter = Math.max(
    1,
    Math.ceil((result.resetAt - Date.now()) / 1000),
  );

  const response = apiError(request, {
    status: 429,
    code: "RATE_LIMITED",
    message: "Too many requests. Please try again later.",
    headers: {
      "Retry-After": String(retryAfter),
      "X-RateLimit-Limit": String(limit),
      "X-RateLimit-Remaining": "0",
      "X-RateLimit-Reset": String(result.resetAt),
    },
  });

  return response;
}
