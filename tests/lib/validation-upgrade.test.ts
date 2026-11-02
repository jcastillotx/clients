import { describe, expect, it } from "vitest";
import { z } from "zod";
import { zodResolver } from "@hookform/resolvers/zod";

describe("patched validation compatibility", () => {
  it("returns field errors for invalid client form input instead of rejecting the resolver", async () => {
    const schema = z.object({ email: z.string().email() });
    const resolver = zodResolver(schema);
    const result = await resolver({ email: "invalid" }, undefined, {
      fields: {},
      shouldUseNativeValidation: false,
    });
    expect(result.errors.email?.message).toBeTruthy();
    expect(result.values).toEqual({});
  });

  it("rejects oversized collections before validating their members", () => {
    let memberChecks = 0;
    const schema = z
      .array(
        z.string().superRefine(() => {
          memberChecks++;
        }),
      )
      .max(2);
    expect(schema.safeParse(Array(1000).fill("value")).success).toBe(false);
    expect(memberChecks).toBe(0);
  });
});
