import { describe, expect, it } from "vitest";
import { runMarketingQualityCheck } from "./quality-check";

describe("runMarketingQualityCheck", () => {
  it("passes clear content that follows the brand guide", () => {
    const result = runMarketingQualityCheck({
      content: "Build a practical campaign plan for your next quarter. Talk with our team to review the options.",
      voiceAvoid: "cheap, guaranteed",
    });

    expect(result.decision).toBe("PASS");
    expect(result.score).toBe(100);
    expect(result.findings).toEqual([]);
  });

  it("blocks language prohibited by the client brand guide", () => {
    const result = runMarketingQualityCheck({
      content: "This cheap offer is guaranteed to transform your business.",
      voiceAvoid: "cheap\nguaranteed",
    });

    expect(result.decision).toBe("BLOCKED");
    expect(result.checks.bannedLanguage).toBe(false);
    expect(result.findings[0]).toMatchObject({
      severity: "critical",
      category: "brand_voice",
    });
  });

  it("warns when numeric claims do not have supporting evidence", () => {
    const result = runMarketingQualityCheck({
      content: "Increase qualified leads by 40% while generating a 3x return.",
      evidence: "",
    });

    expect(result.decision).toBe("WARN");
    expect(result.checks.unsupportedClaims).toBe(false);
  });

  it("accepts numeric claims when their numbers appear in the evidence", () => {
    const result = runMarketingQualityCheck({
      content: "The campaign delivered a 40% improvement.",
      evidence: "Approved analytics report: 40% improvement, Q2.",
    });

    expect(result.decision).toBe("PASS");
    expect(result.checks.unsupportedClaims).toBe(true);
  });
});
