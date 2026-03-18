import { beforeEach, describe, expect, it } from "vitest";
import { decrypt, encrypt, maskSecret } from "./encryption";

describe("encryption", () => {
  beforeEach(() => {
    process.env.ENCRYPTION_KEY =
      "0123456789abcdef0123456789abcdef0123456789abcdef0123456789abcdef";
  });

  it("round-trips plaintext values", () => {
    const plaintext = "sensitive-token-value";
    const ciphertext = encrypt(plaintext);

    expect(ciphertext).not.toBe(plaintext);
    expect(decrypt(ciphertext)).toBe(plaintext);
  });

  it("produces different ciphertext for same input", () => {
    const plaintext = "repeatable-secret";
    const first = encrypt(plaintext);
    const second = encrypt(plaintext);

    expect(first).not.toBe(second);
    expect(decrypt(first)).toBe(plaintext);
    expect(decrypt(second)).toBe(plaintext);
  });

  it("masks secret values for safe display", () => {
    expect(maskSecret("short")).toBe("••••••••");
    expect(maskSecret("abcdef123456")).toContain("abcd");
    expect(maskSecret("abcdef123456")).toContain("3456");
  });
});
