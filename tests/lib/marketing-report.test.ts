import { describe, expect, it } from "vitest";
import { marketingReportStatus } from "@/lib/marketing/report";
describe("actual Google report shapes", () => {
  it("accepts the metadata-only GA4 response observed for an empty property", () =>
    expect(
      marketingReportStatus("google_analytics", {
        kind: "analyticsData#runReport",
        metadata: { currencyCode: "USD" },
      }),
    ).toBe("empty"));
  it("recognizes populated GA4 rows without inventing values", () =>
    expect(
      marketingReportStatus("google_analytics", {
        kind: "analyticsData#runReport",
        rows: [{ metricValues: [{ value: "1" }] }],
      }),
    ).toBe("data"));
  it("rejects an unrelated successful JSON response", () =>
    expect(() => marketingReportStatus("google_analytics", {})).toThrow());
  it("recognizes empty Ads results", () =>
    expect(marketingReportStatus("google_ads", {})).toBe("empty"));
  it("recognizes Ads report rows", () =>
    expect(
      marketingReportStatus("google_ads", {
        results: [{ customer: { id: "123" } }],
      }),
    ).toBe("data"));
  it("does not label an Ads error response as an empty report", () =>
    expect(() =>
      marketingReportStatus("google_ads", { error: { message: "denied" } }),
    ).toThrow());
});
