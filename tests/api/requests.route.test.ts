import { NextRequest } from "next/server";
import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";
import { POST } from "@/app/api/requests/route";
import { createClient } from "@/lib/supabase/server";
import { dispatchNotification } from "@/lib/notifications/service";
import { isUserAssignableToClient } from "@/lib/users/assignable-users";
import { jsonRequest, readJson } from "../helpers/http";

vi.mock("@/lib/supabase/server", () => ({
  createClient: vi.fn(),
  createAdminClientIfAvailable: vi.fn().mockReturnValue(null),
}));

vi.mock("@/lib/notifications/service", () => ({
  dispatchNotification: vi.fn().mockResolvedValue(undefined),
}));

vi.mock("@/lib/users/assignable-users", () => ({
  isUserAssignableToClient: vi.fn().mockResolvedValue(true),
}));

vi.mock("@/lib/supabase/redirect-url", () => ({
  getAuthBaseUrl: vi.fn().mockReturnValue("https://clients.example.test"),
}));

function restoreEnvValue(key: string, value: string | undefined) {
  if (value === undefined) {
    delete process.env[key];
    return;
  }

  process.env[key] = value;
}

const ownClientId = "11111111-1111-1111-1111-111111111111";
const otherClientId = "22222222-2222-2222-2222-222222222222";

function createPostPayload(overrides: Record<string, unknown> = {}) {
  return {
    clientId: ownClientId,
    title: "Website update",
    description: "Please update the website homepage copy.",
    priority: "medium",
    status: "pending",
    type: "support",
    ...overrides,
  };
}

function createRequestClientMock(options: {
  dbUser: { id: string; client_id: string | null; is_super_admin?: boolean | null; name?: string | null; email?: string | null };
  roleRows?: Array<{ role?: { name?: string } | Array<{ name?: string }> }>;
}) {
  let insertedPayload: Record<string, unknown> | null = null;

  const usersChain = {
    select: vi.fn().mockReturnThis(),
    eq: vi.fn().mockReturnThis(),
    maybeSingle: vi.fn().mockResolvedValue({ data: options.dbUser, error: null }),
  };

  const userRolesChain = {
    select: vi.fn().mockReturnThis(),
    eq: vi.fn().mockReturnThis(),
    then: (resolve: (value: unknown) => unknown) =>
      Promise.resolve({ data: options.roleRows ?? [], error: null }).then(resolve),
  };

  const requestsChain = {
    insert: vi.fn((payload: Record<string, unknown>) => {
      insertedPayload = payload;
      return requestsChain;
    }),
    select: vi.fn().mockReturnThis(),
    single: vi.fn().mockResolvedValue({
      data: {
        id: "request-1",
        title: "Website update",
        priority: insertedPayload?.["priority"],
        status: insertedPayload?.["status"],
        client: { company_name: "Acme" },
      },
      error: null,
    }),
  };

  const supabase = {
    auth: {
      getUser: vi.fn().mockResolvedValue({
        data: { user: { id: options.dbUser.id, email: options.dbUser.email ?? "user@example.com" } },
        error: null,
      }),
    },
    from: vi.fn((table: string) => {
      if (table === "users") return usersChain;
      if (table === "user_roles") return userRolesChain;
      if (table === "requests") return requestsChain;
      throw new Error(`Unexpected table: ${table}`);
    }),
  };

  return {
    getInsertedPayload: () => insertedPayload,
    supabase,
  };
}

describe("POST /api/requests", () => {
  const originalPlatformNotificationEmail =
    process.env.PLATFORM_NOTIFICATION_EMAIL;

  beforeEach(() => {
    vi.clearAllMocks();
    process.env.PLATFORM_NOTIFICATION_EMAIL = "platform@example.com";
    vi.mocked(isUserAssignableToClient).mockResolvedValue(true);
  });

  afterEach(() => {
    restoreEnvValue(
      "PLATFORM_NOTIFICATION_EMAIL",
      originalPlatformNotificationEmail,
    );
  });

  it("forces client-created requests to pending for the user's assigned client", async () => {
    const mock = createRequestClientMock({
      dbUser: {
        id: "user-1",
        client_id: ownClientId,
        is_super_admin: false,
        name: "Client User",
        email: "client@example.com",
      },
    });
    vi.mocked(createClient).mockResolvedValue(mock.supabase as never);

    const response = await POST(
      jsonRequest(
        "http://localhost/api/requests",
        createPostPayload({
          clientId: ownClientId,
          status: "completed",
        }),
      ) as NextRequest,
    );
    const body = await readJson<{ success: boolean }>(response);

    expect(response.status).toBe(201);
    expect(body.success).toBe(true);
    expect(mock.getInsertedPayload()).toMatchObject({
      client_id: ownClientId,
      status: "pending",
    });
    expect(dispatchNotification).toHaveBeenCalledOnce();
    expect(dispatchNotification).toHaveBeenCalledWith(
      expect.objectContaining({
        extraEmails: ["platform@example.com"],
      }),
    );
  });

  it("derives the client for client-created requests when clientId is omitted", async () => {
    const mock = createRequestClientMock({
      dbUser: {
        id: "user-1",
        client_id: ownClientId,
        is_super_admin: false,
        name: "Client User",
        email: "client@example.com",
      },
    });
    vi.mocked(createClient).mockResolvedValue(mock.supabase as never);

    const response = await POST(
      jsonRequest(
        "http://localhost/api/requests",
        createPostPayload({
          clientId: undefined,
          status: "completed",
        }),
      ) as NextRequest,
    );

    expect(response.status).toBe(201);
    expect(mock.getInsertedPayload()).toMatchObject({
      client_id: ownClientId,
      status: "pending",
    });
  });

  it("lets admins choose client and status", async () => {
    const mock = createRequestClientMock({
      dbUser: {
        id: "admin-1",
        client_id: null,
        is_super_admin: true,
        name: "Admin User",
        email: "admin@example.com",
      },
    });
    vi.mocked(createClient).mockResolvedValue(mock.supabase as never);

    const response = await POST(
      jsonRequest(
        "http://localhost/api/requests",
        createPostPayload({
          clientId: otherClientId,
          status: "in_progress",
        }),
      ) as NextRequest,
    );

    expect(response.status).toBe(201);
    expect(mock.getInsertedPayload()).toMatchObject({
      client_id: otherClientId,
      status: "in_progress",
    });
    expect(dispatchNotification).toHaveBeenCalledWith(
      expect.objectContaining({
        extraEmails: [],
      }),
    );
  });

  it("rejects admin-created assignments outside the request client or platform staff", async () => {
    vi.mocked(isUserAssignableToClient).mockResolvedValue(false);
    const mock = createRequestClientMock({
      dbUser: {
        id: "admin-1",
        client_id: null,
        is_super_admin: true,
        name: "Admin User",
        email: "admin@example.com",
      },
    });
    vi.mocked(createClient).mockResolvedValue(mock.supabase as never);

    const response = await POST(
      jsonRequest(
        "http://localhost/api/requests",
        createPostPayload({
          clientId: otherClientId,
          assignedTo: "33333333-3333-3333-3333-333333333333",
        }),
      ) as NextRequest,
    );
    const body = await readJson<{ error: { code: string; message: string } }>(response);

    expect(response.status).toBe(400);
    expect(body.error.code).toBe("BAD_REQUEST");
    expect(body.error.message).toContain("Assignee must belong");
    expect(mock.getInsertedPayload()).toBeNull();
  });
});
