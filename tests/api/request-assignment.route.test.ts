import { beforeEach, describe, expect, it, vi } from "vitest";
import { NextRequest } from "next/server";
import { PATCH } from "@/app/api/requests/[id]/route";
import { createClient } from "@/lib/supabase/server";
import { isUserAssignableToClient } from "@/lib/users/assignable-users";
vi.mock("@/lib/supabase/server", () => ({
  createClient: vi.fn(),
  createAdminClientIfAvailable: vi.fn().mockReturnValue(null),
}));
vi.mock("next/cache", () => ({ revalidatePath: vi.fn() }));
vi.mock("@/lib/users/assignable-users", () => ({
  isUserAssignableToClient: vi.fn(),
}));
const client = "11111111-1111-4111-8111-111111111111";
const assignee = "22222222-2222-4222-8222-222222222222";
function setup(role: string) {
  const update = vi.fn().mockReturnThis();
  const request = {
    id: "request",
    client_id: client,
    assigned_to: null,
    status: "pending",
    custom_fields: {},
  };
  const chain = (data: unknown) => ({
    select: vi.fn().mockReturnThis(),
    eq: vi.fn().mockReturnThis(),
    is: vi.fn().mockReturnThis(),
    single: vi.fn().mockResolvedValue({ data }),
    maybeSingle: vi.fn().mockResolvedValue({ data }),
    then: (resolve: (value: unknown) => unknown) =>
      Promise.resolve({ data }).then(resolve),
    update,
  });
  const requests = chain(request);
  const db = {
    auth: {
      getUser: vi.fn().mockResolvedValue({ data: { user: { id: "actor" } } }),
    },
    from: vi.fn((table: string) => {
      if (table === "requests") return requests;
      if (table === "users")
        return chain({
          id: "actor",
          client_id: role === "client" ? client : null,
          is_super_admin: false,
        });
      if (table === "user_roles") return chain([{ role: { name: role } }]);
      if (table === "activity_logs")
        return { insert: vi.fn().mockResolvedValue({ error: null }) };
      throw Error(table);
    }),
  };
  vi.mocked(createClient).mockResolvedValue(db as never);
  return update;
}
function patch(assignedTo: string | null, extra: Record<string, unknown> = {}) {
  return PATCH(
    new NextRequest("https://clients.example.test/api/requests/request", {
      method: "PATCH",
      body: JSON.stringify({ assignedTo, ...extra }),
      headers: { "Content-Type": "application/json" },
    }),
    { params: Promise.resolve({ id: "request" }) },
  );
}
beforeEach(() => {
  vi.clearAllMocks();
  vi.mocked(isUserAssignableToClient).mockResolvedValue(true);
});
describe("request team assignment", () => {
  it("allows staff to save a validated team member", async () => {
    const update = setup("staff");
    expect((await patch(assignee)).status).toBe(200);
    expect(update).toHaveBeenCalledWith({ assigned_to: assignee });
  });
  it("allows staff to clear assignment", async () => {
    const update = setup("staff");
    expect((await patch(null)).status).toBe(200);
    expect(update).toHaveBeenCalledWith({ assigned_to: null });
  });
  it("rejects an ineligible assignee before writing", async () => {
    const update = setup("staff");
    vi.mocked(isUserAssignableToClient).mockResolvedValue(false);
    expect((await patch(assignee)).status).toBe(400);
    expect(update).not.toHaveBeenCalled();
  });
  it("does not expand staff access to unrelated request edits", async () => {
    const update = setup("staff");
    expect((await patch(assignee, { title: "Changed title" })).status).toBe(
      403,
    );
    expect(update).not.toHaveBeenCalled();
  });
  it("does not allow a client to change assignment", async () => {
    const update = setup("client");
    expect((await patch(assignee)).status).toBe(403);
    expect(update).not.toHaveBeenCalled();
  });
});
