import { describe, expect, it } from "vitest";
import { validateIntegrationProviderCategory } from "./integration-validation";

describe("integration validation", () => {
  it("accepts configured AI providers with the matching category", () => {
    expect(validateIntegrationProviderCategory("openai", "ai")).toEqual({
      success: true,
      provider: "openai",
      category: "ai",
    });
  });

  it("uses the provider category when category is omitted", () => {
    expect(validateIntegrationProviderCategory("anthropic")).toEqual({
      success: true,
      provider: "anthropic",
      category: "ai",
    });
  });

  it("accepts Google Workspace and Office 365 as email providers", () => {
    expect(validateIntegrationProviderCategory("google_workspace", "email")).toEqual({
      success: true,
      provider: "google_workspace",
      category: "email",
    });
    expect(validateIntegrationProviderCategory("office365", "email")).toEqual({
      success: true,
      provider: "office365",
      category: "email",
    });
  });

  it("rejects unknown providers", () => {
    expect(validateIntegrationProviderCategory("unknown", "ai")).toEqual({
      success: false,
      error: "Unsupported integration provider.",
    });
  });

  it("rejects mismatched categories", () => {
    expect(validateIntegrationProviderCategory("openai", "payments")).toEqual({
      success: false,
      error: "OpenAI must be saved under the ai category.",
    });
  });
});
