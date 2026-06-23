import { beforeEach, describe, expect, it, vi } from "vitest";
import { GET } from "@/app/api/clients/route";
import { createClient } from "@/lib/supabase/server";
import { createMockSupabaseClient } from "../helpers/supabase-mock";
import { readJson } from "../helpers/http";

vi.mock("@/lib/supabase/server", () => ({
  createClient: vi.fn(),
}));

describe("GET /api/clients", () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it("returns 401 when the caller is not authenticated", async () => {
    vi.mocked(createClient).mockResolvedValue(
      createMockSupabaseClient({ user: null }) as never,
    );

    const response = await GET(new Request("http://localhost/api/clients"));
    const body = await readJson<{ error: { message: string } }>(response);

    expect(response.status).toBe(401);
    expect(body.error.message).toBe("Authentication required");
  });

  it("returns active clients for authenticated users", async () => {
    const clientsChain = {
      select: vi.fn().mockReturnThis(),
      is: vi.fn().mockReturnThis(),
      order: vi.fn().mockReturnThis(),
      eq: vi.fn().mockReturnThis(),
      range: vi.fn().mockReturnThis(),
      then: (resolve: (value: unknown) => unknown) =>
        Promise.resolve({
          data: [{ id: "client-1", company_name: "Acme", status: "active" }],
          error: null,
          count: 1,
        }).then(resolve),
    };

    vi.mocked(createClient).mockResolvedValue({
      auth: {
        getUser: vi.fn().mockResolvedValue({
          data: { user: { id: "user-1" } },
          error: null,
        }),
      },
      from: vi.fn().mockReturnValue(clientsChain),
    } as never);

    const response = await GET(new Request("http://localhost/api/clients"));
    const body = await readJson<{ success: boolean; data: Array<{ id: string }> }>(response);

    expect(response.status).toBe(200);
    expect(body.success).toBe(true);
    expect(body.data).toHaveLength(1);
    expect(body.data[0]?.id).toBe("client-1");
  });
});
