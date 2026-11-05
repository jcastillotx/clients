import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";
const mock = vi.hoisted(() => ({ execute: vi.fn() }));
vi.mock("@/lib/db", () => ({ db: { execute: mock.execute } }));
vi.mock("@/lib/encryption", () => ({
  decrypt: (value: string) => value.replace("encrypted:", ""),
}));
import {
  metaGraphRead,
  metaMarketingResources,
  metaMarketingToken,
  metaMarketingReport,
} from "@/lib/marketing/meta-connections";
beforeEach(() => {
  vi.clearAllMocks();
  vi.stubEnv("NEXT_PUBLIC_APP_URL", "http://127.0.0.1:3101");
  vi.stubEnv("META_MARKETING_CLIENT_SECRET", "fixture");
});
afterEach(() => {
  vi.unstubAllGlobals();
  vi.unstubAllEnvs();
});
describe("client-owned Meta reports", () => {
  it("fails closed without an own valid token and never borrows a platform token", async () => {
    mock.execute.mockResolvedValue([]);
    await expect(metaMarketingToken("tenant", "user")).rejects.toThrow(
      "Reconnect",
    );
    mock.execute.mockResolvedValue([
      { expires_at: new Date(0), access_token: "encrypted:old" },
    ]);
    await expect(metaMarketingToken("tenant", "user")).rejects.toThrow(
      "Reconnect",
    );
  });
  it("does not follow arbitrary next URLs or leak bearer tokens into request URLs", async () => {
    const fetch = vi
      .fn()
      .mockResolvedValueOnce({
        ok: true,
        json: async () => ({
          data: [{ id: "act_1", name: "One" }],
          paging: {
            next: "https://evil.invalid/?access_token=fixture-token",
            cursors: { after: "cursor" },
          },
        }),
      })
      .mockResolvedValueOnce({
        ok: true,
        json: async () => ({ data: [{ id: "act_2", name: "Two" }] }),
      });
    vi.stubGlobal("fetch", fetch);
    expect(await metaMarketingResources("fixture-token")).toHaveLength(2);
    for (const [url, options] of fetch.mock.calls) {
      expect(new URL(url).origin).toBe("https://graph.facebook.com");
      expect(url).not.toContain("fixture-token");
      expect(options.headers.Authorization).toBe("Bearer fixture-token");
    }
    expect(new URL(fetch.mock.calls[1][0]).searchParams.get("after")).toBe(
      "cursor",
    );
  });
  it("rejects mutation and arbitrary endpoints before making a request", async () => {
    const fetch = vi.fn();
    vi.stubGlobal("fetch", fetch);
    await expect(
      metaGraphRead("fixture", "act_1/campaigns", {}),
    ).rejects.toThrow("endpoint");
    await expect(
      metaMarketingReport("fixture", "https://evil.invalid/"),
    ).rejects.toThrow("Select");
    expect(fetch).not.toHaveBeenCalled();
  });
  it("keeps empty reports distinct and rejects mismatched account metrics", async () => {
    const fetch = vi
      .fn()
      .mockResolvedValueOnce({ ok: true, json: async () => ({ data: [] }) })
      .mockResolvedValueOnce({
        ok: true,
        json: async () => ({
          data: [
            {
              account_id: "2",
              date_start: "2026-10-01",
              date_stop: "2026-10-07",
            },
          ],
        }),
      });
    vi.stubGlobal("fetch", fetch);
    expect((await metaMarketingReport("fixture", "act_1")).dataStatus).toBe(
      "empty",
    );
    await expect(metaMarketingReport("fixture", "act_1")).rejects.toThrow(
      "mismatch",
    );
  });
  it("does not treat provider error JSON as an empty report", async () => {
    vi.stubGlobal(
      "fetch",
      vi
        .fn()
        .mockResolvedValue({
          ok: true,
          json: async () => ({ error: { message: "denied" } }),
        }),
    );
    await expect(metaMarketingReport("fixture", "act_1")).rejects.toThrow(
      "unavailable",
    );
  });
});
