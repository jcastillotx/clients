import { beforeEach, describe, expect, it, vi } from "vitest";
import { NextRequest } from "next/server";
import { POST as createUser } from "@/app/api/admin/users/route";
import {
  PATCH as updateUser,
  DELETE as deleteUser,
} from "@/app/api/admin/users/[id]/route";
import { POST as resetPassword } from "@/app/api/admin/users/[id]/reset-password/route";
import { POST as createClient } from "@/app/api/clients/route";
import { POST as backfill } from "@/app/api/admin/trigger-backfill/route";
const mocks = vi.hoisted(() => ({ user: vi.fn(), privileged: vi.fn() }));
vi.mock("@/lib/supabase/server", () => ({
  createClient: vi.fn(async () => ({ auth: { getUser: mocks.user } })),
  createAdminClient: mocks.privileged,
}));
vi.mock("@/lib/rbac/permissions", async (original) => ({
  ...(await original<typeof import("@/lib/rbac/permissions")>()),
  hasPermission: vi.fn(async () => false),
  hasAnyRole: vi.fn(async () => false),
}));
vi.mock("@/lib/db", () => ({ db: {} }));
vi.mock("@/lib/inngest/client", () => ({ inngest: { send: vi.fn() } }));
vi.mock("@/lib/email/send-welcome-email", () => ({
  sendWelcomeEmail: vi.fn(),
}));

describe("editable metadata cannot grant management rights", () => {
  beforeEach(() => {
    vi.clearAllMocks();
    mocks.user.mockResolvedValue({
      data: {
        user: {
          id: "ordinary-client",
          user_metadata: {
            role: "admin",
            app_role: "super_admin",
            is_super_admin: true,
          },
        },
      },
    });
  });
  const params = { params: Promise.resolve({ id: "target-user" }) };
  it.each([
    [
      "create user",
      () =>
        createUser(
          new Request("https://portal.test/api/admin/users", {
            method: "POST",
          }),
        ),
    ],
    [
      "update user",
      () =>
        updateUser(
          new Request("https://portal.test/api/admin/users/id", {
            method: "PATCH",
          }),
          params,
        ),
    ],
    [
      "delete user",
      () =>
        deleteUser(
          new Request("https://portal.test/api/admin/users/id", {
            method: "DELETE",
          }),
          params,
        ),
    ],
    [
      "reset password",
      () =>
        resetPassword(
          new Request("https://portal.test/api/admin/users/id/reset-password", {
            method: "POST",
          }),
          params,
        ),
    ],
    [
      "create company",
      () =>
        createClient(
          new NextRequest("https://portal.test/api/clients", {
            method: "POST",
          }),
        ),
    ],
    [
      "run backfill",
      () =>
        backfill(
          new Request("https://portal.test/api/admin/trigger-backfill", {
            method: "POST",
          }),
        ),
    ],
  ] as const)("denies %s before privileged access", async (_name, action) => {
    expect((await action()).status).toBe(403);
    expect(mocks.privileged).not.toHaveBeenCalled();
  });
});
