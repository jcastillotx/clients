import { describe, expect, it, vi } from "vitest";
import { processStripeWebhookRequest } from "./stripe-webhook-request";

describe("processStripeWebhookRequest", () => {
  it("short-circuits duplicate events", async () => {
    const constructEvent = vi.fn().mockReturnValue({
      id: "evt_123",
      type: "payment_intent.succeeded",
      data: { object: {} },
    });
    const hasProcessedEvent = vi.fn().mockResolvedValue(true);
    const onEvent = vi.fn();

    const result = await processStripeWebhookRequest({
      body: "{}",
      signature: "sig",
      webhookSecret: "whsec_123",
      constructEvent,
      hasProcessedEvent,
      onEvent,
    });

    expect(result.status).toBe(200);
    expect(result.payload).toEqual({ received: true, duplicate: true });
    expect(constructEvent).toHaveBeenCalledOnce();
    expect(hasProcessedEvent).toHaveBeenCalledWith("evt_123");
    expect(onEvent).not.toHaveBeenCalled();
  });

  it("returns invalid signature when constructEvent throws", async () => {
    const result = await processStripeWebhookRequest({
      body: "{}",
      signature: "sig",
      webhookSecret: "whsec_123",
      constructEvent: vi.fn().mockImplementation(() => {
        throw new Error("bad signature");
      }),
      hasProcessedEvent: vi.fn(),
      onEvent: vi.fn(),
    });

    expect(result.status).toBe(400);
    expect(result.payload).toEqual({ error: "Invalid signature" });
  });

  it("processes non-duplicate events exactly once", async () => {
    const constructEvent = vi.fn().mockReturnValue({
      id: "evt_new",
      type: "payment_intent.succeeded",
      data: { object: { id: "pi_1" } },
    });
    const hasProcessedEvent = vi.fn().mockResolvedValue(false);
    const onEvent = vi.fn().mockResolvedValue(undefined);

    const result = await processStripeWebhookRequest({
      body: "{}",
      signature: "sig",
      webhookSecret: "whsec_123",
      constructEvent,
      hasProcessedEvent,
      onEvent,
    });

    expect(result.status).toBe(200);
    expect(result.payload).toEqual({ received: true });
    expect(constructEvent).toHaveBeenCalledOnce();
    expect(hasProcessedEvent).toHaveBeenCalledWith("evt_new");
    expect(onEvent).toHaveBeenCalledOnce();
    expect(onEvent).toHaveBeenCalledWith({
      id: "evt_new",
      type: "payment_intent.succeeded",
      data: { object: { id: "pi_1" } },
    });
  });
});
