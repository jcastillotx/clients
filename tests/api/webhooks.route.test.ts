import { NextRequest } from "next/server";
import { beforeEach, describe, expect, it, vi } from "vitest";
import { DELETE, GET, POST } from "@/app/api/webhooks/route";
import { createClient } from "@/lib/supabase/server";
import * as routeAccess from "@/lib/auth/route-access";
import { createMockSupabaseClient } from "../helpers/supabase-mock";
import { jsonRequest, readJson } from "../helpers/http";

vi.mock("@/lib/supabase/server", () => ({
  createClient: vi.fn(),
}));

vi.mock("@/lib/auth/route-access", () => ({
  resolveRouteAccess: vi.fn(),
  canAccessClient: vi.fn(),
}));

vi.mock("@/lib/db", () => ({
  db: {
    select: vi.fn(),
    insert: vi.fn(),
    update: vi.fn(),
    delete: vi.fn(),
  },
}));

const clientId = "11111111-1111-1111-1111-111111111111";
const webhookId = "22222222-2222-2222-2222-222222222222";

describe("/api/webhooks", () => {
  beforeEach(() => {
    vi.clearAllMocks();
    vi.mocked(routeAccess.resolveRouteAccess).mockResolvedValue({
      clientId,
      isAdmin: true,
    });
    vi.mocked(routeAccess.canAccessClient).mockReturnValue(true);
  });

  it("GET returns 401 when unauthenticated", async () => {
    vi.mocked(createClient).mockResolvedValue(
      createMockSupabaseClient({ user: null }) as never,
    );

    const response = await GET(
      new NextRequest(`http://localhost/api/webhooks?clientId=${clientId}`),
    );
    expect(response).toBeDefined();
    const body = await readJson<{ success: boolean; error: { code: string } }>(response!);

    expect(response!.status).toBe(401);
    expect(body.success).toBe(false);
    expect(body.error.code).toBe("UNAUTHORIZED");
  });

  it("GET returns 400 when clientId is missing", async () => {
    vi.mocked(createClient).mockResolvedValue(
      createMockSupabaseClient({ user: { id: "user-1" } }) as never,
    );

    const response = await GET(new NextRequest("http://localhost/api/webhooks"));
    expect(response).toBeDefined();
    const body = await readJson<{ success: boolean; error: { code: string } }>(response!);

    expect(response!.status).toBe(400);
    expect(body.error.code).toBe("BAD_REQUEST");
  });

  it("GET returns webhook endpoints for authorized users", async () => {
    vi.mocked(createClient).mockResolvedValue(
      createMockSupabaseClient({ user: { id: "user-1" } }) as never,
    );

    const { db } = await import("@/lib/db");
    vi.mocked(db.select).mockReturnValue({
      from: vi.fn().mockReturnValue({
        where: vi.fn().mockReturnValue({
          orderBy: vi.fn().mockResolvedValue([
            {
              id: webhookId,
              url: "https://example.com/hook",
              events: ["invoice.paid"],
              isActive: true,
              secret: "secret-value",
              createdAt: new Date().toISOString(),
            },
          ]),
        }),
      }),
    } as never);

    const response = await GET(
      new NextRequest(`http://localhost/api/webhooks?clientId=${clientId}`),
    );
    expect(response).toBeDefined();
    const body = await readJson<{ success: boolean; data: Array<{ id: string; secret?: string }> }>(
      response!,
    );

    expect(response!.status).toBe(200);
    expect(body.success).toBe(true);
    expect(body.data).toHaveLength(1);
    expect(body.data[0]?.id).toBe(webhookId);
    expect(body.data[0]?.secret).toBeUndefined();
  });

  it("POST returns 401 when unauthenticated", async () => {
    vi.mocked(createClient).mockResolvedValue(
      createMockSupabaseClient({ user: null }) as never,
    );

    const response = await POST(
      jsonRequest("http://localhost/api/webhooks", {
        clientId,
        url: "https://example.com/hook",
        events: ["invoice.paid"],
      }) as NextRequest,
    );

    expect(response).toBeDefined();
    expect(response!.status).toBe(401);
  });

  it("DELETE returns 400 when id is missing", async () => {
    vi.mocked(createClient).mockResolvedValue(
      createMockSupabaseClient({ user: { id: "user-1" } }) as never,
    );

    const response = await DELETE(new NextRequest("http://localhost/api/webhooks"));
    expect(response).toBeDefined();
    const body = await readJson<{ success: boolean; error: { code: string } }>(response!);

    expect(response!.status).toBe(400);
    expect(body.error.code).toBe("BAD_REQUEST");
  });
});
