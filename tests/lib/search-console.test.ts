import { afterEach, describe, expect, it, vi } from "vitest";
vi.mock("@/lib/db", () => ({ db: { execute: vi.fn() } }));
import {
  searchConsoleRequest,
  searchConsoleSite,
} from "@/lib/marketing/search-console";
import {
  googleMarketingResources,
  connectionGroup,
} from "@/lib/marketing/google-connections";
import { marketingReportStatus } from "@/lib/marketing/report";
afterEach(() => vi.unstubAllGlobals());
describe("Search Console reporting", () => {
  it("uses Pacific dates across UTC midnight and a fixed provider origin", () => {
    const query = searchConsoleRequest(
      "https://example.com/",
      new Date("2026-10-27T01:00:00Z"),
    );
    expect(query.body).toEqual({
      startDate: "2026-10-17",
      endDate: "2026-10-23",
      type: "web",
      dataState: "final",
      rowLimit: 1,
    });
    expect(query.url).toBe(
      "https://www.googleapis.com/webmasters/v3/sites/https%3A%2F%2Fexample.com%2F/searchAnalytics/query",
    );
    expect(connectionGroup("google_search_console")).toBe("seo");
  });
  it("supports domain properties and rejects credentials, queries and non-web schemes", () => {
    expect(searchConsoleSite.parse("sc-domain:example.com")).toBe(
      "sc-domain:example.com",
    );
    for (const value of [
      "file:///etc/passwd",
      "https://user:pass@example.com/",
      "https://example.com/?token=x",
      "sc-domain:example.com/path",
    ])
      expect(searchConsoleSite.safeParse(value).success).toBe(false);
  });
  it("discovers only verified accessible properties", async () => {
    const fetch = vi.fn().mockResolvedValue({
      ok: true,
      json: async () => ({
        siteEntry: [
          { siteUrl: "sc-domain:example.com", permissionLevel: "siteOwner" },
          {
            siteUrl: "https://unverified.example/",
            permissionLevel: "siteUnverifiedUser",
          },
        ],
      }),
    });
    vi.stubGlobal("fetch", fetch);
    expect(
      await googleMarketingResources("fixture", "google_search_console"),
    ).toEqual([{ id: "sc-domain:example.com", name: "sc-domain:example.com" }]);
    expect(fetch.mock.calls[0][0]).toBe(
      "https://www.googleapis.com/webmasters/v3/sites",
    );
  });
  it("distinguishes missing rows from genuine aggregate data and rejects provider errors", () => {
    expect(marketingReportStatus("google_search_console", {})).toBe("empty");
    expect(
      marketingReportStatus("google_search_console", {
        rows: [{ clicks: 0, impressions: 2, ctr: 0, position: 4 }],
      }),
    ).toBe("data");
    expect(() =>
      marketingReportStatus("google_search_console", { error: { code: 403 } }),
    ).toThrow();
    expect(() =>
      marketingReportStatus("google_search_console", {
        rows: [{ clicks: -1 }],
      }),
    ).toThrow();
  });
});
