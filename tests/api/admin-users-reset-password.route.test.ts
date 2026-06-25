import { beforeEach, describe, expect, it, vi } from "vitest";
import { POST } from "@/app/api/admin/users/[id]/reset-password/route";
import { createAdminClient, createClient } from "@/lib/supabase/server";
import { hasAnyRole, hasPermission } from "@/lib/rbac/permissions";
import { auditLog } from "@/lib/logger";
import { readJson } from "../helpers/http";

vi.mock("@/lib/supabase/server", () => ({
  createClient: vi.fn(),
  createAdminClient: vi.fn(),
}));

vi.mock("@/lib/rbac/permissions", () => ({
  hasAnyRole: vi.fn(),
  hasPermission: vi.fn(),
  Permissions: {
    USERS_MANAGE: "users.manage",
  },
  Roles: {
    SUPER_ADMIN: "super_admin",
    ADMIN: "admin",
    ACCOUNT_MANAGER: "account_manager",
  },
}));

vi.mock("@/lib/supabase/redirect-url", () => ({
  getAuthConfirmUrl: vi.fn().mockReturnValue("https://app.example.test/auth/confirm?next=%2Freset-password"),
}));

vi.mock("@/lib/logger", () => ({
  auditLog: vi.fn(),
  logger: {
    error: vi.fn(),
  },
}));

function mockSessionUser(user: { id: string; user_metadata?: Record<string, unknown> } | null) {
  vi.mocked(createClient).mockResolvedValue({
    auth: {
      getUser: vi.fn().mockResolvedValue({
        data: { user },
        error: null,
      }),
    },
  } as never);
}

function mockAdminClient(targetUser: {
  id: string;
  email: string | null;
  deleted_at?: string | null;
} | null) {
  const maybeSingle = vi.fn().mockResolvedValue({
    data: targetUser,
    error: null,
  });
  const resetPasswordForEmail = vi.fn().mockResolvedValue({ error: null });

  vi.mocked(createAdminClient).mockReturnValue({
    from: vi.fn().mockReturnValue({
      select: vi.fn().mockReturnThis(),
      eq: vi.fn().mockReturnThis(),
      maybeSingle,
    }),
    auth: {
      resetPasswordForEmail,
    },
  } as never);

  return { maybeSingle, resetPasswordForEmail };
}

describe("POST /api/admin/users/[id]/reset-password", () => {
  beforeEach(() => {
    vi.clearAllMocks();
    vi.mocked(hasPermission).mockResolvedValue(true);
    vi.mocked(hasAnyRole).mockResolvedValue(false);
  });

  it("returns 401 when unauthenticated", async () => {
    mockSessionUser(null);

    const response = await POST(
      new Request("http://localhost/api/admin/users/user-1/reset-password"),
      { params: Promise.resolve({ id: "user-1" }) },
    );
    const body = await readJson<{ error: { code: string } }>(response);

    expect(response.status).toBe(401);
    expect(body.error.code).toBe("UNAUTHORIZED");
    expect(createAdminClient).not.toHaveBeenCalled();
  });

  it("returns 403 when caller is not an admin or user manager", async () => {
    mockSessionUser({ id: "operator-1", user_metadata: { role: "account_manager" } });
    vi.mocked(hasPermission).mockResolvedValue(false);
    vi.mocked(hasAnyRole).mockResolvedValue(false);

    const response = await POST(
      new Request("http://localhost/api/admin/users/user-1/reset-password"),
      { params: Promise.resolve({ id: "user-1" }) },
    );
    const body = await readJson<{ error: { code: string } }>(response);

    expect(response.status).toBe(403);
    expect(body.error.code).toBe("FORBIDDEN");
    expect(createAdminClient).not.toHaveBeenCalled();
  });

  it("returns 404 when target user is missing", async () => {
    mockSessionUser({ id: "admin-1", user_metadata: { role: "admin" } });
    mockAdminClient(null);

    const response = await POST(
      new Request("http://localhost/api/admin/users/missing/reset-password"),
      { params: Promise.resolve({ id: "missing" }) },
    );
    const body = await readJson<{ error: { code: string; message: string } }>(response);

    expect(response.status).toBe(404);
    expect(body.error.code).toBe("NOT_FOUND");
    expect(body.error.message).toBe("User not found");
  });

  it("rejects password resets for deleted users", async () => {
    mockSessionUser({ id: "admin-1", user_metadata: { role: "admin" } });
    mockAdminClient({
      id: "user-1",
      email: "client@example.com",
      deleted_at: "2026-06-25T00:00:00.000Z",
    });

    const response = await POST(
      new Request("http://localhost/api/admin/users/user-1/reset-password"),
      { params: Promise.resolve({ id: "user-1" }) },
    );
    const body = await readJson<{ error: { code: string; message: string } }>(response);

    expect(response.status).toBe(400);
    expect(body.error.code).toBe("BAD_REQUEST");
    expect(body.error.message).toBe("Cannot reset password for a deleted user");
  });

  it("sends a Supabase recovery email with the app reset-password redirect", async () => {
    mockSessionUser({ id: "admin-1", user_metadata: { role: "admin" } });
    const { resetPasswordForEmail } = mockAdminClient({
      id: "user-1",
      email: "client@example.com",
      deleted_at: null,
    });

    const response = await POST(
      new Request("http://localhost/api/admin/users/user-1/reset-password"),
      { params: Promise.resolve({ id: "user-1" }) },
    );
    const body = await readJson<{
      success: boolean;
      data: { sent: boolean };
      message: string;
    }>(response);

    expect(response.status).toBe(200);
    expect(body.success).toBe(true);
    expect(body.data.sent).toBe(true);
    expect(body.message).toBe("Password reset email sent");
    expect(resetPasswordForEmail).toHaveBeenCalledWith("client@example.com", {
      redirectTo: "https://app.example.test/auth/confirm?next=%2Freset-password",
    });
    expect(auditLog).toHaveBeenCalledWith("admin.password_reset_sent", "admin-1", {
      targetUserId: "user-1",
    });
  });
});
