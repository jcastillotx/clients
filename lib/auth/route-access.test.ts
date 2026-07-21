import { describe, expect, it } from "vitest";
import {
  canAccessClient,
  isAdminAccess,
  isStaffAccess,
  normalizeRoleName,
  resolveRouteAccess,
} from "./route-access";

describe("route access helpers", () => {
  it("normalizes role names from joined role objects", () => {
    expect(normalizeRoleName({ role: { name: "Admin" } })).toBe("admin");
    expect(normalizeRoleName({ role: [{ name: "SUPER_ADMIN" }] })).toBe(
      "super_admin",
    );
    expect(normalizeRoleName({})).toBe("");
  });

  it("detects admin access from DB flag", () => {
    const result = isAdminAccess({ id: "u1" }, { is_super_admin: true }, []);
    expect(result).toBe(true);
  });

  it("detects admin access from role membership", () => {
    const result = isAdminAccess({ id: "u1" }, { is_super_admin: false }, [
      { role: { name: "account_manager" } },
    ]);
    expect(result).toBe(true);
  });

  it("enforces tenant access for non-admin users", () => {
    expect(
      canAccessClient({ isAdmin: false, isStaff: false, clientId: "c1" }, "c1"),
    ).toBe(true);
    expect(
      canAccessClient({ isAdmin: false, isStaff: false, clientId: "c1" }, "c2"),
    ).toBe(false);
    expect(
      canAccessClient(
        { isAdmin: true, isStaff: true, clientId: null },
        "any-client",
      ),
    ).toBe(true);
  });

  it("allows platform staff to access client-scoped workspaces", () => {
    const roleRows = [{ role: { name: "staff" } }];
    expect(
      isStaffAccess({ id: "u1" }, { is_super_admin: false }, roleRows),
    ).toBe(true);
    expect(
      canAccessClient(
        { isAdmin: false, isStaff: true, clientId: null },
        "client-2",
      ),
    ).toBe(true);
  });

  it("resolves access from db user row and role rows", async () => {
    const supabaseMock = {
      from(table: string) {
        if (table === "users") {
          return {
            select() {
              return {
                eq() {
                  return {
                    maybeSingle: async () => ({
                      data: { client_id: "client-1", is_super_admin: false },
                    }),
                  };
                },
              };
            },
          };
        }

        if (table === "user_roles") {
          return {
            select() {
              return {
                eq: async () => ({
                  data: [{ role: { name: "staff" } }],
                }),
              };
            },
          };
        }

        throw new Error(`Unexpected table: ${table}`);
      },
    } as unknown as Awaited<
      ReturnType<typeof import("@/lib/supabase/server").createClient>
    >;

    const access = await resolveRouteAccess(supabaseMock, { id: "user-1" });
    expect(access).toEqual({
      clientId: "client-1",
      isAdmin: false,
      isStaff: true,
    });
  });
});
