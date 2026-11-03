import { afterEach, describe, expect, it, vi } from "vitest";
import { createContentSecurityPolicy } from "@/lib/auth/content-security-policy";
import {
  getClientIp,
  checkSharedRateLimitHealth,
  rateLimit,
} from "@/lib/rate-limit";

afterEach(() => {
  vi.unstubAllEnvs();
  vi.unstubAllGlobals();
  vi.restoreAllMocks();
});

describe("script policy", () => {
  it("allows nonced scripts without arbitrary inline scripts or eval", () => {
    const csp = createContentSecurityPolicy("response-nonce");
    const script = csp
      .split(";")
      .find((part) => part.trim().startsWith("script-src "))!;
    expect(script).toContain("'nonce-response-nonce' 'strict-dynamic'");
    expect(script).not.toMatch(/unsafe-inline|unsafe-eval/);
    expect(csp).toContain("script-src-attr 'none'");
    expect(csp).toContain("frame-ancestors 'none'");
  });
  it("keeps development exceptions explicit", () => {
    expect(createContentSecurityPolicy("nonce", false, true)).toContain(
      "'unsafe-eval'",
    );
    expect(createContentSecurityPolicy("nonce", true)).not.toContain(
      "upgrade-insecure-requests",
    );
  });
});

describe("shared rate limiting", () => {
  function configure() {
    vi.stubEnv("SHARED_RATE_LIMIT_REQUIRED", "1");
    vi.stubEnv("SHARED_RATE_LIMIT_URL", "http://counter:8080");
    vi.stubEnv("SHARED_RATE_LIMIT_TOKEN", "test-token");
  }
  it("hashes tenant identifiers and namespaces before sharing counters", async () => {
    configure();
    const fetcher = vi.fn().mockResolvedValue(
      new Response(
        JSON.stringify({
          success: true,
          remaining: 4,
          resetAt: Date.now() + 60000,
        }),
      ),
    );
    vi.stubGlobal("fetch", fetcher);
    expect(
      (await rateLimit("private-client", { limit: 5, windowMs: 60000 }, "auth"))
        .success,
    ).toBe(true);
    const request = JSON.parse(fetcher.mock.calls[0][1].body);
    expect(request.key).toMatch(/^[a-f0-9]{64}$/);
    expect(fetcher.mock.calls[0][1].body).not.toContain("private-client");
    await rateLimit("private-client", { limit: 5, windowMs: 60000 }, "payment");
    expect(JSON.parse(fetcher.mock.calls[1][1].body).key).not.toBe(request.key);
  });
  it("fails closed instead of using process-local counters during an outage", async () => {
    configure();
    vi.spyOn(console, "error").mockImplementation(() => {});
    vi.stubGlobal("fetch", vi.fn().mockRejectedValue(new Error("unavailable")));
    expect(
      (await rateLimit("client", { limit: 5, windowMs: 60000 })).success,
    ).toBe(false);
    expect(await checkSharedRateLimitHealth()).toBe(false);
  });
  it("rejects malformed shared responses and missing required credentials", async () => {
    configure();
    vi.spyOn(console, "error").mockImplementation(() => {});
    vi.stubGlobal(
      "fetch",
      vi
        .fn()
        .mockResolvedValue(
          new Response(
            JSON.stringify({ success: true, remaining: -1, resetAt: 100 }),
          ),
        ),
    );
    expect(
      (await rateLimit("client", { limit: 5, windowMs: 60000 })).success,
    ).toBe(false);
    vi.stubEnv("SHARED_RATE_LIMIT_TOKEN", "");
    expect(await checkSharedRateLimitHealth()).toBe(false);
    expect(
      (await rateLimit("client", { limit: 5, windowMs: 60000 })).success,
    ).toBe(false);
  });
});

describe("trusted client address", () => {
  it("uses the edge-sanitized address rather than a caller-controlled forwarded prefix", () => {
    for (const forged of ["192.0.2.1", "192.0.2.2", "fake-client"]) {
      const request = new Request("https://portal.test/login", {
        headers: {
          "x-real-ip": "203.0.113.10",
          "x-forwarded-for": `${forged}, 203.0.113.10`,
          "cf-connecting-ip": "192.0.2.99",
        },
      });
      expect(getClientIp(request)).toBe("203.0.113.10");
    }
  });
  it("does not select a prepended address in the forwarded-only fallback", () => {
    expect(
      getClientIp(
        new Request("https://portal.test", {
          headers: { "x-forwarded-for": "forged, 203.0.113.10" },
        }),
      ),
    ).toBe("203.0.113.10");
    expect(getClientIp(new Request("https://portal.test"))).toBe("unknown");
  });
});
