import { afterEach, describe, expect, it, vi } from "vitest";
import { trustedVerification, verifyReadOnlyProvider } from "@/lib/settings/verify-provider";
afterEach(() => vi.unstubAllGlobals());
describe("live credential verification", () => {
  it("does not trust legacy presence-only badges", () => {
    expect(trustedVerification("openai", new Date("2026-08-01"))).toBeNull();
    expect(trustedVerification("google_stitch", new Date("2026-10-29"))).toBeNull();
    expect(trustedVerification("stripe", new Date("2026-08-01"))).not.toBeNull();
  });
  it("checks the fixed provider endpoint without generating content", async () => {
    const fetchMock = vi.fn().mockResolvedValue(Response.json({ data: [] }));
    vi.stubGlobal("fetch", fetchMock);
    await verifyReadOnlyProvider("openai", { api_key: "test-key" });
    expect(fetchMock).toHaveBeenCalledWith("https://api.openai.com/v1/models", expect.objectContaining({ redirect: "error", headers: { Authorization: "Bearer test-key" } }));
  });
  it("rejects provider denial and unexpected successful responses", async () => {
    vi.stubGlobal("fetch", vi.fn().mockResolvedValue(new Response("private provider error", { status: 401 })));
    await expect(verifyReadOnlyProvider("openai", { api_key: "test-key" })).rejects.toThrow("HTTP 401");
    vi.stubGlobal("fetch", vi.fn().mockResolvedValue(Response.json({ error: "no" })));
    await expect(verifyReadOnlyProvider("openai", { api_key: "test-key" })).rejects.toThrow("unexpected");
  });
  it("does not claim unsupported providers passed", async () => {
    const fetchMock = vi.fn(); vi.stubGlobal("fetch", fetchMock);
    await expect(verifyReadOnlyProvider("google_stitch", { api_key: "test-key" })).rejects.toThrow("not available");
    expect(fetchMock).not.toHaveBeenCalled();
  });
});
