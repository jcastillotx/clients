import { describe, expect, it } from "vitest";
import { marketingAgentRunRequestSchema } from "./validation";

const clientId = "a9c41562-2b73-4c41-a8b0-2a496f1aad81";

describe("marketingAgentRunRequestSchema", () => {
  it("accepts a client-scoped campaign plan request", () => {
    const result = marketingAgentRunRequestSchema.safeParse({
      clientId,
      workflowId: "campaign_plan",
      campaignName: "Fall acquisition",
      objective: "Generate qualified opportunities for the sales team.",
      targetAudience: "Operations leaders at growing service companies.",
      budget: 5000,
      channels: ["linkedin", "email"],
    });

    expect(result.success).toBe(true);
    if (result.success) {
      expect(result.data.createDrafts).toBe(true);
      expect(result.data.clientId).toBe(clientId);
    }
  });

  it("rejects a run without a valid client id", () => {
    const result = marketingAgentRunRequestSchema.safeParse({
      clientId: "selected-by-the-model",
      workflowId: "quality_check",
      content: "Review this copy.",
    });

    expect(result.success).toBe(false);
  });

  it("limits generated calendar volume", () => {
    const result = marketingAgentRunRequestSchema.safeParse({
      clientId,
      workflowId: "content_calendar",
      objective: "Build awareness for the upcoming service launch.",
      targetAudience: "Local small business owners.",
      startDate: "2026-08-01",
      numberOfItems: 100,
      platforms: ["linkedin"],
    });

    expect(result.success).toBe(false);
  });
});
