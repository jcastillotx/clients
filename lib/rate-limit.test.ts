import { afterEach, describe, expect, it } from "vitest";
import {
  limiters,
  rateLimit,
  resetMemoryRateLimitStoreForTests,
  resetUpstashRateLimitCacheForTests,
} from "@/lib/rate-limit";

describe("rateLimit (in-memory)", () => {
  afterEach(() => {
    resetMemoryRateLimitStoreForTests();
    resetUpstashRateLimitCacheForTests();
  });

  it("allows requests under the limit", async () => {
    const config = { limit: 3, windowMs: 60_000 };

    const first = await rateLimit("test-ip", config, "test");
    const second = await rateLimit("test-ip", config, "test");

    expect(first.success).toBe(true);
    expect(second.success).toBe(true);
    expect(second.remaining).toBe(1);
  });

  it("blocks requests over the limit", async () => {
    const config = { limit: 2, windowMs: 60_000 };

    await rateLimit("blocked-ip", config, "test");
    await rateLimit("blocked-ip", config, "test");
    const third = await rateLimit("blocked-ip", config, "test");

    expect(third.success).toBe(false);
    expect(third.remaining).toBe(0);
  });

  it("publicIntake limiter enforces 5 per window", async () => {
    for (let i = 0; i < 5; i++) {
      const result = await limiters.publicIntake("intake-ip");
      expect(result.success).toBe(true);
    }

    const blocked = await limiters.publicIntake("intake-ip");
    expect(blocked.success).toBe(false);
  });
});
