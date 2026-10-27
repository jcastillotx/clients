import { afterEach, describe, expect, it, vi } from "vitest";
import { getAuthBaseUrl } from "@/lib/supabase/redirect-url";

afterEach(() => vi.unstubAllEnvs());
describe("OpenShip canonical authentication origin", () => {
  it("refuses a retired hosting origin when the canonical production URL is missing", () => {
    vi.stubEnv("NODE_ENV", "production");
    vi.stubEnv("NEXT_PUBLIC_SITE_URL", "");
    vi.stubEnv("NEXT_PUBLIC_APP_URL", "");
    vi.stubEnv("VERCEL_URL", "retired-preview.vercel.app");
    vi.stubEnv(
      "VERCEL_PROJECT_PRODUCTION_URL",
      "retired-production.vercel.app",
    );
    expect(() => getAuthBaseUrl()).toThrow("No base URL configured");
  });
  it("uses the configured custom domain", () => {
    vi.stubEnv("NEXT_PUBLIC_SITE_URL", "https://clients.kre8ivdesigns.com");
    expect(getAuthBaseUrl()).toBe("https://clients.kre8ivdesigns.com");
  });
});
