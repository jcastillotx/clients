import { NextRequest, NextResponse } from "next/server";
import { beforeEach, describe, expect, it, vi } from "vitest";
import { GET, POST } from "@/app/api/admin/service-templates/route";
import * as routeGuards from "@/lib/auth/route-guards";
import { jsonRequest, readJson } from "../helpers/http";

vi.mock("@/lib/auth/route-guards", () => ({
  requireAdminUser: vi.fn(),
}));

vi.mock("@/lib/db", () => ({
  isDatabaseConfigurationError: vi.fn().mockReturnValue(false),
  db: {
    select: vi.fn(() => ({
      from: vi.fn(() => ({
        orderBy: vi.fn().mockResolvedValue([
          { id: "template-1", name: "Website Launch", isActive: true },
        ]),
      })),
    })),
    insert: vi.fn(() => ({
      values: vi.fn(() => ({
        returning: vi.fn().mockResolvedValue([
          {
            id: "template-new",
            name: "SEO Package",
            isActive: true,
          },
        ]),
      })),
    })),
  },
}));

const getRequest = () =>
  new NextRequest("http://localhost/api/admin/service-templates");

describe("/api/admin/service-templates", () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it("GET returns 401 for unauthenticated callers", async () => {
    vi.mocked(routeGuards.requireAdminUser).mockResolvedValue({
      error: NextResponse.json({ error: "Unauthorized" }, { status: 401 }),
    });

    const response = await GET(getRequest());
    expect(response.status).toBe(401);
  });

  it("GET returns 403 for authenticated non-admin callers", async () => {
    vi.mocked(routeGuards.requireAdminUser).mockResolvedValue({
      error: NextResponse.json({ error: "Permission denied" }, { status: 403 }),
    });

    const response = await GET(getRequest());
    expect(response.status).toBe(403);
  });

  it("GET returns templates for admin callers", async () => {
    vi.mocked(routeGuards.requireAdminUser).mockResolvedValue({
      user: { id: "admin-1" } as never,
      supabase: {} as never,
    });

    const response = await GET(getRequest());
    const body = await readJson<{ success: boolean; count: number; data: unknown[] }>(response);

    expect(response.status).toBe(200);
    expect(body.success).toBe(true);
    expect(body.count).toBe(1);
    expect(body.data).toHaveLength(1);
  });

  it("POST returns 400 for invalid payloads", async () => {
    vi.mocked(routeGuards.requireAdminUser).mockResolvedValue({
      user: { id: "admin-1" } as never,
      supabase: {} as never,
    });

    const response = await POST(
      jsonRequest("http://localhost/api/admin/service-templates", {
        name: "",
        lineItems: [],
      }) as NextRequest,
    );
    const body = await readJson<{ success: boolean; error: { code: string } }>(response);

    expect(response.status).toBe(400);
    expect(body.success).toBe(false);
    expect(body.error.code).toBe("VALIDATION_ERROR");
  });

  it("POST creates a template for admin callers", async () => {
    vi.mocked(routeGuards.requireAdminUser).mockResolvedValue({
      user: { id: "admin-1" } as never,
      supabase: {} as never,
    });

    const response = await POST(
      jsonRequest("http://localhost/api/admin/service-templates", {
        name: "SEO Package",
        currency: "USD",
        lineItems: [{ description: "Audit", quantity: 1, unitPrice: 500 }],
      }) as NextRequest,
    );
    const body = await readJson<{ success: boolean; data: { id: string } }>(response);

    expect(response.status).toBe(201);
    expect(body.success).toBe(true);
    expect(body.data.id).toBe("template-new");
  });
});
