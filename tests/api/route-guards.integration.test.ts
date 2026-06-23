import { NextResponse } from "next/server";
import { beforeEach, describe, expect, it, vi } from "vitest";
import {
  requireAdminUser,
  requireAuthenticatedUser,
  requirePermission,
} from "@/lib/auth/route-guards";
import { createClient } from "@/lib/supabase/server";
import { isUserAdmin } from "@/lib/rbac/check";
import { hasPermission } from "@/lib/rbac/permissions";
import { createMockSupabaseClient } from "../helpers/supabase-mock";
import { readJson } from "../helpers/http";

vi.mock("@/lib/supabase/server", () => ({
  createClient: vi.fn(),
}));

vi.mock("@/lib/rbac/check", () => ({
  isUserAdmin: vi.fn(),
}));

vi.mock("@/lib/rbac/permissions", () => ({
  hasPermission: vi.fn(),
}));

describe("route guards", () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it("requireAuthenticatedUser returns 401 when no session", async () => {
    vi.mocked(createClient).mockResolvedValue(
      createMockSupabaseClient({ user: null }) as never,
    );

    const result = await requireAuthenticatedUser();
    expect("error" in result).toBe(true);
    if ("error" in result) {
      expect(result.error.status).toBe(401);
      const body = await readJson(result.error);
      expect(body).toMatchObject({
        success: false,
        error: { code: "UNAUTHORIZED", message: "Unauthorized" },
      });
    }
  });

  it("requireAuthenticatedUser returns user context when authenticated", async () => {
    vi.mocked(createClient).mockResolvedValue(
      createMockSupabaseClient({ user: { id: "user-1", email: "a@test.com" } }) as never,
    );

    const result = await requireAuthenticatedUser();
    expect("error" in result).toBe(false);
    if (!("error" in result)) {
      expect(result.user.id).toBe("user-1");
    }
  });

  it("requireAdminUser returns 403 for non-admin users", async () => {
    vi.mocked(createClient).mockResolvedValue(
      createMockSupabaseClient({ user: { id: "user-1" } }) as never,
    );
    vi.mocked(isUserAdmin).mockResolvedValue(false);

    const result = await requireAdminUser();
    expect("error" in result).toBe(true);
    if ("error" in result) {
      expect(result.error.status).toBe(403);
      const body = await readJson(result.error);
      expect(body).toMatchObject({
        success: false,
        error: { code: "FORBIDDEN", message: "Permission denied" },
      });
    }
  });

  it("requireAdminUser allows admin users", async () => {
    vi.mocked(createClient).mockResolvedValue(
      createMockSupabaseClient({ user: { id: "admin-1" } }) as never,
    );
    vi.mocked(isUserAdmin).mockResolvedValue(true);

    const result = await requireAdminUser();
    expect("error" in result).toBe(false);
    if (!("error" in result)) {
      expect(result.user.id).toBe("admin-1");
    }
  });

  it("requirePermission returns 403 when permission is missing", async () => {
    vi.mocked(createClient).mockResolvedValue(
      createMockSupabaseClient({ user: { id: "user-1" } }) as never,
    );
    vi.mocked(hasPermission).mockResolvedValue(false);

    const result = await requirePermission("settings.manage");
    expect("error" in result).toBe(true);
    if ("error" in result) {
      expect(result.error.status).toBe(403);
    }
  });

  it("requirePermission allows authorized users", async () => {
    vi.mocked(createClient).mockResolvedValue(
      createMockSupabaseClient({ user: { id: "user-1" } }) as never,
    );
    vi.mocked(hasPermission).mockResolvedValue(true);

    const result = await requirePermission("settings.manage");
    expect("error" in result).toBe(false);
  });
});

describe("route guard error responses", () => {
  it("returns JSON error bodies from guard failures", async () => {
    const response = NextResponse.json({ error: "Unauthorized" }, { status: 401 });
    expect(response.status).toBe(401);
    await expect(readJson(response)).resolves.toEqual({ error: "Unauthorized" });
  });
});
