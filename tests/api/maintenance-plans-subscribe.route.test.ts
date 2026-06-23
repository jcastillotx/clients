import { NextRequest, NextResponse } from "next/server";
import { beforeEach, describe, expect, it, vi } from "vitest";
import { POST } from "@/app/api/maintenance-plans/subscribe/route";
import * as routeGuards from "@/lib/auth/route-guards";
import { jsonRequest, readJson } from "../helpers/http";

vi.mock("@/lib/auth/route-guards", () => ({
  requireAuthenticatedUser: vi.fn(),
}));

vi.mock("@/lib/db", () => ({
  isDatabaseConfigurationError: vi.fn().mockReturnValue(false),
  db: {
    select: vi.fn(),
    insert: vi.fn(),
  },
}));

const clientId = "11111111-1111-1111-1111-111111111111";
const templateId = "22222222-2222-2222-2222-222222222222";

describe("POST /api/maintenance-plans/subscribe", () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it("returns 401 when unauthenticated", async () => {
    vi.mocked(routeGuards.requireAuthenticatedUser).mockResolvedValue({
      error: NextResponse.json({ success: false }, { status: 401 }),
    });

    const response = await POST(
      jsonRequest("http://localhost/api/maintenance-plans/subscribe", {
        templateId,
        clientId,
      }) as NextRequest,
    );

    expect(response.status).toBe(401);
  });

  it("returns 400 when templateId is missing", async () => {
    vi.mocked(routeGuards.requireAuthenticatedUser).mockResolvedValue({
      user: { id: "user-1" } as never,
      supabase: {} as never,
    });

    const response = await POST(
      jsonRequest("http://localhost/api/maintenance-plans/subscribe", {
        clientId,
      }) as NextRequest,
    );
    const body = await readJson<{ success: boolean; error: { code: string; message: string } }>(
      response,
    );

    expect(response.status).toBe(400);
    expect(body.error.code).toBe("BAD_REQUEST");
    expect(body.error.message).toContain("templateId");
  });

  it("returns 404 when template does not exist", async () => {
    vi.mocked(routeGuards.requireAuthenticatedUser).mockResolvedValue({
      user: { id: "user-1" } as never,
      supabase: {} as never,
    });

    const { db } = await import("@/lib/db");
    vi.mocked(db.select).mockReturnValue({
      from: vi.fn().mockReturnValue({
        where: vi.fn().mockReturnValue({
          limit: vi.fn().mockResolvedValue([]),
        }),
      }),
    } as never);

    const response = await POST(
      jsonRequest("http://localhost/api/maintenance-plans/subscribe", {
        templateId,
        clientId,
      }) as NextRequest,
    );
    const body = await readJson<{ success: boolean; error: { code: string } }>(response);

    expect(response.status).toBe(404);
    expect(body.error.code).toBe("NOT_FOUND");
  });

  it("creates a maintenance plan subscription", async () => {
    vi.mocked(routeGuards.requireAuthenticatedUser).mockResolvedValue({
      user: { id: "user-1" } as never,
      supabase: {} as never,
    });

    const { db } = await import("@/lib/db");
    vi.mocked(db.select).mockReturnValue({
      from: vi.fn().mockReturnValue({
        where: vi.fn().mockReturnValue({
          limit: vi.fn().mockResolvedValue([
            {
              id: templateId,
              name: "Standard Care",
              description: "Monthly support",
              planType: "standard",
              billingCycle: "monthly",
              monthlyRate: "500.00",
              currency: "USD",
              autoRenew: true,
              includedHours: "10",
              hourlyRateOverage: "150.00",
              rolloverEnabled: false,
              maxRolloverHours: null,
              servicesIncluded: [],
              overageBillingEnabled: true,
              overageApprovalRequired: false,
              overageNotificationThreshold: null,
              renewalTermMonths: 12,
              isActive: true,
            },
          ]),
        }),
      }),
    } as never);

    vi.mocked(db.insert).mockReturnValue({
      values: vi.fn().mockReturnValue({
        returning: vi.fn().mockResolvedValue([
          {
            id: "plan-1",
            clientId,
            templateId,
            name: "Standard Care",
            status: "active",
          },
        ]),
      }),
    } as never);

    const response = await POST(
      jsonRequest("http://localhost/api/maintenance-plans/subscribe", {
        templateId,
        clientId,
      }) as NextRequest,
    );
    const body = await readJson<{ success: boolean; data: { id: string }; message?: string }>(
      response,
    );

    expect(response.status).toBe(201);
    expect(body.success).toBe(true);
    expect(body.data.id).toBe("plan-1");
    expect(body.message).toContain("Successfully subscribed");
  });
});
