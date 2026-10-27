import { beforeEach, describe, expect, it, vi } from "vitest";
import { POST } from "@/app/api/marketing/tools/route";
const mocks = vi.hoisted(() => ({
  auth: vi.fn(),
  scope: vi.fn(),
  ready: vi.fn(),
  insert: vi.fn(),
}));
vi.mock("@/lib/api/resolve-staff-access", () => ({
  resolveStaffAccess: mocks.auth,
}));
vi.mock("@/lib/ai/marketing-skills/access", () => ({
  resolveMarketingToolAccess: mocks.scope,
}));
vi.mock("@/lib/ai/marketing-agents/provider", () => ({
  hasMarketingAiProvider: mocks.ready,
  getMarketingSimulatorUrl: () => null,
  MarketingAgentProviderError: class extends Error {},
}));
vi.mock("@/lib/ai/marketing-skills/execution", async (original) => {
  const module =
    await original<typeof import("@/lib/ai/marketing-skills/execution")>();
  return { ...module, executeMarketingSkill: vi.fn() };
});
vi.mock("@/lib/db", () => ({ db: { insert: mocks.insert } }));
vi.mock("@/lib/rate-limit", () => ({
  rateLimit: vi.fn(async () => ({ success: true })),
}));
const clientId = "11111111-1111-4111-8111-111111111111";
beforeEach(() => {
  vi.clearAllMocks();
  mocks.auth.mockResolvedValue({ userId: "owner", clientId, isStaff: false });
  mocks.scope.mockResolvedValue({ clientId, access: { userId: "owner" } });
  mocks.ready.mockResolvedValue(false);
});
function request(body: unknown) {
  return new Request("http://localhost/api/marketing/tools", {
    method: "POST",
    body: JSON.stringify(body),
  });
}
describe("marketing tool failure boundaries", () => {
  it("authenticates before reading client input", async () => {
    mocks.auth.mockResolvedValue(null);
    const req = request({});
    const read = vi.spyOn(req, "text");
    expect((await POST(req)).status).toBe(401);
    expect(read).not.toHaveBeenCalled();
    expect(mocks.insert).not.toHaveBeenCalled();
  });
  it("rejects foreign client scope without provider access or writes", async () => {
    mocks.scope.mockResolvedValue({ error: "forbidden" });
    expect(
      (
        await POST(
          request({
            clientId,
            skillId: "google-ads",
            brief: "Prepare an evidence-based campaign analysis.",
          }),
        )
      ).status,
    ).toBe(403);
    expect(mocks.ready).not.toHaveBeenCalled();
    expect(mocks.insert).not.toHaveBeenCalled();
  });
  it("returns a clear setup error rather than a fake draft when no provider is configured", async () => {
    const response = await POST(
      request({
        skillId: "google-ads",
        brief: "Prepare an evidence-based campaign analysis.",
      }),
    );
    expect(response.status).toBe(503);
    expect((await response.json()).message).toContain("not configured");
    expect(mocks.insert).not.toHaveBeenCalled();
  });
  it("rejects unknown tools before creating a task", async () => {
    expect(
      (
        await POST(
          request({
            skillId: "shell",
            brief: "Please execute an external command now.",
          }),
        )
      ).status,
    ).toBe(400);
    expect(mocks.insert).not.toHaveBeenCalled();
  });
});
