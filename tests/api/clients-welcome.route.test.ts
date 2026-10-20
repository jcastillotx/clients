import { beforeEach, describe, expect, it, vi } from "vitest";
import { POST } from "@/app/api/clients/route";

const mocks = vi.hoisted(() => ({
  returning: vi.fn(),
  send: vi.fn(),
  welcome: vi.fn(),
  permission: vi.fn(),
}));
vi.mock("@/lib/supabase/server", () => ({
  createClient: vi.fn().mockResolvedValue({ auth: { getUser: async () => ({ data: { user: { id: "admin" } } }) } }),
}));
vi.mock("@/lib/rbac/permissions", () => ({
  hasPermission: mocks.permission,
  hasAnyRole: vi.fn().mockResolvedValue(false),
  Permissions: { CLIENTS_CREATE: "clients.create" },
  Roles: { ADMIN: "admin", SUPER_ADMIN: "super_admin", ACCOUNT_MANAGER: "account_manager" },
}));
vi.mock("@/lib/db", () => ({ db: { insert: () => ({ values: () => ({ returning: mocks.returning }) }) } }));
vi.mock("@/lib/inngest/client", () => ({ inngest: { send: mocks.send } }));
vi.mock("@/lib/email/send-welcome-email", () => ({ sendClientWelcomeEmail: mocks.welcome }));
const client = { id: "client-1", companyName: "Acme", email: "team@example.com" };
const request = (email = client.email) => new Request("http://localhost/api/clients", {
  method: "POST", body: JSON.stringify({ company_name: "Acme", email }),
});

describe("client welcome email", () => {
  beforeEach(() => {
    vi.clearAllMocks();
    mocks.permission.mockResolvedValue(true);
    mocks.returning.mockResolvedValue([client]);
    mocks.welcome.mockResolvedValue({ success: true });
  });
  it("sends the saved client's welcome email and reports acceptance", async () => {
    const response = await POST(request());
    expect(response.status).toBe(201);
    expect(mocks.welcome).toHaveBeenCalledWith(client);
    expect((await response.json()).data.welcome_email_sent).toBe(true);
  });
  it("preserves the client when email fails", async () => {
    mocks.welcome.mockResolvedValue({ success: false });
    const response = await POST(request());
    expect(response.status).toBe(201);
    expect((await response.json()).data.welcome_email_sent).toBe(false);
  });
  it("does not send after a failed insert", async () => {
    mocks.returning.mockRejectedValue(new Error("DB unavailable"));
    expect((await POST(request())).status).toBe(500);
    expect(mocks.welcome).not.toHaveBeenCalled();
  });
  it("rejects invalid recipients before saving or sending", async () => {
    expect((await POST(request("invalid"))).status).toBe(400);
    expect(mocks.returning).not.toHaveBeenCalled();
    expect(mocks.welcome).not.toHaveBeenCalled();
  });
  it("does not send for unauthorized callers", async () => {
    mocks.permission.mockResolvedValue(false);
    expect((await POST(request())).status).toBe(403);
    expect(mocks.welcome).not.toHaveBeenCalled();
  });
});
