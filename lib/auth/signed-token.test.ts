import { describe, expect, it } from "vitest";
import { createSignedToken, verifySignedToken } from "./signed-token";

describe("signed token", () => {
  it("creates and verifies signed payload", () => {
    const token = createSignedToken(
      { userId: "u1", provider: "google" },
      "secret",
      60,
    );
    const payload = verifySignedToken(token, "secret");

    expect(payload?.userId).toBe("u1");
    expect(payload?.provider).toBe("google");
  });

  it("rejects tampered payload", () => {
    const token = createSignedToken({ foo: "bar" }, "secret", 60);
    const [body] = token.split(".");
    const tampered = `${body}.invalid`;

    expect(verifySignedToken(tampered, "secret")).toBeNull();
  });
});
