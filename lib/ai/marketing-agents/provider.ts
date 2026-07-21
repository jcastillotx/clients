import { and, eq } from "drizzle-orm";
import type { z } from "zod";
import { db } from "@/lib/db";
import { encryptedSettings } from "@/lib/db/schema/encrypted-settings";
import { decrypt } from "@/lib/encryption";
import type { MarketingAgentDefinition } from "./catalog";

const DEFAULT_MODEL = "gpt-4o-mini";
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
  };
  error?: {
    message?: string;
  };
}

export interface MarketingAgentCallUsage {
  provider: "openai";
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

async function resolveOpenAiApiKey(clientId: string): Promise<string | null> {
  try {
    const rows = await db
      .select({ encryptedValue: encryptedSettings.encryptedValue })
      .from(encryptedSettings)
      .where(
        and(
          eq(encryptedSettings.clientId, clientId),
          eq(encryptedSettings.provider, "openai"),
          eq(encryptedSettings.settingKey, "api_key"),
          eq(encryptedSettings.isActive, true),
        ),
      )
      .limit(1);

    const encryptedValue = rows[0]?.encryptedValue;
    if (encryptedValue) {
      const decryptedValue = decrypt(encryptedValue).trim();
      if (decryptedValue) return decryptedValue;
    }
  } catch {
    // Fall back to the platform key when local encryption is unavailable.
  }

  return process.env.OPENAI_API_KEY?.trim() || null;
}

export function parseJsonAgentResponse<T>(content: string, schema: z.ZodType<T>): T {
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
  const apiKey = await resolveOpenAiApiKey(clientId);
  if (!apiKey) {
    throw new MarketingAgentProviderError(
      "No OpenAI provider is configured for this client or the portal.",
    );
  }

  const model = process.env.MARKETING_AGENT_MODEL?.trim() || DEFAULT_MODEL;
  const response = await fetch("https://api.openai.com/v1/chat/completions", {
    method: "POST",
    headers: {
      Authorization: `Bearer ${apiKey}`,
      "Content-Type": "application/json",
    },
    body: JSON.stringify({
      model,
      temperature: 0.35,
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
      payload.error?.message || "The AI provider could not complete the marketing agent run.",
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
      provider: "openai",
      model,
      promptTokens,
      completionTokens,
      totalTokens:
        payload.usage?.total_tokens ?? promptTokens + completionTokens,
      estimatedCost: estimateCost(promptTokens, completionTokens),
    },
  };
}
