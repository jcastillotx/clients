import { NextResponse } from "next/server";
import { beforeEach, describe, expect, it, vi } from "vitest";
import { GET, HEAD } from "@/app/api/health/route";
import { createClient } from "@/lib/supabase/server";
import { readJson } from "../helpers/http";

vi.mock("@/lib/supabase/server", () => ({
  createClient: vi.fn(),
}));

describe("GET /api/health", () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it("returns operational when database query succeeds", async () => {
    vi.mocked(createClient).mockResolvedValue({
      from: vi.fn().mockReturnValue({
        select: vi.fn().mockReturnValue({
          limit: vi.fn().mockResolvedValue({ data: [{ id: "client-1" }], error: null }),
        }),
      }),
    } as never);

    const response = await GET(new Request("http://localhost/api/health"));
    const body = await readJson<{
      success: boolean;
      data?: { status: string; services: { database: string } };
      status?: string;
      services?: { database: string };
    }>(response);

    expect(response.status).toBe(200);
    expect(body.success).toBe(true);
    expect(body.data?.status ?? body.status).toBe("operational");
    expect(body.data?.services?.database ?? body.services?.database).toBe("ok");
  });

  it("returns degraded when database query fails", async () => {
    vi.mocked(createClient).mockResolvedValue({
      from: vi.fn().mockReturnValue({
        select: vi.fn().mockReturnValue({
          limit: vi.fn().mockResolvedValue({
            data: null,
            error: { code: "XX000", message: "connection failed" },
          }),
        }),
      }),
    } as never);

    const response = await GET(new Request("http://localhost/api/health"));
    const body = await readJson<{ success: boolean; data?: { status: string; code?: string }; status?: string; code?: string }>(response);

    expect(response.status).toBe(503);
    expect(body.data?.status ?? body.status).toBe("degraded");
    expect(body.data?.code ?? body.code).toBe("XX000");
  });

  it("treats empty table as operational (PGRST116)", async () => {
    vi.mocked(createClient).mockResolvedValue({
      from: vi.fn().mockReturnValue({
        select: vi.fn().mockReturnValue({
          limit: vi.fn().mockResolvedValue({
            data: null,
            error: { code: "PGRST116", message: "no rows" },
          }),
        }),
      }),
    } as never);

    const response = await GET(new Request("http://localhost/api/health"));
    expect(response.status).toBe(200);
  });
});

describe("HEAD /api/health", () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it("returns 503 when database query fails", async () => {

    vi.mocked(createClient).mockResolvedValue({
      from: vi.fn().mockReturnValue({
        select: vi.fn().mockReturnValue({
          limit: vi.fn().mockResolvedValue({
            data: null,
            error: { code: "XX000", message: "connection failed" },
          }),
        }),
      }),
    } as never);

    const response = await HEAD();
    expect(response.status).toBe(503);
  });
});
