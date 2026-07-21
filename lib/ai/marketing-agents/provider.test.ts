import { describe, expect, it } from "vitest";
import { z } from "zod";
import {
  MarketingAgentProviderError,
  parseJsonAgentResponse,
} from "./provider";

const schema = z.object({
  title: z.string(),
  score: z.number().min(0).max(100),
});

describe("parseJsonAgentResponse", () => {
  it("parses fenced JSON returned by a provider", () => {
    const result = parseJsonAgentResponse(
      '```json\n{"title":"Campaign","score":92}\n```',
      schema,
    );

    expect(result).toEqual({ title: "Campaign", score: 92 });
  });

  it("rejects content that does not match the required artifact", () => {
    expect(() =>
      parseJsonAgentResponse('{"title":"Campaign","score":140}', schema),
    ).toThrow(MarketingAgentProviderError);
  });

  it("does not accept unstructured provider prose", () => {
    expect(() => parseJsonAgentResponse("Here is your campaign.", schema)).toThrow(
      MarketingAgentProviderError,
    );
  });
});
