import { beforeEach, describe, expect, it, vi } from "vitest";
const m = vi.hoisted(() => ({
  select: vi.fn(),
  limit: vi.fn(),
  update: vi.fn(),
  provider: vi.fn(),
}));
vi.mock("@/lib/db", () => ({ db: { select: m.select, update: m.update } }));
vi.mock("@/lib/ai/marketing-agents/provider", () => ({
  runJsonMarketingAgent: m.provider,
}));
import { executeMarketingAgentTask } from "@/lib/ai/marketing-agents/execution";
const clientId = "00000000-0000-4000-8000-000000000001";
beforeEach(() => {
  vi.clearAllMocks();
  m.select.mockReturnValue({
    from: () => ({ where: () => ({ orderBy: () => ({ limit: m.limit }) }) }),
  });
});
describe("marketing-agent redelivery", () => {
  it("keeps completed task results and drafts without another provider call", async () => {
    m.limit.mockResolvedValue([
      {
        id: "task",
        clientId,
        status: "completed",
        input: {
          parameters: {
            workflowId: "quality_check",
            clientId,
            content: "Reviewed fixture",
            evidence: "",
          },
        },
      },
    ]);
    await executeMarketingAgentTask("task");
    expect(m.update).not.toHaveBeenCalled();
    expect(m.provider).not.toHaveBeenCalled();
  });
  it("still rejects invalid client scope on a completed task", async () => {
    m.limit.mockResolvedValue([
      {
        id: "task",
        clientId,
        status: "completed",
        input: {
          parameters: {
            workflowId: "quality_check",
            clientId: "00000000-0000-4000-8000-000000000002",
            content: "Reviewed fixture",
            evidence: "",
          },
        },
      },
    ]);
    await expect(executeMarketingAgentTask("task")).rejects.toThrow(
      "client scope",
    );
    expect(m.update).not.toHaveBeenCalled();
  });
});
