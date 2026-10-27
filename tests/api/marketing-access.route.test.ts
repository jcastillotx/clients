import { beforeEach, describe, expect, it, vi } from "vitest";
import { NextRequest } from "next/server";
const m = vi.hoisted(() => ({ execute: vi.fn(), limit: vi.fn(), captcha: vi.fn(), invite: vi.fn() }));
vi.mock("@/lib/db", () => ({ db: { execute: m.execute } }));
vi.mock("@/lib/rate-limit", () => ({ rateLimit: m.limit, getClientIp: () => "127.0.0.1" }));
vi.mock("@/lib/turnstile/verify", () => ({ assertTurnstileToken: m.captcha }));
vi.mock("@/lib/marketing/purchase-access", () => ({ inviteMarketingBuyer: m.invite }));
import { POST } from "@/app/api/public/marketing-access/route";
const body = { name: "QA Buyer", email: "QA@example.com", company: "QA Company", planId: "11111111-1111-4111-8111-111111111111", turnstileToken: "verified" };
const request = (data: unknown = body, origin = "https://clients.example.com") => new NextRequest("https://clients.example.com/api/public/marketing-access", { method: "POST", headers: { origin, "Content-Type": "application/json" }, body: JSON.stringify(data) });
beforeEach(() => { vi.clearAllMocks(); process.env.NEXT_PUBLIC_APP_URL = "https://clients.example.com"; m.limit.mockResolvedValue({ success: true }); m.captcha.mockResolvedValue({ ok: true }); m.execute.mockResolvedValue([{ id: body.planId }]); m.invite.mockResolvedValue(undefined); });
describe("public marketing purchase invitations", () => {
  it("rejects cross-origin account creation", async () => { expect((await POST(request(body, "https://attacker.example"))).status).toBe(403); expect(m.invite).not.toHaveBeenCalled(); });
  it("requires valid fields, an empty honeypot and CAPTCHA", async () => { expect((await POST(request({ ...body, contactFax: "bot" }))).status).toBe(400); m.captcha.mockResolvedValue({ ok: false, status: 400, error: "Verify CAPTCHA" }); expect((await POST(request())).status).toBe(400); expect(m.invite).not.toHaveBeenCalled(); });
  it("enforces rate limits before provisioning", async () => { m.limit.mockResolvedValue({ success: false }); expect((await POST(request())).status).toBe(429); expect(m.invite).not.toHaveBeenCalled(); });
  it("never creates purchase access for an unavailable plan", async () => { m.execute.mockResolvedValue([]); expect((await POST(request())).status).toBe(400); expect(m.invite).not.toHaveBeenCalled(); });
  it("normalizes email and returns no account identifiers or authentication token", async () => { const response = await POST(request()); expect(response.status).toBe(200); expect(m.invite).toHaveBeenCalledWith(expect.objectContaining({ email: "qa@example.com" })); const result = await response.text(); expect(result).not.toContain("token_hash"); expect(result).not.toContain("client_id"); });
});
