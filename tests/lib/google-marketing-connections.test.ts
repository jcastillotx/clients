import { beforeEach, afterEach, describe, expect, it, vi } from "vitest";
const m = vi.hoisted(() => ({ execute: vi.fn() }));
vi.mock("@/lib/db", () => ({ db: { execute: m.execute } }));
vi.mock("@/lib/encryption", () => ({
  encrypt: (v: string) => "encrypted:" + v,
  decrypt: (v: string) => v.replace(/^encrypted:/, ""),
}));
import {
  googleMarketingResources,
  googleMarketingToken,
} from "@/lib/marketing/google-connections";
beforeEach(() => {
  vi.clearAllMocks();
  vi.stubEnv("NEXT_PUBLIC_APP_URL", "http://127.0.0.1:3101");
  vi.stubEnv("GOOGLE_MARKETING_CLIENT_ID", "fixture");
  vi.stubEnv("GOOGLE_MARKETING_CLIENT_SECRET", "fixture");
});
afterEach(() => {
  vi.unstubAllGlobals();
  vi.unstubAllEnvs();
});
describe("client-owned Google marketing tokens", () => {
  it("does not contact Google without this user and tenant connection", async () => {
    m.execute.mockResolvedValue([]);
    const fetch = vi.fn();
    vi.stubGlobal("fetch", fetch);
    await expect(
      googleMarketingToken("client", "user", "google_ads"),
    ).rejects.toThrow("Connect");
    expect(fetch).not.toHaveBeenCalled();
  });
  it("refreshes an expired token and persists the rotated refresh token encrypted", async () => {
    m.execute
      .mockResolvedValueOnce([
        {
          id: "row",
          access_token: "encrypted:old",
          refresh_token: "encrypted:refresh",
          expires_at: new Date(0),
        },
      ])
      .mockResolvedValue([]);
    const fetch = vi
      .fn()
      .mockResolvedValue({
        ok: true,
        json: async () => ({
          access_token: "new",
          refresh_token: "rotated",
          expires_in: 3600,
        }),
      });
    vi.stubGlobal("fetch", fetch);
    expect(
      (await googleMarketingToken("client", "user", "google_analytics")).token,
    ).toBe("new");
    expect(fetch.mock.calls[0][0]).toBe("https://oauth2.googleapis.com/token");
    expect(m.execute).toHaveBeenCalledTimes(2);
  });
  it("does not return a stale token after refresh is revoked", async () => {
    m.execute.mockResolvedValue([
      {
        access_token: "encrypted:old",
        refresh_token: "encrypted:refresh",
        expires_at: new Date(0),
      },
    ]);
    vi.stubGlobal("fetch", vi.fn().mockResolvedValue({ ok: false }));
    await expect(
      googleMarketingToken("client", "user", "google_analytics"),
    ).rejects.toThrow("Reconnect");
  });
  it("discovers only valid Analytics property IDs and follows pagination", async () => {
    const fetch = vi
      .fn()
      .mockResolvedValueOnce({
        ok: true,
        json: async () => ({
          accountSummaries: [
            {
              propertySummaries: [
                { property: "properties/123", displayName: "Own site" },
                { property: "https://attacker.invalid" },
              ],
            },
          ],
          nextPageToken: "next",
        }),
      })
      .mockResolvedValueOnce({
        ok: true,
        json: async () => ({ accountSummaries: [] }),
      });
    vi.stubGlobal("fetch", fetch);
    expect(await googleMarketingResources("token", "google_analytics")).toEqual(
      [{ id: "properties/123", name: "Own site" }],
    );
    expect(fetch.mock.calls[1][0].hostname).toBe(
      "analyticsadmin.googleapis.com",
    );
  });
  it("uses only the accessible Ads endpoint and filters malformed resources", async () => {
    const fetch = vi
      .fn()
      .mockResolvedValue({
        ok: true,
        json: async () => ({
          resourceNames: ["customers/123", "https://attacker.invalid"],
        }),
      });
    vi.stubGlobal("fetch", fetch);
    expect(await googleMarketingResources("token", "google_ads")).toEqual([
      { id: "customers/123", name: "Google Ads 123" },
    ]);
    expect(fetch.mock.calls[0][0]).toMatch(
      /^https:\/\/googleads.googleapis.com\/v\d+\/customers:listAccessibleCustomers$/,
    );
  });
});
