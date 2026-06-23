import { afterEach, describe, expect, it, vi } from "vitest";
import {
  hasAiProviderIntegration,
  hasEmailDelivery,
  isAdsPlatformSyncEnabled,
  isAiEmailPreviewMode,
  isSocialOAuthEnabled,
} from "@/lib/features/incomplete-features";

describe("incomplete feature flags", () => {
  afterEach(() => {
    vi.unstubAllEnvs();
  });

  it("detects AI preview mode when no provider key is configured", () => {
    vi.stubEnv("OPENAI_API_KEY", "");
    vi.stubEnv("ANTHROPIC_API_KEY", "");

    expect(hasAiProviderIntegration()).toBe(false);
    expect(isAiEmailPreviewMode()).toBe(true);
  });

  it("enables AI mode when OpenAI is configured", () => {
    vi.stubEnv("OPENAI_API_KEY", "sk-test");

    expect(hasAiProviderIntegration()).toBe(true);
    expect(isAiEmailPreviewMode()).toBe(false);
  });

  it("defaults optional integrations to disabled", () => {
    expect(isSocialOAuthEnabled()).toBe(false);
    expect(isAdsPlatformSyncEnabled()).toBe(false);
  });

  it("allows enabling optional integrations via env flags", () => {
    vi.stubEnv("FEATURE_SOCIAL_OAUTH", "true");
    vi.stubEnv("FEATURE_ADS_PLATFORM_SYNC", "1");

    expect(isSocialOAuthEnabled()).toBe(true);
    expect(isAdsPlatformSyncEnabled()).toBe(true);
  });

  it("detects email delivery configuration", () => {
    vi.stubEnv("RESEND_API_KEY", "");
    expect(hasEmailDelivery()).toBe(false);

    vi.stubEnv("RESEND_API_KEY", "re_test");
    expect(hasEmailDelivery()).toBe(true);
  });
});
