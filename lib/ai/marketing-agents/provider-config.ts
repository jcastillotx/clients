export type MarketingProviderName = "openai" | "openrouter";
export interface MarketingProviderCredentials {
  provider: MarketingProviderName;
  apiKey: string;
  endpoint: string;
  defaultModel: string;
}
export function selectMarketingProvider(keys: {
  clientOpenRouter?: string | null;
  clientOpenAi?: string | null;
  platformOpenRouter?: string | null;
  platformOpenAi?: string | null;
  preferredPlatform?: string;
}): MarketingProviderCredentials | null {
  const ownRouter = keys.clientOpenRouter?.trim();
  const ownOpenAi = keys.clientOpenAi?.trim();
  const platformRouter = keys.platformOpenRouter?.trim();
  const platformOpenAi = keys.platformOpenAi?.trim();
  const selected = ownRouter
    ? { provider: "openrouter" as const, apiKey: ownRouter }
    : ownOpenAi
      ? { provider: "openai" as const, apiKey: ownOpenAi }
      : keys.preferredPlatform === "openrouter"
        ? platformRouter
          ? { provider: "openrouter" as const, apiKey: platformRouter }
          : null
        : keys.preferredPlatform === "openai"
          ? platformOpenAi
            ? { provider: "openai" as const, apiKey: platformOpenAi }
            : null
          : platformRouter
            ? { provider: "openrouter" as const, apiKey: platformRouter }
            : platformOpenAi
              ? { provider: "openai" as const, apiKey: platformOpenAi }
              : null;
  if (!selected) return null;
  return {
    ...selected,
    endpoint:
      selected.provider === "openrouter"
        ? "https://openrouter.ai/api/v1/chat/completions"
        : "https://api.openai.com/v1/chat/completions",
    defaultModel:
      selected.provider === "openrouter" ? "openai/gpt-4o-mini" : "gpt-4o-mini",
  };
}
