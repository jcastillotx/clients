import { describe, expect, it } from "vitest";
import { selectMarketingProvider } from "@/lib/ai/marketing-agents/provider-config";
describe("marketing provider credential destinations", () => {
  it("routes a platform OpenRouter key only to OpenRouter", () => {
    expect(
      selectMarketingProvider({
        platformOpenRouter: "router-fixture",
        preferredPlatform: "openrouter",
      }),
    ).toMatchObject({
      provider: "openrouter",
      apiKey: "router-fixture",
      endpoint: "https://openrouter.ai/api/v1/chat/completions",
    });
  });
  it("keeps the client's own OpenAI key with OpenAI despite a platform OpenRouter default", () => {
    expect(
      selectMarketingProvider({
        clientOpenAi: "client-fixture",
        platformOpenRouter: "platform-fixture",
        preferredPlatform: "openrouter",
      }),
    ).toMatchObject({
      provider: "openai",
      apiKey: "client-fixture",
      endpoint: "https://api.openai.com/v1/chat/completions",
    });
  });
  it("keeps a client OpenRouter key scoped to that client", () => {
    expect(
      selectMarketingProvider({
        clientOpenRouter: "client-fixture",
        platformOpenAi: "platform-fixture",
      })?.apiKey,
    ).toBe("client-fixture");
    expect(selectMarketingProvider({})).toBeNull();
  });
  it("does not silently charge a different platform provider when the selected one is missing", () => {
    expect(
      selectMarketingProvider({
        platformOpenAi: "other-fixture",
        preferredPlatform: "openrouter",
      }),
    ).toBeNull();
  });
});
