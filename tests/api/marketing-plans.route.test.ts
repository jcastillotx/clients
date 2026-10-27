import { beforeEach, describe, expect, it, vi } from "vitest";
const m = vi.hoisted(() => ({ auth: vi.fn(), execute: vi.fn() }));
vi.mock("@/lib/api/resolve-staff-access", () => ({
  resolveStaffAccess: m.auth,
}));
vi.mock("@/lib/db", () => ({ db: { execute: m.execute } }));
import { PATCH } from "@/app/api/marketing/plans/route";
beforeEach(() => {
  vi.clearAllMocks();
});
describe("plan management authorization", () => {
  it("requires authentication before reading input", async () => {
    m.auth.mockResolvedValue(null);
    const req = new Request("http://localhost", { method: "PATCH" });
    const read = vi.spyOn(req, "json");
    expect((await PATCH(req)).status).toBe(401);
    expect(read).not.toHaveBeenCalled();
  });
  it("prevents clients from publishing their own entitlements", async () => {
    m.auth.mockResolvedValue({ isAdmin: false });
    expect(
      (await PATCH(new Request("http://localhost", { method: "PATCH" })))
        .status,
    ).toBe(403);
    expect(m.execute).not.toHaveBeenCalled();
  });
  it("rejects unknown groups and invalid prices for administrators", async () => {
    m.auth.mockResolvedValue({ isAdmin: true });
    const req = new Request("http://localhost", {
      method: "PATCH",
      body: JSON.stringify({
        id: "11111111-1111-4111-8111-111111111111",
        name: "Unsafe",
        monthlyPriceCents: -1,
        groups: ["admin"],
        published: true,
      }),
    });
    expect((await PATCH(req)).status).toBe(400);
    expect(m.execute).not.toHaveBeenCalled();
  });
});
