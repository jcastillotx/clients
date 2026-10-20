import { beforeEach, describe, expect, it, vi } from "vitest";
import { POST } from "@/app/api/admin/users/route";
import { createAdminClient, createClient } from "@/lib/supabase/server";
import { sendWelcomeEmail } from "@/lib/email/send-welcome-email";

vi.mock("@/lib/supabase/server", () => ({ createClient: vi.fn(), createAdminClient: vi.fn() }));
vi.mock("@/lib/email/send-welcome-email", () => ({ sendWelcomeEmail: vi.fn() }));
vi.mock("@/lib/rbac/permissions", () => ({
  hasAnyRole: vi.fn().mockResolvedValue(true),
  hasPermission: vi.fn().mockResolvedValue(true),
  Permissions: {}, Roles: {},
}));

describe("admin user welcome email", () => {
  const createUser = vi.fn();
  const deleteUser = vi.fn();
  beforeEach(() => {
    vi.clearAllMocks();
    vi.mocked(createClient).mockResolvedValue({ auth: { getUser: vi.fn().mockResolvedValue({ data: { user: { id: "admin" } } }) } } as never);
    createUser.mockResolvedValue({ data: { user: { id: "new-user" } }, error: null });
    const query = {
      select: vi.fn().mockReturnThis(), eq: vi.fn().mockReturnThis(),
      upsert: vi.fn().mockReturnThis(),
      single: vi.fn().mockResolvedValue({ data: { id: "new-user" }, error: null }),
      maybeSingle: vi.fn().mockResolvedValue({ data: { id: "new-user" }, error: null }),
    };
    vi.mocked(createAdminClient).mockReturnValue({
      auth: { admin: { createUser, deleteUser } }, from: vi.fn().mockReturnValue(query),
    } as never);
    vi.mocked(sendWelcomeEmail).mockResolvedValue({ success: true });
  });

  function request() {
    return new Request("https://portal.test/api/admin/users", {
      method: "POST", body: JSON.stringify({ name: "Jane", email: "jane@example.com", password: "private-password" }),
    });
  }

  it("sends a welcome message after creating the account without passing the password", async () => {
    const response = await POST(request());
    expect(response.status).toBe(201);
    expect(sendWelcomeEmail).toHaveBeenCalledExactlyOnceWith({ name: "Jane", email: "jane@example.com" });
    expect((await response.json()).data.welcome_email_sent).toBe(true);
  });

  it("preserves the account and reports a delivery failure", async () => {
    vi.mocked(sendWelcomeEmail).mockResolvedValue({ success: false });
    const response = await POST(request());
    expect(response.status).toBe(201);
    expect((await response.json()).data.welcome_email_sent).toBe(false);
    expect(deleteUser).not.toHaveBeenCalled();
  });

  it("does not send a welcome when account creation fails", async () => {
    createUser.mockResolvedValue({ data: null, error: new Error("Creation failed") });
    expect((await POST(request())).status).toBe(500);
    expect(sendWelcomeEmail).not.toHaveBeenCalled();
  });

  it("rejects invalid account details before creating an account or sending mail", async () => {
    const response = await POST(new Request("https://portal.test/api/admin/users", {
      method: "POST", body: JSON.stringify({ name: "Jane", email: "invalid", password: "short" }),
    }));
    expect(response.status).toBe(400);
    expect(createUser).not.toHaveBeenCalled();
    expect(sendWelcomeEmail).not.toHaveBeenCalled();
  });

  it("does not create an account or send mail for unauthenticated requests", async () => {
    vi.mocked(createClient).mockResolvedValue({ auth: { getUser: vi.fn().mockResolvedValue({ data: { user: null } }) } } as never);
    expect((await POST(request())).status).toBe(401);
    expect(createUser).not.toHaveBeenCalled();
    expect(sendWelcomeEmail).not.toHaveBeenCalled();
  });
});
