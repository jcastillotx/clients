import { beforeEach, describe, expect, it, vi } from "vitest";
const mocks = vi.hoisted(() => ({ execute: vi.fn() }));
vi.mock("@/lib/db", () => ({ db: { execute: mocks.execute } }));
import { getClientMarketingPlan } from "@/lib/ai/marketing-skills/plans";
import { marketingSubscriptionActive } from "@/lib/ai/marketing-skills/subscriptions";
import {
  marketingToolGroup,
  marketingToolGroups,
} from "@/lib/ai/marketing-skills/menu";
import { marketingSkills } from "@/lib/ai/marketing-skills/catalog";
beforeEach(() => vi.clearAllMocks());
describe("marketing plan boundaries", () => {
  it("fails closed for a missing or inactive subscription", async () => {
    mocks.execute.mockResolvedValue([]);
    expect(await getClientMarketingPlan("client")).toBeNull();
  });
  it("accepts only known tool groups from saved plan data", async () => {
    mocks.execute.mockResolvedValue([
      { id: "plan", name: "Test", groups: ["content", "admin", "analytics"] },
    ]);
    expect((await getClientMarketingPlan("client"))?.groups).toEqual([
      "content",
      "analytics",
    ]);
  });
  it("does not interpret malformed JSON as unrestricted access", async () => {
    mocks.execute.mockResolvedValue([
      { id: "plan", name: "Test", groups: "all" },
    ]);
    expect((await getClientMarketingPlan("client"))?.groups).toEqual([]);
  });
  it("maps every imported skill to one menu group", () => {
    for (const skill of marketingSkills)
      expect(marketingToolGroup(skill.category)).toBeDefined();
    expect(new Set(marketingToolGroups.map((g) => g.id)).size).toBe(
      marketingToolGroups.length,
    );
  });
  it.each([
    "past_due",
    "unpaid",
    "incomplete",
    "incomplete_expired",
    "canceled",
    "paused",
  ])("does not grant access for %s subscriptions", (status) =>
    expect(marketingSubscriptionActive(status)).toBe(false),
  );
  it.each(["active", "trialing"])("recognizes %s subscriptions", (status) =>
    expect(marketingSubscriptionActive(status)).toBe(true),
  );
});
