import { afterEach, describe, expect, it, vi } from "vitest";
import { isTurnstileEnforced } from "@/lib/turnstile/config";
import { assertTurnstileToken } from "@/lib/turnstile/verify";

describe("turnstile", () => {
  afterEach(() => {
    vi.unstubAllEnvs();
    vi.unstubAllGlobals();
  });

  it("isTurnstileEnforced is false without keys", () => {
    vi.stubEnv("NEXT_PUBLIC_TURNSTILE_SITE_KEY", "");
    vi.stubEnv("TURNSTILE_SECRET_KEY", "");
    expect(isTurnstileEnforced()).toBe(false);
  });

  it("assertTurnstileToken skips verification when not configured", async () => {
    vi.stubEnv("NEXT_PUBLIC_TURNSTILE_SITE_KEY", "");
    vi.stubEnv("TURNSTILE_SECRET_KEY", "");
    await expect(assertTurnstileToken(undefined)).resolves.toEqual({ ok: true });
  });

  it("assertTurnstileToken requires token when configured", async () => {
    vi.stubEnv("NEXT_PUBLIC_TURNSTILE_SITE_KEY", "site-key");
    vi.stubEnv("TURNSTILE_SECRET_KEY", "secret-key");

    await expect(assertTurnstileToken(null)).resolves.toEqual({
      ok: false,
      error: "CAPTCHA verification is required.",
      status: 400,
    });
  });

  it("fails closed in production when CAPTCHA keys are absent", async () => {
    vi.stubEnv("NODE_ENV", "production");
    vi.stubEnv("NEXT_PUBLIC_TURNSTILE_SITE_KEY", "");
    vi.stubEnv("TURNSTILE_SECRET_KEY", "");
    expect(isTurnstileEnforced()).toBe(true);
    expect(await assertTurnstileToken(undefined)).toMatchObject({ ok: false, status: 503 });
  });

  it("rejects invalid tokens after server-side verification", async () => {
    vi.stubEnv("NEXT_PUBLIC_TURNSTILE_SITE_KEY", "site-key");
    vi.stubEnv("TURNSTILE_SECRET_KEY", "secret-key");
    vi.stubGlobal("fetch", vi.fn().mockResolvedValue({ ok: true, json: async () => ({ success: false }) }));
    expect(await assertTurnstileToken("forged-token")).toMatchObject({ ok: false, status: 400 });
  });

  it("accepts tokens approved by the verification provider", async () => {
    vi.stubEnv("NEXT_PUBLIC_TURNSTILE_SITE_KEY", "site-key");
    vi.stubEnv("TURNSTILE_SECRET_KEY", "secret-key");
    vi.stubGlobal("fetch", vi.fn().mockResolvedValue({ ok: true, json: async () => ({ success: true }) }));
    expect(await assertTurnstileToken("verified-token")).toEqual({ ok: true });
  });
});
