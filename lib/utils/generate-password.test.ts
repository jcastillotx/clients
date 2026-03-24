import { describe, expect, it } from "vitest";
import { generateSecurePassword } from "./generate-password";

describe("generateSecurePassword", () => {
  it("returns requested length within bounds", () => {
    const p = generateSecurePassword(20);
    expect(p.length).toBe(20);
    expect(generateSecurePassword(8).length).toBe(12); // minimum 12
  });

  it("includes upper, lower, digit, and symbol", () => {
    for (let i = 0; i < 20; i++) {
      const p = generateSecurePassword(24);
      expect(/[a-z]/.test(p)).toBe(true);
      expect(/[A-Z]/.test(p)).toBe(true);
      expect(/[0-9]/.test(p)).toBe(true);
      expect(/[!@#$%^&*\-_=+]/.test(p)).toBe(true);
    }
  });
});
