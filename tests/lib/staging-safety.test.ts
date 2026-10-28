import { describe, expect, it } from "vitest";
import { assertIsolatedStaging } from "@/lib/staging/safety";
const local = {
  STAGING_ISOLATED: "1",
  NEXT_PUBLIC_APP_URL: "http://127.0.0.1:3100",
  NEXT_PUBLIC_SITE_URL: "http://127.0.0.1:3100",
  NEXT_PUBLIC_SUPABASE_URL: "http://127.0.0.1:55321",
  DATABASE_URL: "postgres://fixture@127.0.0.1:55322/postgres",
};
describe("isolated staging configuration", () => {
  it("accepts only local services and test payment keys", () =>
    expect(() =>
      assertIsolatedStaging({ ...local, STRIPE_SECRET_KEY: "sk_test_fixture" }),
    ).not.toThrow());
  it.each([
    "DATABASE_URL",
    "NEXT_PUBLIC_SUPABASE_URL",
    "AWS_S3_ENDPOINT",
    "STRIPE_API_BASE_URL",
    "MARKETING_AI_SIMULATOR_URL",
  ])("rejects an external %s", (key) => {
    expect(() =>
      assertIsolatedStaging({
        ...local,
        [key]: "https://production.example.com",
      }),
    ).toThrow();
  });
  it("rejects live payment credentials", () =>
    expect(() =>
      assertIsolatedStaging({ ...local, STRIPE_SECRET_KEY: "sk_live_fixture" }),
    ).toThrow());
  it.each(["RESEND_API_KEY", "OPENAI_API_KEY", "OPENROUTER_API_KEY"])(
    "rejects external credential %s",
    (key) =>
      expect(() =>
        assertIsolatedStaging({ ...local, [key]: "fixture" }),
      ).toThrow(),
  );
});

describe("self-hosted jobs acceptance", () => {
  const jobs = {
    ...local,
    STAGING_SELF_HOSTED_JOBS: "1",
    INNGEST_BASE_URL: "http://127.0.0.1:8488",
    INNGEST_DEV: "0",
    INNGEST_EVENT_KEY: "deadbeef" + "a".repeat(56),
    INNGEST_SIGNING_KEY: "deadbeef" + "b".repeat(56),
  };
  it("accepts local synthetic keys with signed execution", () => {
    expect(() => assertIsolatedStaging(jobs)).not.toThrow();
  });
  it.each([
    { INNGEST_BASE_URL: "https://api.inngest.com" },
    { INNGEST_EVENT_KEY: "production-key" },
    { INNGEST_SIGNING_KEY: "production-key" },
    { INNGEST_DEV: "1" },
    { INNGEST_BASE_URL: "http://127.0.0.1:8288" },
  ])("refuses unsafe signed-job configuration %j", (override) => {
    expect(() => assertIsolatedStaging({ ...jobs, ...override })).toThrow();
  });
  it("still refuses unrelated live provider credentials", () => {
    expect(() =>
      assertIsolatedStaging({ ...jobs, OPENROUTER_API_KEY: "live" }),
    ).toThrow();
  });
});
