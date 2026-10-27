import { NextRequest } from "next/server";
import { beforeEach, describe, expect, it, vi } from "vitest";
const m = vi.hoisted(() => ({
  auth: vi.fn(),
  access: vi.fn(),
  limit: vi.fn(),
  returning: vi.fn(),
  update: vi.fn(),
}));
vi.mock("@/lib/supabase/server", () => ({
  createClient: async () => ({ auth: { getUser: m.auth } }),
}));
vi.mock("@/lib/auth/route-access", () => ({
  resolveRouteAccess: m.access,
  canAccessClient: (access: { clientId: string }, clientId: string) =>
    access.clientId === clientId,
}));
vi.mock("@/lib/db", () => ({
  isDatabaseConfigurationError: () => false,
  db: {
    select: () => ({ from: () => ({ where: () => ({ limit: m.limit }) }) }),
    update: m.update,
  },
}));
import { POST } from "@/app/api/proposals/[id]/cancel/route";
const invoke = () =>
  POST(
    new NextRequest("http://localhost/api/proposals/fixture/cancel", {
      method: "POST",
    }),
    { params: Promise.resolve({ id: "fixture" }) },
  );
beforeEach(() => {
  vi.clearAllMocks();
  m.auth.mockResolvedValue({ data: { user: { id: "user" } } });
  m.access.mockResolvedValue({ clientId: "own" });
  m.update.mockReturnValue({
    set: () => ({ where: () => ({ returning: m.returning }) }),
  });
});
describe("proposal cancellation authorization and transitions", () => {
  it("does not disclose acceptance status to a different tenant", async () => {
    m.limit.mockResolvedValue([
      { id: "fixture", clientId: "foreign", status: "accepted" },
    ]);
    const response = await invoke();
    expect(response.status).toBe(403);
    expect(JSON.stringify(await response.json())).not.toContain("accepted");
    expect(m.update).not.toHaveBeenCalled();
  });
  it("preserves the accepted-proposal restriction for the owning client", async () => {
    m.limit.mockResolvedValue([
      { id: "fixture", clientId: "own", status: "accepted" },
    ]);
    expect((await invoke()).status).toBe(400);
    expect(m.update).not.toHaveBeenCalled();
  });
  it("reports a conflict when the conditional cancellation loses a race", async () => {
    m.limit.mockResolvedValue([
      { id: "fixture", clientId: "own", status: "sent" },
    ]);
    m.returning.mockResolvedValue([]);
    expect((await invoke()).status).toBe(409);
  });
});
