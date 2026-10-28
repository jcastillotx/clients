import { getPublicServiceCatalog } from "@/lib/catalog/public-catalog";
import { NextRequest } from "next/server";
import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";
import { POST } from "@/app/api/public/project-requests/route";
import { extractApiErrorMessage } from "@/lib/api/response";
import { assertTurnstileToken } from "@/lib/turnstile/verify";
import { createAdminClientIfAvailable } from "@/lib/supabase/server";
import { dispatchNotification } from "@/lib/notifications/service";
import { sendServiceRequestConfirmation } from "@/lib/email/service-request-confirmation";
import { jsonRequest, readJson } from "../helpers/http";

vi.mock("@/lib/catalog/public-catalog", () => ({ getPublicServiceCatalog: vi.fn() }));

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

vi.mock("@/lib/email/service-request-confirmation", () => ({
  sendServiceRequestConfirmation: vi.fn(),
}));

vi.mock("@/lib/supabase/redirect-url", () => ({
  getAuthBaseUrl: () => "https://clients.kre8ivdesigns.com",
}));

vi.mock("@/lib/supabase/server", () => ({
  createAdminClientIfAvailable: vi.fn(),
}));

function restoreEnvValue(key: string, value: string | undefined) {
  if (value === undefined) {
    delete process.env[key];
    return;
  }

  process.env[key] = value;
}

const validPayload = {
  companyName: "Acme Corp",
  contactName: "Jane Doe",
  contactEmail: "jane@acme.com",
  title: "New Website",
  executiveSummary: "We need a modern marketing website with lead capture and analytics.",
  priority: "medium" as const,
};

function createAdminMock(requestFails = false) {
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
      if (table === "system_settings") {
        return {
          select: vi.fn().mockReturnThis(),
          eq: vi.fn().mockReturnThis(),
          maybeSingle: vi.fn().mockResolvedValue({
            data: { value: process.env.PLATFORM_NOTIFICATION_EMAIL ?? "" },
            error: null,
          }),
        };
      }
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
            data: requestFails ? null : { id: "request-1", client_id: "client-1" },
            error: requestFails ? { message: "Insert failed" } : null,
          }),
        };
      }
      return usersChain;
    }),
  };
}

describe("POST /api/public/project-requests", () => {
  const originalPlatformNotificationEmail =
    process.env.PLATFORM_NOTIFICATION_EMAIL;

  beforeEach(() => {
    vi.clearAllMocks();
    vi.mocked(dispatchNotification).mockResolvedValue(undefined);
    vi.mocked(sendServiceRequestConfirmation).mockResolvedValue(true);
    process.env.PLATFORM_NOTIFICATION_EMAIL = "platform@example.com";
    vi.mocked(assertTurnstileToken).mockResolvedValue({ ok: true });
    vi.mocked(createAdminClientIfAvailable).mockReturnValue(createAdminMock() as never);
  });

  afterEach(() => {
    restoreEnvValue(
      "PLATFORM_NOTIFICATION_EMAIL",
      originalPlatformNotificationEmail,
    );
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
    expect(sendServiceRequestConfirmation).not.toHaveBeenCalled();
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
    const admin = vi.mocked(createAdminClientIfAvailable).mock.results[0].value;
    expect(admin.from).not.toHaveBeenCalledWith("clients");
    expect(admin.from).not.toHaveBeenCalledWith("requests");
    expect(dispatchNotification).not.toHaveBeenCalled();
    expect(sendServiceRequestConfirmation).not.toHaveBeenCalled();
  });

  it("rejects a filled honeypot before any intake records or mail are created", async () => {
    const response = await POST(jsonRequest("http://localhost/api/public/project-requests", {
      ...validPayload, contactFax: "bot-filled-field",
    }) as NextRequest);
    expect(response.status).toBe(400);
    const admin = vi.mocked(createAdminClientIfAvailable).mock.results[0].value;
    expect(admin.from).not.toHaveBeenCalledWith("clients");
    expect(admin.from).not.toHaveBeenCalledWith("requests");
    expect(dispatchNotification).not.toHaveBeenCalled();
    expect(sendServiceRequestConfirmation).not.toHaveBeenCalled();
  });

  it.each([{title:"   "},{executiveSummary:"                         "},{website:"javascript:alert(1)"},{contactPhone:"abc"},{requestedStartDate:"1970-05-31"},{requestedLaunchDate:"2026-02-30"},{country:"Hrqgqnijq"}])("rejects invalid field data before writes or notifications: %j", async patch => {
    const response = await POST(jsonRequest("http://localhost/api/public/project-requests", {...validPayload,...patch}) as NextRequest);
    expect(response.status).toBe(400);
    const admin = vi.mocked(createAdminClientIfAvailable).mock.results[0].value;
    expect(admin.from).not.toHaveBeenCalledWith("clients");
    expect(admin.from).not.toHaveBeenCalledWith("requests");
    expect(dispatchNotification).not.toHaveBeenCalled();
    expect(sendServiceRequestConfirmation).not.toHaveBeenCalled();
  });

  it.each(["jane@acme.com", "nazevedo@metrocompactor.com"])("keeps submitted contact %s out of internal notification recipients", async (contactEmail) => {
    const response = await POST(
      jsonRequest("http://localhost/api/public/project-requests", {...validPayload, contactEmail}) as NextRequest,
    );
    const body = await readJson<{ success: boolean; data: { requestId: string; clientId: string; confirmationEmailSent: boolean } }>(response);

    expect(response.status).toBe(201);
    expect(body.success).toBe(true);
    expect(body.data.requestId).toBe("request-1");
    expect(body.data.clientId).toBe("client-1");
    expect(body.data.confirmationEmailSent).toBe(true);
    expect(sendServiceRequestConfirmation).toHaveBeenCalledOnce();
    expect(sendServiceRequestConfirmation).toHaveBeenCalledWith({ email: contactEmail, requestId: "request-1" });
    expect(dispatchNotification).toHaveBeenCalledOnce();
    expect(dispatchNotification).toHaveBeenCalledWith(
      expect.objectContaining({
        recipientUserIds: ["owner-1"],
        extraEmails: ["platform@example.com"],
        data: expect.objectContaining({
          request_title: validPayload.title,
          request_description: validPayload.executiveSummary,
          request_url: "https://clients.kre8ivdesigns.com/projects/requests/request-1",
          company_name: validPayload.companyName,
          created_by_name: validPayload.contactName,
        }),
      }),
    );
  });

  it("rejects unpublished packages before creating an organization or request", async () => {
    vi.mocked(getPublicServiceCatalog).mockResolvedValue({ schemaVersion: 1, services: [], packages: [], version: "test" });
    const response = await POST(jsonRequest("http://localhost/api/public/project-requests", { ...validPayload, metadata: { packageId: "retired-package" } }) as NextRequest);
    expect(response.status).toBe(400);
    const admin = vi.mocked(createAdminClientIfAvailable).mock.results[0].value;
    expect(admin.from).not.toHaveBeenCalledWith("clients");
    expect(admin.from).not.toHaveBeenCalledWith("requests");
    expect(dispatchNotification).not.toHaveBeenCalled();
  });

  it("saves canonical package context rather than caller-supplied package details", async () => {
    const canonical = { id: "care-growth", name: "Care Growth", serviceId: "service-id" };
    vi.mocked(getPublicServiceCatalog).mockResolvedValue({ schemaVersion: 1, services: [], packages: [{ ...canonical, group: "Website care", fit: "Scoped ongoing care", includes: ["Agreed support"], service: "Website care" }], version: "test" });
    const response = await POST(jsonRequest("http://localhost/api/public/project-requests", { ...validPayload, metadata: { packageId: canonical.id, selectedPackage: { name: "FORGED", serviceId: "another-tenant" } } }) as NextRequest);
    expect(response.status).toBe(201);
    const admin = vi.mocked(createAdminClientIfAvailable).mock.results[0].value;
    const index = admin.from.mock.calls.findIndex(([table]: [string]) => table === "requests");
    expect(admin.from.mock.results[index].value.insert).toHaveBeenCalledWith(expect.objectContaining({ custom_fields: expect.objectContaining({ selectedPackage: canonical, source: "public_project_request" }) }));
  });

  it("does not acknowledge a request that failed to persist", async () => {
    vi.mocked(createAdminClientIfAvailable).mockReturnValue(createAdminMock(true) as never);
    const response = await POST(jsonRequest("http://localhost/api/public/project-requests", validPayload) as NextRequest);
    expect(response.status).toBe(500);
    expect(sendServiceRequestConfirmation).not.toHaveBeenCalled();
    expect(dispatchNotification).not.toHaveBeenCalled();
  });

  it("sends the receipt even when internal notification dispatch fails", async () => {
    vi.mocked(dispatchNotification).mockRejectedValueOnce(new Error("Internal alert failed"));
    const response = await POST(jsonRequest("http://localhost/api/public/project-requests", validPayload) as NextRequest);
    expect(response.status).toBe(201);
    expect(sendServiceRequestConfirmation).toHaveBeenCalledOnce();
  });

  it.each([false, "throws"])("preserves the saved request when confirmation delivery %s", async (mode) => {
    if (mode === "throws") {
      vi.mocked(sendServiceRequestConfirmation).mockRejectedValueOnce(new Error("Mail unavailable"));
    } else {
      vi.mocked(sendServiceRequestConfirmation).mockResolvedValueOnce(false);
    }
    const response = await POST(jsonRequest("http://localhost/api/public/project-requests", validPayload) as NextRequest);
    const body = await readJson<{data: {requestId: string; confirmationEmailSent: boolean}}>(response);
    expect(response.status).toBe(201);
    expect(body.data.requestId).toBe("request-1");
    expect(body.data.confirmationEmailSent).toBe(false);
    expect(dispatchNotification).toHaveBeenCalledOnce();
  });
});
