import { describe, expect, it } from "vitest";

import { shouldRouteToAuthConfirm } from "./auth-token-redirect";

function params(value: string): URLSearchParams {
  return new URLSearchParams(value);
}

describe("Supabase auth token redirect routing", () => {
  it("routes non-api Supabase auth codes to auth confirm", () => {
    expect(shouldRouteToAuthConfirm("/", params("code=abc"))).toBe(true);
    expect(shouldRouteToAuthConfirm("/login", params("token_hash=abc&type=magiclink"))).toBe(true);
  });

  it("does not intercept OAuth provider API callbacks with code params", () => {
    expect(shouldRouteToAuthConfirm("/api/admin/email/callback/microsoft", params("code=abc"))).toBe(false);
    expect(shouldRouteToAuthConfirm("/api/admin/email/callback/google", params("code=abc"))).toBe(false);
    expect(shouldRouteToAuthConfirm("/api/calendar/callback/microsoft", params("code=abc"))).toBe(false);
  });

  it("does not reroute canonical auth callback paths", () => {
    expect(shouldRouteToAuthConfirm("/auth/confirm", params("code=abc"))).toBe(false);
    expect(shouldRouteToAuthConfirm("/auth/callback", params("code=abc"))).toBe(false);
  });
});
