import { describe, expect, it } from "vitest";
import { googleAdsResourceTarget } from "@/lib/marketing/google-ads-resources";
describe("Google Ads account targets", () => {
  it("supports directly authorized advertisers", () =>
    expect(googleAdsResourceTarget("customers/123")).toEqual({
      customerId: "123",
      loginCustomerId: null,
    }));
  it("keeps the manager identity when reading a linked advertiser", () =>
    expect(
      googleAdsResourceTarget("customers/123/customerClients/456"),
    ).toEqual({ customerId: "456", loginCustomerId: "123" }));
  it.each([
    "https://attacker.invalid/customers/123",
    "customers/123?query=mutate",
    "customers/123/customerClients/456/mutate",
    "customers/not-a-number",
  ])("rejects malformed target %s", (id) =>
    expect(googleAdsResourceTarget(id)).toBeNull(),
  );
});
