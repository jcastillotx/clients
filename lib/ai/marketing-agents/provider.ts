import { and, eq, inArray } from "drizzle-orm";
import type { z } from "zod";
import { db } from "@/lib/db";
import { encryptedSettings } from "@/lib/db/schema/encrypted-settings";
import { decrypt } from "@/lib/encryption";
import type { MarketingAgentDefinition } from "./catalog";

import {
  selectMarketingProvider,
  type MarketingProviderCredentials,
  type MarketingProviderName,
} from "./provider-config";
const REQUEST_TIMEOUT_MS = 90_000;

interface OpenAiChatResponse {
  choices?: Array<{
    message?: {
      content?: string | null;
    };
  }>;
  usage?: {
    prompt_tokens?: number;
    completion_tokens?: number;
    total_tokens?: number;
    cost?: number;
  };
  error?: {
    message?: string;
  };
}

export interface MarketingAgentCallUsage {
  provider: MarketingProviderName;
  model: string;
  promptTokens: number;
  completionTokens: number;
  totalTokens: number;
  estimatedCost: number;
}

export interface MarketingAgentCallResult<T> {
  value: T;
  usage: MarketingAgentCallUsage;
}

export class MarketingAgentProviderError extends Error {
  constructor(message: string) {
    super(message);
    this.name = "MarketingAgentProviderError";
  }
}

export async function hasMarketingAiProvider(
  clientId: string,
): Promise<boolean> {
  return (await getMarketingAiProviderStatus(clientId)).ready;
}

export async function getMarketingAiProviderStatus(clientId: string) {
  if (process.env.STAGING_ISOLATED === "1") {
    return {
      ready: Boolean(getMarketingSimulatorUrl()),
      provider: "local_simulator" as const,
    };
  }
  try {
    const credentials = await resolveMarketingProvider(clientId);
    return {
      ready: Boolean(credentials),
      provider: credentials?.provider ?? null,
    };
  } catch {
    return { ready: false, provider: null };
  }
}

export function getMarketingSimulatorUrl(): string | null {
  if (
    process.env.STAGING_ISOLATED !== "1" ||
    !process.env.MARKETING_AI_SIMULATOR_URL
  )
    return null;
  const url = new URL(process.env.MARKETING_AI_SIMULATOR_URL);
  if (
    url.protocol !== "http:" ||
    !["127.0.0.1", "localhost", "[::1]"].includes(url.hostname)
  ) {
    throw new MarketingAgentProviderError(
      "The isolated AI simulator must use localhost.",
    );
  }
  return url.toString();
}

async function resolveMarketingProvider(
  clientId: string,
): Promise<MarketingProviderCredentials | null> {
  const rows = await db
    .select({
      provider: encryptedSettings.provider,
      encryptedValue: encryptedSettings.encryptedValue,
    })
    .from(encryptedSettings)
    .where(
      and(
        eq(encryptedSettings.clientId, clientId),
        inArray(encryptedSettings.provider, ["openai", "openrouter"]),
        eq(encryptedSettings.settingKey, "api_key"),
        eq(encryptedSettings.isActive, true),
      ),
    );
  const own: { clientOpenAi?: string; clientOpenRouter?: string } = {};
  for (const row of rows) {
    // A corrupt client credential is a setup error, not permission to borrow another tenant's key.
    const value = decrypt(row.encryptedValue).trim();
    if (row.provider === "openrouter") own.clientOpenRouter = value;
    if (row.provider === "openai") own.clientOpenAi = value;
  }
  return selectMarketingProvider({
    ...own,
    platformOpenRouter: process.env.OPENROUTER_API_KEY,
    platformOpenAi: process.env.OPENAI_API_KEY,
    preferredPlatform: process.env.MARKETING_AGENT_PROVIDER,
  });
}

export function parseJsonAgentResponse<T>(
  content: string,
  schema: z.ZodType<T>,
): T {
  const withoutFence = content
    .trim()
    .replace(/^```(?:json)?\s*/i, "")
    .replace(/\s*```$/i, "")
    .trim();
  const firstBrace = withoutFence.indexOf("{");
  const lastBrace = withoutFence.lastIndexOf("}");

  if (firstBrace < 0 || lastBrace <= firstBrace) {
    throw new MarketingAgentProviderError(
      "The AI provider returned an invalid structured response.",
    );
  }

  try {
    const parsed: unknown = JSON.parse(
      withoutFence.slice(firstBrace, lastBrace + 1),
    );
    return schema.parse(parsed);
  } catch {
    throw new MarketingAgentProviderError(
      "The AI provider returned a response that did not match the required marketing artifact format.",
    );
  }
}

function estimateCost(promptTokens: number, completionTokens: number): number {
  const inputRate = Number.parseFloat(
    process.env.MARKETING_AGENT_INPUT_COST_PER_MILLION || "0",
  );
  const outputRate = Number.parseFloat(
    process.env.MARKETING_AGENT_OUTPUT_COST_PER_MILLION || "0",
  );
  const safeInputRate = Number.isFinite(inputRate) ? inputRate : 0;
  const safeOutputRate = Number.isFinite(outputRate) ? outputRate : 0;

  return (
    (promptTokens / 1_000_000) * safeInputRate +
    (completionTokens / 1_000_000) * safeOutputRate
  );
}

export async function runJsonMarketingAgent<T>({
  clientId,
  agent,
  userPrompt,
  schema,
}: {
  clientId: string;
  agent: MarketingAgentDefinition;
  userPrompt: string;
  schema: z.ZodType<T>;
}): Promise<MarketingAgentCallResult<T>> {
  const simulatorUrl = getMarketingSimulatorUrl();
  if (process.env.STAGING_ISOLATED === "1" && !simulatorUrl) {
    throw new MarketingAgentProviderError(
      "Configure the local AI simulator for isolated testing.",
    );
  }
  const credentials = simulatorUrl
    ? null
    : await resolveMarketingProvider(clientId);
  const apiKey = simulatorUrl ? "isolated-simulator" : credentials?.apiKey;
  if (!apiKey) {
    throw new MarketingAgentProviderError(
      "No OpenRouter or OpenAI provider is configured for this client or the portal.",
    );
  }

  const model =
    credentials?.provider === "openrouter"
      ? process.env.MARKETING_AGENT_OPENROUTER_MODEL?.trim() ||
        process.env.OPENROUTER_MODEL?.trim() ||
        credentials.defaultModel
      : process.env.MARKETING_AGENT_MODEL?.trim() ||
        credentials?.defaultModel ||
        "gpt-4o-mini";
  const response = await fetch(simulatorUrl || credentials!.endpoint, {
    method: "POST",
    headers: {
      Authorization: `Bearer ${apiKey}`,
      "Content-Type": "application/json",
      ...(credentials?.provider === "openrouter"
        ? {
            "HTTP-Referer":
              process.env.NEXT_PUBLIC_APP_URL ||
              "https://clients.kre8ivdesigns.com",
            "X-Title": "KRE8IV Client Portal",
          }
        : {}),
    },
    body: JSON.stringify({
      model,
      temperature: 0.35,
      max_tokens: 6000,
      ...(credentials?.provider === "openrouter"
        ? { provider: { data_collection: "deny", require_parameters: true } }
        : {}),
      response_format: { type: "json_object" },
      messages: [
        { role: "system", content: agent.systemPrompt },
        { role: "user", content: userPrompt },
      ],
    }),
    signal: AbortSignal.timeout(REQUEST_TIMEOUT_MS),
  });

  const payload = (await response.json()) as OpenAiChatResponse;
  if (!response.ok) {
    throw new MarketingAgentProviderError(
      "The AI provider rejected the request. Check its configuration and usage limits.",
    );
  }

  const content = payload.choices?.[0]?.message?.content;
  if (!content) {
    throw new MarketingAgentProviderError(
      "The AI provider returned an empty marketing agent response.",
    );
  }

  const promptTokens = payload.usage?.prompt_tokens ?? 0;
  const completionTokens = payload.usage?.completion_tokens ?? 0;

  return {
    value: parseJsonAgentResponse(content, schema),
    usage: {
      provider: credentials?.provider || "openai",
      model,
      promptTokens,
      completionTokens,
      totalTokens:
        payload.usage?.total_tokens ?? promptTokens + completionTokens,
      estimatedCost:
        typeof payload.usage?.cost === "number"
          ? payload.usage.cost
          : estimateCost(promptTokens, completionTokens),
    },
  };
}
