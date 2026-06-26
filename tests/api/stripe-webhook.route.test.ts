import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";
import { extractApiErrorMessage } from "@/lib/api/response";
import { resolveStripeWebhookSecrets } from "@/lib/stripe/settings";
import { readJson } from "../helpers/http";

vi.mock("next/headers", () => ({
  headers: vi.fn(),
}));

vi.mock("@/lib/supabase/server", () => ({
  createClient: vi.fn(),
  createAdminClientIfAvailable: vi.fn().mockReturnValue(null),
}));

vi.mock("@/lib/inngest/client", () => ({
  inngest: { send: vi.fn() },
}));

vi.mock("@/lib/notifications/service", () => ({
  dispatchNotification: vi.fn(),
}));

vi.mock("@/lib/stripe/settings", () => ({
  resolveStripeWebhookSecrets: vi.fn(),
}));

describe("POST /api/webhooks/stripe", () => {
  const originalStripeSecret = process.env.STRIPE_SECRET_KEY;
  const originalWebhookSecret = process.env.STRIPE_WEBHOOK_SECRET;

  beforeEach(() => {
    vi.resetModules();
    vi.clearAllMocks();
    vi.mocked(resolveStripeWebhookSecrets).mockResolvedValue(["whsec_test_123"]);
  });

  afterEach(() => {
    process.env.STRIPE_SECRET_KEY = originalStripeSecret;
    process.env.STRIPE_WEBHOOK_SECRET = originalWebhookSecret;
  });

  it("returns 503 when Stripe webhook is not configured", async () => {
    vi.mocked(resolveStripeWebhookSecrets).mockResolvedValue([]);

    const { POST } = await import("@/app/api/webhooks/stripe/route");
    const response = await POST(
      new Request("http://localhost/api/webhooks/stripe", {
        method: "POST",
        body: "{}",
      }),
    );
    const body = await readJson<Record<string, unknown>>(response);

    expect(response.status).toBe(503);
    expect(extractApiErrorMessage(body)).toBe("Stripe webhook is not configured");
  });

  it("returns 400 when stripe-signature header is missing", async () => {
    process.env.STRIPE_SECRET_KEY = "sk_test_123";
    process.env.STRIPE_WEBHOOK_SECRET = "whsec_test_123";

    const { headers } = await import("next/headers");
    vi.mocked(headers).mockResolvedValue(new Headers() as never);

    const { POST } = await import("@/app/api/webhooks/stripe/route");
    const response = await POST(
      new Request("http://localhost/api/webhooks/stripe", {
        method: "POST",
        body: "{}",
      }),
    );
    const body = await readJson<Record<string, unknown>>(response);

    expect(response.status).toBe(400);
    expect(extractApiErrorMessage(body)).toBe("Missing signature");
  });
});
