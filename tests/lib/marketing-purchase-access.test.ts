import { beforeEach, describe, expect, it, vi } from "vitest";
vi.mock("server-only", () => ({}));
const m = vi.hoisted(() => ({ execute: vi.fn(), transaction: vi.fn(), tx: vi.fn(), generate: vi.fn(), send: vi.fn() }));
vi.mock("@/lib/db", () => ({ db: { execute: m.execute, transaction: m.transaction } }));
vi.mock("@/lib/supabase/server", () => ({ createAdminClient: () => ({ auth: { admin: { generateLink: m.generate } } }) }));
vi.mock("@/lib/supabase/redirect-url", () => ({ getAuthBaseUrl: () => "https://clients.example.com" }));
vi.mock("@/lib/email/client", () => ({ sendEmail: m.send }));
import { inviteMarketingBuyer } from "@/lib/marketing/purchase-access";
const input = { email: "qa@example.com", name: "QA Buyer", company: "QA Company", planId: "11111111-1111-4111-8111-111111111111" };
beforeEach(() => {
  vi.clearAllMocks(); m.execute.mockResolvedValueOnce([]).mockResolvedValueOnce([{ id: "22222222-2222-4222-8222-222222222222" }]);
  m.tx.mockResolvedValue([]); m.transaction.mockImplementation(callback => callback({ execute: m.tx })); m.send.mockResolvedValue({ success: true });
  m.generate.mockResolvedValue({ data: { user: { id: "33333333-3333-4333-8333-333333333333", created_at: new Date().toISOString() }, properties: { hashed_token: "private-invitation-token" } } });
});
describe("marketing purchase account boundary", () => {
  it("never reassigns an existing portal account", async () => { m.execute.mockReset().mockResolvedValue([{ id: "existing" }]); await inviteMarketingBuyer(input); expect(m.generate).not.toHaveBeenCalled(); expect(m.transaction).not.toHaveBeenCalled(); });
  it("does not attach an existing confirmed auth identity", async () => { m.generate.mockResolvedValue({ data: { user: { id: "existing", email_confirmed_at: new Date().toISOString() }, properties: { hashed_token: "token" } } }); await inviteMarketingBuyer(input); expect(m.transaction).not.toHaveBeenCalled(); expect(m.send).not.toHaveBeenCalled(); });
  it("refuses an existing privileged or assigned database profile", async () => { m.tx.mockResolvedValueOnce([{ is_super_admin: true }]).mockResolvedValueOnce([]); await expect(inviteMarketingBuyer(input)).rejects.toThrow("cannot be reassigned"); expect(m.send).not.toHaveBeenCalled(); });
  it("creates only a new client account and sends a private verification link without a password", async () => { await inviteMarketingBuyer(input); expect(m.generate).toHaveBeenCalledWith({ type: "invite", email: input.email, options: { data: { name: input.name } } }); expect(m.tx).toHaveBeenCalledTimes(6); const mail = m.send.mock.calls[0]![0]; expect(mail.to).toBe(input.email); expect(mail.text).toContain("type=invite"); expect(mail.text).toContain("marketing%2Fplans"); expect(mail.text).not.toContain("password"); });
});
