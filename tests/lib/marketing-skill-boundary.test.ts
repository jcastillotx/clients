import { afterEach, describe, expect, it, vi } from "vitest";
import {
  marketingSkills,
  getMarketingSkill,
} from "@/lib/ai/marketing-skills/catalog";
import { canAccessMarketingClient } from "@/lib/ai/marketing-skills/access";
import { getMarketingSimulatorUrl } from "@/lib/ai/marketing-agents/provider";
import { serializeDrizzleJson } from "@/lib/db/json-serialization";
import {
  skillArtifactSchema,
  skillRunSchema,
} from "@/lib/ai/marketing-skills/execution";

afterEach(() => vi.unstubAllEnvs());
describe("client marketing skill boundaries", () => {
  it("includes all 48 marketing methods and excludes repository administration", () => {
    expect(marketingSkills).toHaveLength(48);
    expect(new Set(marketingSkills.map((s) => s.id)).size).toBe(48);
    for (const skill of marketingSkills) {
      expect(skill.methodology.length).toBeGreaterThan(100);
      expect(skill.sourceSha256).toMatch(/^[a-f0-9]{64}$/);
      expect(
        skillRunSchema.safeParse({
          skillId: skill.id,
          brief: "Prepare a draft using supplied evidence.",
        }).success,
      ).toBe(true);
    }
    expect(getMarketingSkill("safe-push")).toBeUndefined();
    expect(getMarketingSkill("repo-scaffold")).toBeUndefined();
  });
  it("never grants a client access to another client's context", () => {
    expect(
      canAccessMarketingClient({ isStaff: false, clientId: "a" }, "a"),
    ).toBe(true);
    expect(
      canAccessMarketingClient({ isStaff: false, clientId: "a" }, "b"),
    ).toBe(false);
    expect(
      canAccessMarketingClient({ isStaff: false, clientId: null }, "b"),
    ).toBe(false);
  });
  it("refuses invented tools and unstructured provider responses", () => {
    expect(
      skillRunSchema.safeParse({
        skillId: "shell",
        brief: "Run a system command for this account.",
      }).success,
    ).toBe(false);
    expect(skillArtifactSchema.safeParse({ analysis: "Success" }).success).toBe(
      false,
    );
  });
  it("ignores simulator configuration outside isolated staging", () => {
    vi.stubEnv("STAGING_ISOLATED", "0");
    vi.stubEnv(
      "MARKETING_AI_SIMULATOR_URL",
      "http://127.0.0.1:3102/v1/chat/completions",
    );
    expect(getMarketingSimulatorUrl()).toBeNull();
  });
  it.each([
    "https://example.com/api",
    "http://169.254.169.254/api",
    "file:///tmp/example",
  ])("rejects nonlocal simulator destination %s", (url) => {
    vi.stubEnv("STAGING_ISOLATED", "1");
    vi.stubEnv("MARKETING_AI_SIMULATOR_URL", url);
    expect(() => getMarketingSimulatorUrl()).toThrow("localhost");
  });
  it("preserves Drizzle's JSON wire representation without nesting it in a JSON string", () => {
    const structured = {
      parameters: { workflowId: "marketing_skill_library" },
      channels: ["search"],
    };
    const encodedByDrizzle = JSON.stringify(structured);
    expect(JSON.parse(serializeDrizzleJson(encodedByDrizzle))).toEqual(
      structured,
    );
    expect(
      JSON.parse(serializeDrizzleJson(JSON.stringify("intentional scalar"))),
    ).toBe("intentional scalar");
  });
});
