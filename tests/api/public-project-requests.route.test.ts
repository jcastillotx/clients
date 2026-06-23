import { NextRequest } from "next/server";
import { beforeEach, describe, expect, it, vi } from "vitest";
import { POST } from "@/app/api/public/project-requests/route";
import { extractApiErrorMessage } from "@/lib/api/response";
import { assertTurnstileToken } from "@/lib/turnstile/verify";
import { createAdminClientIfAvailable } from "@/lib/supabase/server";
import { dispatchNotification } from "@/lib/notifications/service";
import { jsonRequest, readJson } from "../helpers/http";

vi.mock("@/lib/rate-limit", () => ({
  limiters: {
    publicIntake: vi.fn().mockResolvedValue({
      success: true,
      remaining: 4,
      resetAt: Date.now() + 60_000,
    }),
  },
  getClientIp: vi.fn().mockReturnValue("127.0.0.1"),
  rateLimitLimits: { publicIntake: 5 },
}));

vi.mock("@/lib/turnstile/verify", () => ({
  assertTurnstileToken: vi.fn(),
}));

vi.mock("@/lib/notifications/service", () => ({
  dispatchNotification: vi.fn().mockResolvedValue(undefined),
}));

vi.mock("@/lib/supabase/server", () => ({
  createAdminClientIfAvailable: vi.fn(),
}));

const validPayload = {
  companyName: "Acme Corp",
  contactName: "Jane Doe",
  contactEmail: "jane@acme.com",
  title: "New Website",
  executiveSummary: "We need a modern marketing website with lead capture and analytics.",
  priority: "medium" as const,
};

function createAdminMock() {
  const usersChain = {
    select: vi.fn().mockReturnThis(),
    eq: vi.fn().mockReturnThis(),
    limit: vi.fn().mockReturnThis(),
    maybeSingle: vi.fn().mockResolvedValue({ data: { id: "owner-1" }, error: null }),
  };

  const userRolesChain = {
    select: vi.fn().mockResolvedValue({
      data: [{ user_id: "owner-1", role: { name: "admin" } }],
      error: null,
    }),
  };

  return {
    from: vi.fn((table: string) => {
      if (table === "users") return usersChain;
      if (table === "user_roles") return userRolesChain;
      if (table === "clients") {
        return {
          insert: vi.fn().mockReturnThis(),
          select: vi.fn().mockReturnThis(),
          single: vi.fn().mockResolvedValue({
            data: { id: "client-1" },
            error: null,
          }),
        };
      }
      if (table === "requests") {
        return {
          insert: vi.fn().mockReturnThis(),
          select: vi.fn().mockReturnThis(),
          single: vi.fn().mockResolvedValue({
            data: { id: "request-1", client_id: "client-1" },
            error: null,
          }),
        };
      }
      return usersChain;
    }),
  };
}

describe("POST /api/public/project-requests", () => {
  beforeEach(() => {
    vi.clearAllMocks();
    vi.mocked(assertTurnstileToken).mockResolvedValue({ ok: true });
    vi.mocked(createAdminClientIfAvailable).mockReturnValue(createAdminMock() as never);
  });

  it("returns 503 when public intake is not configured", async () => {
    vi.mocked(createAdminClientIfAvailable).mockReturnValue(null);

    const response = await POST(
      jsonRequest("http://localhost/api/public/project-requests", validPayload) as NextRequest,
    );
    const body = await readJson<Record<string, unknown>>(response);

    expect(response.status).toBe(503);
    expect(extractApiErrorMessage(body)).toBe("Public intake is not configured");
  });

  it("returns 400 when payload validation fails", async () => {
    const response = await POST(
      jsonRequest("http://localhost/api/public/project-requests", {
        ...validPayload,
        executiveSummary: "too short",
      }) as NextRequest,
    );

    expect(response.status).toBe(400);
  });

  it("returns 400 when captcha verification fails", async () => {
    vi.mocked(assertTurnstileToken).mockResolvedValue({
      ok: false,
      error: "CAPTCHA verification failed. Please try again.",
      status: 400,
    });

    const response = await POST(
      jsonRequest("http://localhost/api/public/project-requests", {
        ...validPayload,
        turnstileToken: "bad-token",
      }) as NextRequest,
    );
    const body = await readJson<Record<string, unknown>>(response);

    expect(response.status).toBe(400);
    expect(extractApiErrorMessage(body)).toContain("CAPTCHA");
  });

  it("creates intake records for valid submissions", async () => {
    const response = await POST(
      jsonRequest("http://localhost/api/public/project-requests", validPayload) as NextRequest,
    );
    const body = await readJson<{ success: boolean; data: { requestId: string; clientId: string } }>(response);

    expect(response.status).toBe(201);
    expect(body.success).toBe(true);
    expect(body.data.requestId).toBe("request-1");
    expect(body.data.clientId).toBe("client-1");
    expect(dispatchNotification).toHaveBeenCalledOnce();
  });
});
