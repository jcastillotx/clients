import { afterEach, describe, expect, it, vi } from "vitest";
import { isTurnstileEnforced } from "@/lib/turnstile/config";
import { assertTurnstileToken } from "@/lib/turnstile/verify";

describe("turnstile", () => {
  afterEach(() => {
    vi.unstubAllEnvs();
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
});
