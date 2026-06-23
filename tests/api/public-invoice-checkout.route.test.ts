import { NextRequest } from "next/server";
import { beforeEach, describe, expect, it, vi } from "vitest";
import { POST } from "@/app/api/public/invoices/create-checkout-session/route";
import { extractApiErrorMessage } from "@/lib/api/response";
import { assertTurnstileToken } from "@/lib/turnstile/verify";
import { createAdminClientIfAvailable } from "@/lib/supabase/server";
import { getStripe } from "@/lib/stripe/client";
import { jsonRequest, readJson } from "../helpers/http";

vi.mock("@/lib/rate-limit", () => ({
  limiters: {
    publicPayment: vi.fn().mockResolvedValue({
      success: true,
      remaining: 9,
      resetAt: Date.now() + 60_000,
    }),
  },
  getClientIp: vi.fn().mockReturnValue("127.0.0.1"),
  rateLimitLimits: { publicPayment: 10 },
}));

vi.mock("@/lib/turnstile/verify", () => ({
  assertTurnstileToken: vi.fn(),
}));

vi.mock("@/lib/supabase/server", () => ({
  createAdminClientIfAvailable: vi.fn(),
}));

vi.mock("@/lib/stripe/client", () => ({
  getStripe: vi.fn(),
}));

const validPayload = {
  invoiceNumber: "INV-1001",
  paymentAmount: 1500,
  email: "billing@acme.com",
  businessName: "Acme Corp",
  contactName: "Jane Doe",
};

describe("POST /api/public/invoices/create-checkout-session", () => {
  beforeEach(() => {
    vi.clearAllMocks();
    vi.mocked(assertTurnstileToken).mockResolvedValue({ ok: true });
  });

  it("returns 500 when admin client is unavailable", async () => {
    vi.mocked(createAdminClientIfAvailable).mockReturnValue(null);

    const response = await POST(
      jsonRequest(
        "http://localhost/api/public/invoices/create-checkout-session",
        validPayload,
      ) as NextRequest,
    );

    expect(response.status).toBe(500);
  });

  it("returns 404 when invoice is not found", async () => {
    vi.mocked(createAdminClientIfAvailable).mockReturnValue({
      from: vi.fn().mockReturnValue({
        select: vi.fn().mockReturnThis(),
        eq: vi.fn().mockReturnThis(),
        is: vi.fn().mockReturnThis(),
        single: vi.fn().mockResolvedValue({
          data: null,
          error: { message: "not found" },
        }),
      }),
    } as never);

    const response = await POST(
      jsonRequest(
        "http://localhost/api/public/invoices/create-checkout-session",
        validPayload,
      ) as NextRequest,
    );
    const body = await readJson<Record<string, unknown>>(response);

    expect(response.status).toBe(404);
    expect(extractApiErrorMessage(body)).toContain("Invoice not found");
  });

  it("returns 400 when payment amount does not match invoice total", async () => {
    vi.mocked(createAdminClientIfAvailable).mockReturnValue({
      from: vi.fn().mockReturnValue({
        select: vi.fn().mockReturnThis(),
        eq: vi.fn().mockReturnThis(),
        is: vi.fn().mockReturnThis(),
        single: vi.fn().mockResolvedValue({
          data: {
            id: "inv-1",
            invoice_number: "INV-1001",
            client_id: "client-1",
            amount: 2000,
            status: "sent",
            client: { id: "client-1", company_name: "Acme", email: "billing@acme.com" },
          },
          error: null,
        }),
      }),
    } as never);

    const response = await POST(
      jsonRequest(
        "http://localhost/api/public/invoices/create-checkout-session",
        validPayload,
      ) as NextRequest,
    );
    const body = await readJson<Record<string, unknown>>(response);

    expect(response.status).toBe(400);
    expect(extractApiErrorMessage(body)).toContain("Payment amount must match");
  });

  it("creates a checkout session for valid requests", async () => {
    vi.stubEnv("NEXT_PUBLIC_APP_URL", "http://localhost:3000");

    vi.mocked(createAdminClientIfAvailable).mockReturnValue({
      from: vi.fn().mockReturnValue({
        select: vi.fn().mockReturnThis(),
        eq: vi.fn().mockReturnThis(),
        is: vi.fn().mockReturnThis(),
        single: vi.fn().mockResolvedValue({
          data: {
            id: "inv-1",
            invoice_number: "INV-1001",
            client_id: "client-1",
            amount: 1500,
            status: "sent",
            client: { id: "client-1", company_name: "Acme", email: "billing@acme.com" },
          },
          error: null,
        }),
        update: vi.fn().mockReturnThis(),
      }),
    } as never);

    vi.mocked(getStripe).mockReturnValue({
      checkout: {
        sessions: {
          create: vi.fn().mockResolvedValue({
            id: "cs_test_123",
            url: "https://checkout.stripe.test/session",
          }),
        },
      },
    } as never);

    const response = await POST(
      jsonRequest(
        "http://localhost/api/public/invoices/create-checkout-session",
        validPayload,
      ) as NextRequest,
    );
    const body = await readJson<{
      success: boolean;
      data: { checkoutUrl: string; sessionId: string };
      checkoutUrl: string;
      sessionId: string;
    }>(response);

    expect(response.status).toBe(200);
    expect(body.success).toBe(true);
    expect(body.data.sessionId).toBe("cs_test_123");
    expect(body.data.checkoutUrl).toContain("checkout.stripe.test");
    expect(body.checkoutUrl).toContain("checkout.stripe.test");
  });
});
