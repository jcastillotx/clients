import { beforeEach, describe, expect, it, vi } from "vitest";
const m = vi.hoisted(() => ({
  auth: vi.fn(),
  execute: vi.fn(),
  access: vi.fn(),
}));
vi.mock("@/lib/api/resolve-staff-access", () => ({
  resolveStaffAccess: m.auth,
}));
vi.mock("@/lib/db", () => ({ db: { execute: m.execute } }));
vi.mock("@/lib/marketing/connection-access", () => ({
  marketingConnectionAccess: m.access,
}));
import { GET, POST, DELETE } from "@/app/api/marketing/connections/route";
beforeEach(() => vi.clearAllMocks());
describe("marketing connection authentication", () => {
  it.each([
    ["GET", GET],
    ["POST", POST],
    ["DELETE", DELETE],
  ] as const)(
    "requires authentication before %s database access",
    async (method, handler) => {
      m.auth.mockResolvedValue(null);
      const request = new Request(
        "http://localhost/api/marketing/connections",
        { method },
      );
      const read = vi.spyOn(request, "json");
      expect((await handler(request)).status).toBe(401);
      expect(read).not.toHaveBeenCalled();
      expect(m.execute).not.toHaveBeenCalled();
    },
  );
  it("rejects users without a tenant before listing saved connections", async () => {
    m.auth.mockResolvedValue({ userId: "user", clientId: null });
    expect((await GET(new Request("http://localhost"))).status).toBe(403);
    expect(m.execute).not.toHaveBeenCalled();
  });
});
