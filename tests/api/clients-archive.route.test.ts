import { beforeEach, describe, expect, it, vi } from "vitest";
import { DELETE, POST } from "@/app/api/clients/[id]/archive/route";
import { createClient } from "@/lib/supabase/server";
import { hasAnyRole, hasPermission } from "@/lib/rbac/permissions";
import {
  archiveClientWithRecords,
  restoreClientWithRecords,
} from "@/lib/clients/archive-client";
import { createMockSupabaseClient } from "../helpers/supabase-mock";
import { readJson } from "../helpers/http";

vi.mock("@/lib/supabase/server", () => ({
  createClient: vi.fn(),
}));

vi.mock("@/lib/rbac/permissions", () => ({
  hasPermission: vi.fn(),
  hasAnyRole: vi.fn(),
  Permissions: { CLIENTS_DELETE: "clients.delete" },
  Roles: {
    SUPER_ADMIN: "super_admin",
    ADMIN: "admin",
    ACCOUNT_MANAGER: "account_manager",
  },
}));

vi.mock("@/lib/clients/archive-client", () => ({
  archiveClientWithRecords: vi.fn(),
  restoreClientWithRecords: vi.fn(),
}));

const clientId = "11111111-1111-1111-1111-111111111111";
const params = Promise.resolve({ id: clientId });

describe("/api/clients/[id]/archive", () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it("POST returns 401 when unauthenticated", async () => {
    vi.mocked(createClient).mockResolvedValue(
      createMockSupabaseClient({ user: null }) as never,
    );

    const response = await POST(new Request(`http://localhost/api/clients/${clientId}/archive`), {
      params,
    });
    expect(response).toBeDefined();
    const body = await readJson<{ success: boolean; error: { code: string } }>(response!);

    expect(response!.status).toBe(401);
    expect(body.error.code).toBe("UNAUTHORIZED");
  });

  it("POST returns 403 when caller lacks archive permission", async () => {
    vi.mocked(createClient).mockResolvedValue(
      createMockSupabaseClient({
        user: { id: "user-1" },
      }) as never,
    );
    vi.mocked(hasPermission).mockResolvedValue(false);
    vi.mocked(hasAnyRole).mockResolvedValue(false);

    const response = await POST(new Request(`http://localhost/api/clients/${clientId}/archive`), {
      params,
    });
    expect(response).toBeDefined();
    const body = await readJson<{ success: boolean; error: { code: string } }>(response!);

    expect(response!.status).toBe(403);
    expect(body.error.code).toBe("FORBIDDEN");
  });

  it("POST archives a client for authorized callers", async () => {
    vi.mocked(createClient).mockResolvedValue(
      createMockSupabaseClient({ user: { id: "admin-1" } }) as never,
    );
    vi.mocked(hasPermission).mockResolvedValue(true);
    vi.mocked(hasAnyRole).mockResolvedValue(true);
    vi.mocked(archiveClientWithRecords).mockResolvedValue({
      client: { id: clientId, companyName: "Acme", deletedAt: new Date() },
      alreadyArchived: false,
      summaries: [{ table: "requests", updated: 2 }],
    });

    const response = await POST(new Request(`http://localhost/api/clients/${clientId}/archive`), {
      params,
    });
    expect(response).toBeDefined();
    const body = await readJson<{
      success: boolean;
      data: { archivedRecords: Array<{ table: string; updated: number }> };
      archivedRecords: Array<{ table: string; updated: number }>;
    }>(response!);

    expect(response!.status).toBe(200);
    expect(body.success).toBe(true);
    expect(body.data.archivedRecords).toHaveLength(1);
    expect(body.archivedRecords?.[0]?.updated).toBe(2);
  });

  it("DELETE returns 404 when client is not found", async () => {
    vi.mocked(createClient).mockResolvedValue(
      createMockSupabaseClient({ user: { id: "admin-1" } }) as never,
    );
    vi.mocked(hasPermission).mockResolvedValue(true);
    vi.mocked(hasAnyRole).mockResolvedValue(true);
    vi.mocked(restoreClientWithRecords).mockResolvedValue({
      client: null,
      alreadyRestored: false,
      summaries: [],
    });

    const response = await DELETE(
      new Request(`http://localhost/api/clients/${clientId}/archive`),
      { params },
    );
    expect(response).toBeDefined();
    const body = await readJson<{ success: boolean; error: { code: string } }>(response!);

    expect(response!.status).toBe(404);
    expect(body.error.code).toBe("NOT_FOUND");
  });

  it("DELETE restores an archived client for authorized callers", async () => {
    vi.mocked(createClient).mockResolvedValue(
      createMockSupabaseClient({ user: { id: "admin-1" } }) as never,
    );
    vi.mocked(hasPermission).mockResolvedValue(true);
    vi.mocked(hasAnyRole).mockResolvedValue(true);
    vi.mocked(restoreClientWithRecords).mockResolvedValue({
      client: { id: clientId, companyName: "Acme", deletedAt: null },
      alreadyRestored: false,
      summaries: [{ table: "requests", updated: 2 }],
    });

    const response = await DELETE(
      new Request(`http://localhost/api/clients/${clientId}/archive`),
      { params },
    );
    expect(response).toBeDefined();
    const body = await readJson<{
      success: boolean;
      data: { restoredRecords: Array<{ table: string; updated: number }> };
      restoredRecords: Array<{ table: string; updated: number }>;
    }>(response!);

    expect(response!.status).toBe(200);
    expect(body.success).toBe(true);
    expect(body.data.restoredRecords).toHaveLength(1);
    expect(body.restoredRecords?.[0]?.updated).toBe(2);
  });
});
