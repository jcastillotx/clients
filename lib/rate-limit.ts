/** Shared OpenShip counters in production; local memory only when unconfigured. */

import { Ratelimit, type Duration } from "@upstash/ratelimit";
import { Redis } from "@upstash/redis";

interface RateLimitEntry {
  count: number;
  resetAt: number;
}

const memoryStore = new Map<string, RateLimitEntry>();

// Purge expired in-memory entries every 5 minutes
if (typeof setInterval !== "undefined") {
  setInterval(
    () => {
      const now = Date.now();
      for (const [key, entry] of memoryStore.entries()) {
        if (entry.resetAt < now) memoryStore.delete(key);
      }
    },
    5 * 60 * 1000,
  );
}

export interface RateLimitConfig {
  limit: number;
  windowMs: number;
}

export interface RateLimitResult {
  success: boolean;
  remaining: number;
  resetAt: number;
}

let upstashRedis: Redis | null = null;
const upstashLimiters = new Map<string, Ratelimit>();

export async function checkSharedRateLimitHealth(): Promise<boolean> {
  if (
    !process.env.SHARED_RATE_LIMIT_URL &&
    process.env.SHARED_RATE_LIMIT_REQUIRED !== "1"
  )
    return true;
  if (
    !process.env.SHARED_RATE_LIMIT_URL ||
    !process.env.SHARED_RATE_LIMIT_TOKEN
  )
    return false;
  try {
    const response = await fetch(
      new URL("/limit", process.env.SHARED_RATE_LIMIT_URL),
      {
        method: "POST",
        headers: {
          Authorization: `Bearer ${process.env.SHARED_RATE_LIMIT_TOKEN}`,
          "Content-Type": "application/json",
        },
        body: JSON.stringify({ key: "0".repeat(64), limit: 1, windowMs: 1000 }),
        cache: "no-store",
        signal: AbortSignal.timeout(2000),
      },
    );
    return response.ok;
  } catch {
    return false;
  }
}

function isUpstashConfigured(): boolean {
  return Boolean(
    process.env.UPSTASH_REDIS_REST_URL && process.env.UPSTASH_REDIS_REST_TOKEN,
  );
}

function getUpstashRedis(): Redis {
  if (!upstashRedis) {
    upstashRedis = new Redis({
      url: process.env.UPSTASH_REDIS_REST_URL!,
      token: process.env.UPSTASH_REDIS_REST_TOKEN!,
    });
  }
  return upstashRedis;
}

function windowMsToDuration(windowMs: number): Duration {
  if (windowMs >= 60_000 && windowMs % 60_000 === 0) {
    return `${windowMs / 60_000} m`;
  }
  return `${Math.max(1, Math.ceil(windowMs / 1000))} s`;
}

function getUpstashLimiter(prefix: string, config: RateLimitConfig): Ratelimit {
  const cacheKey = `${prefix}:${config.limit}:${config.windowMs}`;
  const existing = upstashLimiters.get(cacheKey);
  if (existing) {
    return existing;
  }

  const limiter = new Ratelimit({
    redis: getUpstashRedis(),
    limiter: Ratelimit.slidingWindow(
      config.limit,
      windowMsToDuration(config.windowMs),
    ),
    prefix: `kre8iv:${prefix}`,
    analytics: true,
  });

  upstashLimiters.set(cacheKey, limiter);
  return limiter;
}

function rateLimitInMemory(
  identifier: string,
  config: RateLimitConfig,
): RateLimitResult {
  const now = Date.now();
  const existing = memoryStore.get(identifier);

  if (!existing || existing.resetAt < now) {
    const entry: RateLimitEntry = {
      count: 1,
      resetAt: now + config.windowMs,
    };
    memoryStore.set(identifier, entry);
    return {
      success: true,
      remaining: config.limit - 1,
      resetAt: entry.resetAt,
    };
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
 * Check rate limit for the given identifier (e.g. IP address or user ID).
 */
export async function rateLimit(
  identifier: string,
  config: RateLimitConfig,
  prefix = "default",
): Promise<RateLimitResult> {
  if (
    process.env.SHARED_RATE_LIMIT_URL ||
    process.env.SHARED_RATE_LIMIT_REQUIRED === "1"
  ) {
    const denied = {
      success: false,
      remaining: 0,
      resetAt: Date.now() + 10_000,
    };
    const base = process.env.SHARED_RATE_LIMIT_URL;
    const token = process.env.SHARED_RATE_LIMIT_TOKEN;
    if (!base || !token) return denied;
    try {
      const rawKey = `${prefix}:${config.limit}:${config.windowMs}:${identifier}`;
      const digest = await crypto.subtle.digest(
        "SHA-256",
        new TextEncoder().encode(rawKey),
      );
      const key = Array.from(new Uint8Array(digest), (byte) =>
        byte.toString(16).padStart(2, "0"),
      ).join("");
      const response = await fetch(new URL("/limit", base), {
        method: "POST",
        headers: {
          Authorization: `Bearer ${token}`,
          "Content-Type": "application/json",
        },
        body: JSON.stringify({ key, ...config }),
        cache: "no-store",
        signal: AbortSignal.timeout(2000),
      });
      if (!response.ok) throw new Error("Counter unavailable");
      const result: unknown = await response.json();
      if (
        !result ||
        typeof result !== "object" ||
        !("success" in result) ||
        typeof result.success !== "boolean" ||
        !("remaining" in result) ||
        typeof result.remaining !== "number" ||
        !Number.isInteger(result.remaining) ||
        result.remaining < 0 ||
        result.remaining > config.limit ||
        !("resetAt" in result) ||
        typeof result.resetAt !== "number" ||
        !Number.isFinite(result.resetAt) ||
        result.resetAt <= 0
      )
        throw new Error("Invalid counter response");
      return {
        success: result.success,
        remaining: result.remaining,
        resetAt: result.resetAt,
      };
    } catch {
      console.error("Shared rate limiter unavailable; denying request");
      return denied;
    }
  }
  if (isUpstashConfigured()) {
    const limiter = getUpstashLimiter(prefix, config);
    const result = await limiter.limit(identifier);
    return {
      success: result.success,
      remaining: result.remaining,
      resetAt: result.reset,
    };
  }

  return rateLimitInMemory(`${prefix}:${identifier}`, config);
}

export type LimiterFn = (id: string) => Promise<RateLimitResult>;

function createLimiter(prefix: string, config: RateLimitConfig): LimiterFn {
  return (id: string) => rateLimit(id, config, prefix);
}

/** Pre-configured limiters for common use cases. */
export const limiters = {
  auth: createLimiter("auth", { limit: 10, windowMs: 60_000 }),
  api: createLimiter("api", { limit: 100, windowMs: 60_000 }),
  sensitive: createLimiter("sensitive", { limit: 5, windowMs: 15 * 60_000 }),
  publicIntake: createLimiter("public-intake", {
    limit: 5,
    windowMs: 15 * 60_000,
  }),
  publicPayment: createLimiter("public-payment", {
    limit: 10,
    windowMs: 15 * 60_000,
  }),
};

export const rateLimitLimits = {
  auth: 10,
  api: 100,
  sensitive: 5,
  publicIntake: 5,
  publicPayment: 10,
} as const;

/** Whether a shared counter backend is configured. */
export function isDistributedRateLimitEnabled(): boolean {
  return (
    Boolean(
      process.env.SHARED_RATE_LIMIT_URL && process.env.SHARED_RATE_LIMIT_TOKEN,
    ) || isUpstashConfigured()
  );
}

/**
 * Extract the real client IP from Next.js request headers.
 */
export function getClientIp(request: Request): string {
  const forwarded =
    request.headers.get("x-forwarded-for") ??
    request.headers.get("cf-connecting-ip") ??
    request.headers.get("x-real-ip") ??
    "unknown";
  return forwarded.split(",")[0].trim();
}

/** @internal Reset in-memory store for tests. */
export function resetMemoryRateLimitStoreForTests(): void {
  memoryStore.clear();
}

/** @internal Reset Upstash client cache for tests. */
export function resetUpstashRateLimitCacheForTests(): void {
  upstashRedis = null;
  upstashLimiters.clear();
}
