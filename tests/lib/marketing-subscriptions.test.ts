import { beforeEach, describe, expect, it, vi } from "vitest";
const m = vi.hoisted(() => ({
  execute: vi.fn(),
  credentials: vi.fn(),
  retrieve: vi.fn(),
  checkout: vi.fn(),
}));
vi.mock("@/lib/db", () => ({ db: { execute: m.execute } }));
vi.mock("@/lib/stripe/settings", () => ({
  resolvePlatformStripeCredentials: m.credentials,
}));
vi.mock("@/lib/stripe/client", () => ({
  createStripeClient: () => ({
    subscriptions: { retrieve: m.retrieve },
    checkout: { sessions: { retrieve: m.checkout } },
  }),
}));
import { syncMarketingSubscription } from "@/lib/ai/marketing-skills/subscriptions";
const recordId = "11111111-1111-4111-8111-111111111111";
const event = {
  type: "customer.subscription.updated",
  data: { object: { id: "sub_a", metadata: { workflow: "marketing_plan" } } },
};
beforeEach(() => {
  vi.clearAllMocks();
  m.credentials.mockResolvedValue({
    secret_key: "sk_test_fixture",
    webhook_secret: "whsec_platform",
  });
  m.retrieve.mockResolvedValue({
    id: "sub_a",
    metadata: { checkout_record_id: recordId },
    customer: "cus_a",
    status: "active",
    current_period_end: 2000000000,
    cancel_at_period_end: false,
    items: { data: [{ price: { unit_amount: 4900, currency: "usd" } }] },
  });
  m.execute
    .mockResolvedValueOnce([
      {
        id: recordId,
        stripe_session_id: "cs_a",
        client_id: "client_a",
        plan_id: "plan_a",
        price_cents: 4900,
        currency: "usd",
      },
    ])
    .mockResolvedValue([]);
  m.checkout.mockResolvedValue({
    subscription: "sub_a",
    client_reference_id: "client_a",
  });
});
describe("marketing subscription payment boundaries", () => {
  it("leaves unrelated invoice/subscription handling untouched", async () =>
    expect(
      await syncMarketingSubscription(
        { type: "payment_intent.succeeded", data: { object: {} } },
        "whsec_platform",
      ),
    ).toBe(false));
  it("rejects a valid webhook signed by another tenant payment account", async () => {
    await expect(
      syncMarketingSubscription(event, "whsec_foreign"),
    ).rejects.toThrow("Incorrect platform");
    expect(m.retrieve).not.toHaveBeenCalled();
  });
  it("retrieves canonical subscription state before applying an event", async () => {
    expect(await syncMarketingSubscription(event, "whsec_platform")).toBe(true);
    expect(m.retrieve).toHaveBeenCalledWith("sub_a");
    expect(m.execute).toHaveBeenCalledTimes(3);
  });
  it("does not trust forged checkout metadata without a server record", async () => {
    m.execute.mockReset().mockResolvedValue([]);
    await expect(
      syncMarketingSubscription(event, "whsec_platform"),
    ).rejects.toThrow("unavailable");
  });
  it("rejects a checkout belonging to another client", async () => {
    m.checkout.mockResolvedValue({
      subscription: "sub_a",
      client_reference_id: "client_b",
    });
    await expect(
      syncMarketingSubscription(event, "whsec_platform"),
    ).rejects.toThrow("mismatch");
    expect(m.execute).toHaveBeenCalledTimes(1);
  });
  it("rejects an unrelated subscription attached to a checkout", async () => {
    m.checkout.mockResolvedValue({
      subscription: "sub_b",
      client_reference_id: "client_a",
    });
    await expect(
      syncMarketingSubscription(event, "whsec_platform"),
    ).rejects.toThrow("mismatch");
  });
  it("rejects a subscription with a different recurring price", async () => {
    const sub = await m.retrieve();
    sub.items.data[0].price.unit_amount = 1;
    m.retrieve.mockResolvedValue(sub);
    await expect(
      syncMarketingSubscription(event, "whsec_platform"),
    ).rejects.toThrow("price mismatch");
    expect(m.execute).toHaveBeenCalledTimes(1);
  });
});
