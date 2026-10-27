import { beforeEach, describe, expect, it, vi } from "vitest";
import { NextRequest } from "next/server";
import { PgDialect } from "drizzle-orm/pg-core";
import { GET as listProjects } from "@/app/api/projects/route";
import { GET as getProject } from "@/app/api/projects/[id]/route";

const mocks = vi.hoisted(() => ({
  getUser: vi.fn(), access: vi.fn(), select: vi.fn(), where: vi.fn(), limit: vi.fn(),
}));
vi.mock("@/lib/supabase/server", () => ({ createClient: vi.fn(async () => ({ auth: { getUser: mocks.getUser } })) }));
vi.mock("@/lib/auth/route-access", async (original) => ({
  ...await original<typeof import("@/lib/auth/route-access")>(), resolveRouteAccess: mocks.access,
}));
vi.mock("@/lib/db", () => ({ db: { select: mocks.select } }));

describe("project tenant isolation", () => {
  beforeEach(() => {
    vi.clearAllMocks();
    mocks.getUser.mockResolvedValue({ data: { user: { id: "client-user" } } });
    mocks.access.mockResolvedValue({ clientId: "own-client", isAdmin: false, isStaff: false });
    const query = {
      from: vi.fn().mockReturnThis(), leftJoin: vi.fn().mockReturnThis(),
      groupBy: vi.fn().mockReturnThis(), orderBy: vi.fn().mockReturnThis(),
      where: mocks.where, limit: mocks.limit,
    };
    mocks.select.mockReturnValue(query);
    mocks.where.mockReturnValue(query);
    mocks.limit.mockResolvedValue([{ id: "foreign-project", clientId: "other-client" }]);
  });

  it("rejects a requested foreign tenant before querying projects", async () => {
    const r = await listProjects(new NextRequest("https://portal.test/api/projects?clientId=other-client"));
    expect(r.status).toBe(403);
    expect(mocks.select).not.toHaveBeenCalled();
  });

  it("fails closed when a client user has no tenant", async () => {
    mocks.access.mockResolvedValue({ clientId: null, isAdmin: false, isStaff: false });
    expect((await listProjects(new NextRequest("https://portal.test/api/projects"))).status).toBe(403);
    expect(mocks.select).not.toHaveBeenCalled();
  });

  it("always scopes client lists and excludes deleted projects", async () => {
    mocks.where.mockResolvedValue([]);
    const r = await listProjects(new NextRequest("https://portal.test/api/projects?includeDeleted=true"));
    expect(r.status).toBe(200);
    const sql = new PgDialect().sqlToQuery(mocks.where.mock.calls[0][0]);
    expect(sql.sql).toContain('"projects"."client_id" =');
    expect(sql.params).toContain("own-client");
    expect(sql.sql).toContain('"projects"."deleted_at" is null');
  });

  it("blocks foreign project details before reading budgets or deliverables", async () => {
    const r = await getProject(new NextRequest("https://portal.test/api/projects/foreign-project"), {
      params: Promise.resolve({ id: "foreign-project" }),
    });
    expect(r.status).toBe(403);
    expect(mocks.select).toHaveBeenCalledTimes(1);
  });

  it("rejects anonymous requests", async () => {
    mocks.getUser.mockResolvedValue({ data: { user: null } });
    expect((await listProjects(new NextRequest("https://portal.test/api/projects"))).status).toBe(401);
    expect(mocks.select).not.toHaveBeenCalled();
  });
});
