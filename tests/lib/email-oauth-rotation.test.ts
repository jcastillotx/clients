import { afterEach, describe, expect, it, vi } from "vitest";
import { createAdminClientIfAvailable } from "@/lib/supabase/server";
import { sendViaMicrosoftGraph, type EmailConfig } from "@/lib/email/providers";
vi.mock("@/lib/supabase/server", () => ({
  createAdminClientIfAvailable: vi.fn(),
}));
vi.mock("@/lib/encryption", () => ({
  encrypt: (v: string) => "sealed:" + v,
  decrypt: (v: string) => v,
}));
afterEach(() => {
  vi.clearAllMocks();
  vi.unstubAllGlobals();
  vi.unstubAllEnvs();
});
function setup(failWrite = false) {
  vi.stubEnv("MICROSOFT_EMAIL_CLIENT_ID", "fixture-application");
  vi.stubEnv("MICROSOFT_EMAIL_CLIENT_SECRET", "fixture-credential");
  const upsert = vi
    .fn()
    .mockResolvedValue({
      error: failWrite ? { message: "database unavailable" } : null,
    });
  vi.mocked(createAdminClientIfAvailable).mockReturnValue({
    from: vi.fn().mockReturnValue({ upsert }),
  } as never);
  const fetcher = vi
    .fn()
    .mockResolvedValueOnce(
      Response.json({
        access_token: "new-access",
        refresh_token: "new-refresh",
        expires_in: 3600,
      }),
    )
    .mockResolvedValue(new Response(null, { status: 202 }));
  vi.stubGlobal("fetch", fetcher);
  const cfg: EmailConfig = {
    provider: "office365",
    from_email: "sender@example.invalid",
    oauth_refresh_token: "old-refresh",
    oauth_token_expiry: "2000-01-01T00:00:00Z",
  };
  return { upsert, fetcher, cfg };
}
const message = {
  to: "recipient@example.invalid",
  subject: "fixture",
  html: "<p>fixture</p>",
};
describe("email OAuth credential lifecycle", () => {
  it("persists rotation and reuses refreshed cached credentials for the next send", async () => {
    const { upsert, fetcher, cfg } = setup();
    await sendViaMicrosoftGraph(cfg, message);
    await sendViaMicrosoftGraph(cfg, message);
    expect(upsert).toHaveBeenCalledWith(
      expect.objectContaining({
        key: "oauth_refresh_token",
        value: "sealed:new-refresh",
        is_encrypted: true,
      }),
      expect.anything(),
    );
    expect(cfg.oauth_refresh_token).toBe("new-refresh");
    expect(fetcher).toHaveBeenCalledTimes(3);
    expect(fetcher.mock.calls[1][1].headers.Authorization).toBe(
      "Bearer new-access",
    );
  });
  it("reports persistence failure before sending instead of losing the rotated credentials silently", async () => {
    const { fetcher, cfg } = setup(true);
    await expect(sendViaMicrosoftGraph(cfg, message)).rejects.toThrow(
      "Unable to save refreshed email credentials",
    );
    expect(fetcher).toHaveBeenCalledTimes(1);
  });
});
