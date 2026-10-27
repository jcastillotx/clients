import { beforeEach, describe, expect, it, vi } from "vitest";
import { NextRequest } from "next/server";
import { createClient } from "@/lib/supabase/server";
import { verifySignedToken } from "@/lib/auth/signed-token";
import { resolveCalendarOAuthCredentialsForUser } from "@/lib/calendar/oauth-credentials";
import { GET as google } from "@/app/api/calendar/callback/google/route";
import { GET as microsoft } from "@/app/api/calendar/callback/microsoft/route";

vi.mock("@/lib/db", () => ({ db: {} }));
vi.mock("@/lib/supabase/server", () => ({ createClient: vi.fn() }));
vi.mock("@/lib/auth/signed-token", () => ({
  getSigningSecret: () => "fixture-state-key",
  verifySignedToken: vi.fn(),
}));
vi.mock("@/lib/calendar/oauth-credentials", () => ({
  resolveCalendarOAuthCredentialsForUser: vi.fn(),
  hasCompleteCalendarOAuthCredentials: vi.fn(),
  getMicrosoftCalendarTenant: () => "fixture-tenant",
}));
const owner = "11111111-1111-4111-8111-111111111111";
beforeEach(() => vi.clearAllMocks());
describe.each([
  ["google", google],
  ["microsoft", microsoft],
] as const)("%s calendar OAuth session boundary", (provider, GET) => {
  it.each([null, "22222222-2222-4222-8222-222222222222"])(
    "rejects callbacks outside the initiating portal session (%s)",
    async (sessionId) => {
      vi.mocked(verifySignedToken).mockReturnValue({
        userId: owner,
        provider,
        exp: Math.floor(Date.now() / 1000) + 600,
      });
      vi.mocked(createClient).mockResolvedValue({
        auth: {
          getUser: vi.fn().mockResolvedValue({
            data: { user: sessionId ? { id: sessionId } : null },
          }),
        },
      } as never);
      const response = await GET(
        new NextRequest(
          `http://localhost/api/calendar/callback/${provider}?code=fixture&state=fixture`,
        ),
      );
      expect(response.headers.get("location")).toContain(
        "error=session_mismatch",
      );
      expect(resolveCalendarOAuthCredentialsForUser).not.toHaveBeenCalled();
    },
  );
});
