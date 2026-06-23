import { afterEach, describe, expect, it, vi } from "vitest";
import { getSentryEnvironment, isSentryEnabled } from "@/lib/sentry/config";

describe("sentry config", () => {
  afterEach(() => {
    vi.unstubAllEnvs();
  });

  it("isSentryEnabled is false without DSN", () => {
    vi.stubEnv("SENTRY_DSN", "");
    expect(isSentryEnabled()).toBe(false);
  });

  it("isSentryEnabled is true with DSN", () => {
    vi.stubEnv("SENTRY_DSN", "https://example@sentry.io/1");
    expect(isSentryEnabled()).toBe(true);
  });

  it("getSentryEnvironment prefers NEXT_PUBLIC_SENTRY_ENVIRONMENT", () => {
    vi.stubEnv("NEXT_PUBLIC_SENTRY_ENVIRONMENT", "staging");
    vi.stubEnv("VERCEL_ENV", "production");
    expect(getSentryEnvironment()).toBe("staging");
  });
});
