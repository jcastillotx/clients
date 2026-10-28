import { describe, expect, it } from "vitest";
import { zodResolver } from "@hookform/resolvers/zod";
import { z } from "zod";
import { OpenAIStream, StreamingTextResponse } from "ai";
import { createRequestSchema } from "@/lib/validations/request";

const options = { criteriaMode: "all" as const, fields: {}, shouldUseNativeValidation: false };
describe("Zod 4 compatibility", () => {
  it("keeps field errors available to React Hook Form rather than throwing", async () => {
    const resolve = zodResolver(z.object({ email: z.string().email("Enter a valid email") }));
    const result = await resolve({ email: "invalid" }, undefined, options);
    expect(result.errors.email?.message).toBe("Enter a valid email");
    expect(result.values).toEqual({});
  });
  it("retains defaults and number coercion in submitted values", async () => {
    const resolve = zodResolver(z.object({ priority: z.enum(["low", "medium"]).default("medium"), quantity: z.coerce.number().min(1) }));
    const result = await resolve({ quantity: "2" }, undefined, options);
    expect(result.errors).toEqual({});
    expect(result.values).toEqual({ priority: "medium", quantity: 2 });
  });
  it("retains database GUID compatibility and rejects non-identifiers", () => {
    const base = { title: "Website change", description: "Please update our website content." };
    expect(createRequestSchema.safeParse({ ...base, clientId: "00000000-0000-0000-0000-000000000001" }).success).toBe(true);
    expect(createRequestSchema.safeParse({ ...base, clientId: "" }).success).toBe(true);
    expect(createRequestSchema.safeParse({ ...base, clientId: "not-an-identifier" }).success).toBe(false);
  });
  it("preserves the existing AI chat streaming protocol", async () => {
    let completion = "";
    const body = 'data: {"choices":[{"delta":{"content":"Hello"}}]}\n\ndata: [DONE]\n\n';
    const stream = OpenAIStream(new Response(body, { headers: { "Content-Type": "text/event-stream" } }), { onCompletion: async value => { completion = value; } });
    const output = await new StreamingTextResponse(stream).text();
    expect(output).toContain("Hello");
    expect(completion).toBe("Hello");
  });
});
