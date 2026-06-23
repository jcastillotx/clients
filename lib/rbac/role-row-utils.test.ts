import { describe, expect, it } from "vitest";
import { collectRoleNames, roleNameFromJoinRow } from "@/lib/rbac/role-row-utils";

describe("role row utils", () => {
  it("reads nested role names", () => {
    expect(roleNameFromJoinRow({ role: { name: "Admin" } })).toBe("admin");
    expect(roleNameFromJoinRow({ role: [{ name: "Staff" }] })).toBe("staff");
  });

  it("collects unique role names", () => {
    expect(
      collectRoleNames([
        { role: { name: "admin" } },
        { role: { name: "staff" } },
        { role: { name: "admin" } },
      ]),
    ).toEqual(new Set(["admin", "staff"]));
  });
});
