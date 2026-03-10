/**
 * In-memory rate limiter with sliding window algorithm.
 *
 * Works out of the box with no external dependencies.
 * For distributed/production environments, swap the store for
 * Upstash Redis by setting UPSTASH_REDIS_REST_URL and
 * UPSTASH_REDIS_REST_TOKEN, then replace the Map with:
 *   import { Ratelimit } from "@upstash/ratelimit"
 *   import { Redis } from "@upstash/redis"
 */

interface RateLimitEntry {
  count: number;
  resetAt: number;
}

const store = new Map<string, RateLimitEntry>();

// Purge expired entries every 5 minutes to prevent unbounded memory growth
setInterval(
  () => {
    const now = Date.now();
    for (const [key, entry] of store.entries()) {
      if (entry.resetAt < now) store.delete(key);
    }
  },
  5 * 60 * 1000
);

export interface RateLimitConfig {
  /** Maximum requests allowed in the window */
  limit: number;
  /** Window duration in milliseconds */
  windowMs: number;
}

export interface RateLimitResult {
  success: boolean;
  /** Requests remaining in the current window */
  remaining: number;
  /** Epoch ms when the current window resets */
  resetAt: number;
}

/**
 * Check rate limit for the given identifier (e.g. IP address or user ID).
 * Returns `success: true` if the request is within limits.
 */
export function rateLimit(
  identifier: string,
  config: RateLimitConfig
): RateLimitResult {
  const now = Date.now();
  const key = identifier;

  const existing = store.get(key);

  if (!existing || existing.resetAt < now) {
    // New window
    const entry: RateLimitEntry = { count: 1, resetAt: now + config.windowMs };
    store.set(key, entry);
    return { success: true, remaining: config.limit - 1, resetAt: entry.resetAt };
  }

  if (existing.count >= config.limit) {
    return { success: false, remaining: 0, resetAt: existing.resetAt };
  }

  existing.count++;
  return {
    success: true,
    remaining: config.limit - existing.count,
    resetAt: existing.resetAt,
  };
}

/**
 * Pre-configured limiters for common use cases.
 */
export const limiters = {
  /** Auth endpoints: 10 requests per minute per IP */
  auth: (ip: string) => rateLimit(`auth:${ip}`, { limit: 10, windowMs: 60_000 }),

  /** API endpoints: 100 requests per minute per user/IP */
  api: (id: string) => rateLimit(`api:${id}`, { limit: 100, windowMs: 60_000 }),

  /** Password reset / sensitive actions: 5 per 15 minutes */
  sensitive: (id: string) =>
    rateLimit(`sensitive:${id}`, { limit: 5, windowMs: 15 * 60_000 }),
};

/**
 * Extract the real client IP from Next.js request headers.
 * Falls back to a placeholder if behind a proxy without proper headers.
 */
export function getClientIp(request: Request): string {
  const forwarded =
    (request.headers as Headers).get("x-forwarded-for") ??
    (request.headers as Headers).get("cf-connecting-ip") ??
    (request.headers as Headers).get("x-real-ip") ??
    "unknown";
  // x-forwarded-for may be a comma-separated list; take the first (client) IP
  return forwarded.split(",")[0].trim();
}
