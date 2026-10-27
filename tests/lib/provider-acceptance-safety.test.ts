import { describe, expect, it } from "vitest";
import {
  isLocalOpenRouterAcceptance,
  isLocalProviderAcceptance,
} from "@/lib/staging/provider-acceptance.mjs";
const local = {
  STAGING_PROVIDER_ACCEPTANCE: "openrouter",
  NEXT_PUBLIC_APP_URL: "http://127.0.0.1:3101",
  NEXT_PUBLIC_SITE_URL: "http://127.0.0.1:3101",
  NEXT_PUBLIC_SUPABASE_URL: "http://127.0.0.1:55321",
  DATABASE_URL: "postgres://fixture@127.0.0.1:55322/postgres",
};
describe("real provider acceptance boundary", () => {
  it("permits Stripe sandbox with synthetic local services only", () =>
    expect(
      isLocalProviderAcceptance({
        ...local,
        STAGING_PROVIDER_ACCEPTANCE: "stripe_sandbox",
        STRIPE_SECRET_KEY: "sk_test_fixture",
      }),
    ).toBe(true));
  it("refuses a live Stripe key in provider acceptance", () =>
    expect(() =>
      isLocalProviderAcceptance({
        ...local,
        STAGING_PROVIDER_ACCEPTANCE: "stripe_sandbox",
        STRIPE_SECRET_KEY: "sk_live_fixture",
      }),
    ).toThrow());
  it("refuses mixing AI credentials into Stripe acceptance", () =>
    expect(() =>
      isLocalProviderAcceptance({
        ...local,
        STAGING_PROVIDER_ACCEPTANCE: "stripe_sandbox",
        STRIPE_SECRET_KEY: "sk_test_fixture",
        OPENROUTER_API_KEY: "fixture",
      }),
    ).toThrow());
  it("does not activate in normal production", () =>
    expect(isLocalOpenRouterAcceptance({})).toBe(false));
  it("permits the separately configured synthetic local environment", () =>
    expect(isLocalOpenRouterAcceptance(local)).toBe(true));
  it.each(["NEXT_PUBLIC_APP_URL", "NEXT_PUBLIC_SUPABASE_URL", "DATABASE_URL"])(
    "refuses external %s",
    (key) =>
      expect(() =>
        isLocalOpenRouterAcceptance({
          ...local,
          [key]: "https://production.example.com",
        }),
      ).toThrow(),
  );
  it("never weakens isolated simulator mode", () =>
    expect(() =>
      isLocalOpenRouterAcceptance({ ...local, STAGING_ISOLATED: "1" }),
    ).toThrow());
  it.each([
    "OPENAI_API_KEY",
    "RESEND_API_KEY",
    "MICROSOFT_EMAIL_CLIENT_SECRET",
  ])("rejects unrelated external credentials %s", (key) =>
    expect(() =>
      isLocalOpenRouterAcceptance({ ...local, [key]: "fixture" }),
    ).toThrow(),
  );
});
