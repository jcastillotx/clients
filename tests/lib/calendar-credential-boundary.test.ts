import { afterEach, describe, expect, it, vi } from "vitest";
import { db } from "@/lib/db";
import { resolveCalendarOAuthCredentialsForUser } from "@/lib/calendar/oauth-credentials";
vi.mock("@/lib/db", () => ({ db: { select: vi.fn() } }));
vi.mock("@/lib/encryption", () => ({ decrypt: (value: string) => value }));
afterEach(() => {
  vi.clearAllMocks();
  vi.unstubAllEnvs();
});
function userQuery(clientId: string | null) {
  return {
    from: vi.fn().mockReturnThis(),
    where: vi.fn().mockReturnThis(),
    limit: vi.fn().mockResolvedValue([{ clientId }]),
  };
}
describe("calendar credential isolation", () => {
  it("does not use another tenant's preferred credential scope", async () => {
    vi.mocked(db.select).mockReturnValue(userQuery("alpha") as never);
    const result = await resolveCalendarOAuthCredentialsForUser(
      "user",
      "microsoft",
      "beta",
    );
    expect(result.source).toBe("none");
    expect(db.select).toHaveBeenCalledTimes(1);
  });
  it("falls back to platform configuration without scanning other client credentials", async () => {
    vi.stubEnv("MICROSOFT_EMAIL_CLIENT_ID", "platform-application");
    vi.stubEnv("MICROSOFT_EMAIL_CLIENT_SECRET", "fixture-client-credential");
    vi.stubEnv("MICROSOFT_CALENDAR_CLIENT_ID", "");
    vi.stubEnv("MICROSOFT_CALENDAR_CLIENT_SECRET", "");
    vi.mocked(db.select)
      .mockReturnValueOnce(userQuery("alpha") as never)
      .mockReturnValueOnce({
        from: vi.fn().mockReturnThis(),
        where: vi.fn().mockResolvedValue([]),
      } as never);
    const result = await resolveCalendarOAuthCredentialsForUser(
      "user",
      "microsoft",
    );
    expect(result.client_id).toBe("platform-application");
    expect(result.source).toBe("env");
    expect(result.clientScopeId).toBeNull();
    expect(db.select).toHaveBeenCalledTimes(2);
  });
});
