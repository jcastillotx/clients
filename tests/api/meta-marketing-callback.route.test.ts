import { beforeEach, describe, expect, it, vi } from "vitest";
const m = vi.hoisted(() => ({
  execute: vi.fn(),
  access: vi.fn(),
  read: vi.fn(),
}));
vi.mock("@/lib/db", () => ({ db: { execute: m.execute } }));
vi.mock("@/lib/encryption", () => ({
  encrypt: (value: string) => "encrypted:" + value,
}));
vi.mock("@/lib/marketing/connection-access", () => ({
  marketingConnectionAccess: m.access,
}));
vi.mock("@/lib/marketing/meta-connections", () => ({
  metaMarketingConfig: () => ({
    clientId: "app",
    clientSecret: "secret",
    configId: "config",
    version: "v26.0",
    callback: "http://localhost:3101/api/marketing/connections/meta/callback",
  }),
  metaGraphRead: m.read,
}));
import { GET } from "@/app/api/marketing/connections/meta/callback/route";
const callback = () =>
  GET(
    new Request(
      "http://localhost:3101/api/marketing/connections/meta/callback?state=fixture-state&code=fixture-code",
    ),
  );
beforeEach(() => {
  vi.clearAllMocks();
  process.env.NEXT_PUBLIC_APP_URL = "http://localhost:3101";
  m.execute
    .mockResolvedValueOnce([
      { user_id: "user", client_id: "client", provider: "meta_ads" },
    ])
    .mockResolvedValue([]);
  m.access.mockResolvedValue({ userId: "user", clientId: "client" });
  m.read.mockResolvedValue({
    data: [
      { permission: "ads_read", status: "granted" },
      { permission: "public_profile", status: "granted" },
    ],
  });
  vi.stubGlobal(
    "fetch",
    vi
      .fn()
      .mockResolvedValue({
        ok: true,
        json: async () => ({ access_token: "fixture-token", expires_in: 3600 }),
      }),
  );
});
describe("Meta OAuth callback boundary", () => {
  it("requires a single-use state before exchanging any code", async () => {
    m.execute.mockReset().mockResolvedValue([]);
    const response = await callback();
    expect(response.headers.get("location")).toContain(
      "error=connection_failed",
    );
    expect(fetch).not.toHaveBeenCalled();
    expect(m.read).not.toHaveBeenCalled();
  });
  it("binds the returned grant to the same authenticated user and tenant", async () => {
    m.access.mockResolvedValue({ userId: "other", clientId: "client" });
    expect((await callback()).headers.get("location")).toContain(
      "error=connection_failed",
    );
    expect(fetch).not.toHaveBeenCalled();
    expect(m.execute).toHaveBeenCalledTimes(1);
  });
  it("requires the active paid-media plan at callback time", async () => {
    m.access.mockResolvedValue(null);
    expect((await callback()).headers.get("location")).toContain(
      "error=connection_failed",
    );
    expect(fetch).not.toHaveBeenCalled();
  });
  it("rejects a broader grant instead of silently saving campaign-management access", async () => {
    m.read.mockResolvedValue({
      data: [
        { permission: "ads_read", status: "granted" },
        { permission: "ads_management", status: "granted" },
      ],
    });
    expect((await callback()).headers.get("location")).toContain(
      "error=connection_failed",
    );
    expect(m.execute).toHaveBeenCalledTimes(1);
  });
  it("saves only a validated grant and keeps credentials out of redirect URLs", async () => {
    const response = await callback();
    expect(response.headers.get("location")).toBe(
      "http://localhost:3101/marketing/connections?connected=meta_ads",
    );
    expect(m.execute).toHaveBeenCalledTimes(2);
    expect(fetch).toHaveBeenCalledTimes(2);
    for (const [url, options] of vi.mocked(fetch).mock.calls) {
      expect(url).toBe("https://graph.facebook.com/v26.0/oauth/access_token");
      expect(options?.method).toBe("POST");
      expect(options?.redirect).toBe("error");
    }
  });
});
