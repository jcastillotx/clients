import { describe, expect, it } from "vitest";
import {
  getAssignableUsersForClient,
  isUserAssignableToClient,
} from "./assignable-users";

const clientA = "11111111-1111-1111-1111-111111111111";
const clientB = "22222222-2222-2222-2222-222222222222";

const userRows = [
  {
    id: "client-a-user",
    name: "Client User",
    email: "client@example.com",
    client_id: clientA,
    is_super_admin: false,
  },
  {
    id: "client-b-user",
    name: "Other Client User",
    email: "other@example.com",
    client_id: clientB,
    is_super_admin: false,
  },
  {
    id: "staff-user",
    name: "Staff User",
    email: "staff@example.com",
    client_id: null,
    is_super_admin: false,
  },
  {
    id: "super-admin",
    name: "Super Admin",
    email: "admin@example.com",
    client_id: null,
    is_super_admin: true,
  },
];

const roleRows = [
  {
    user_id: "staff-user",
    role: { name: "staff" },
  },
];

function createQueryResult<T>(data: T) {
  return {
    then: (resolve: (value: { data: T; error: null }) => unknown) =>
      Promise.resolve({ data, error: null }).then(resolve),
  };
}

function createMockDbClient() {
  return {
    from(table: string) {
      if (table === "users") {
        return {
          select() {
            return {
              is() {
                return {
                  eq() {
                    return {
                      order() {
                        return createQueryResult(userRows);
                      },
                    };
                  },
                };
              },
              eq(_column: string, value: string | boolean) {
                if (typeof value !== "string") {
                  return {
                    eq() {
                      return {
                        is() {
                          return {
                            maybeSingle() {
                              return createQueryResult(null);
                            },
                          };
                        },
                      };
                    },
                  };
                }

                return {
                  eq() {
                    return {
                      is() {
                        return {
                          maybeSingle() {
                            return createQueryResult(
                              userRows.find((row) => row.id === value) ?? null,
                            );
                          },
                        };
                      },
                    };
                  },
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
              then: createQueryResult(roleRows).then,
              eq(_column: string, userId: string) {
                return createQueryResult(
                  roleRows
                    .filter((row) => row.user_id === userId)
                    .map((row) => ({ role: row.role })),
                );
              },
            };
          },
        };
      }

      throw new Error(`Unexpected table ${table}`);
    },
  };
}

describe("assignable users", () => {
  it("returns same-client users plus platform staff and admins", async () => {
    const users = await getAssignableUsersForClient(createMockDbClient(), clientA);

    expect(users.map((user) => user.id)).toEqual([
      "client-a-user",
      "staff-user",
      "super-admin",
    ]);
  });

  it("allows same-client users", async () => {
    await expect(
      isUserAssignableToClient(createMockDbClient(), "client-a-user", clientA),
    ).resolves.toBe(true);
  });

  it("allows platform staff", async () => {
    await expect(
      isUserAssignableToClient(createMockDbClient(), "staff-user", clientA),
    ).resolves.toBe(true);
  });

  it("rejects users from a different client", async () => {
    await expect(
      isUserAssignableToClient(createMockDbClient(), "client-b-user", clientA),
    ).resolves.toBe(false);
  });
});
