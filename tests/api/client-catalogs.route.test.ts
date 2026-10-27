import { beforeEach, describe, expect, it, vi } from "vitest";
import { NextResponse } from "next/server";
import { PgDialect } from "drizzle-orm/pg-core";
import { GET as serviceCatalog } from "@/app/api/service-templates/route";
import { GET as maintenanceCatalog } from "@/app/api/maintenance-plan-templates/route";

const mocks = vi.hoisted(() => ({ guard: vi.fn(), select: vi.fn(), where: vi.fn() }));
vi.mock("@/lib/auth/route-guards", () => ({ requireAuthenticatedUser: mocks.guard }));
vi.mock("@/lib/db", () => ({ db: { select: mocks.select } }));

describe.each([
  ["services", serviceCatalog],
  ["maintenance", maintenanceCatalog],
] as const)("client %s catalog", (_name, getCatalog) => {
  beforeEach(() => {
    vi.clearAllMocks();
    mocks.guard.mockResolvedValue({ user: { id: "client-user" } });
    const query = {
      from: vi.fn().mockReturnThis(), where: mocks.where,
      orderBy: vi.fn().mockResolvedValue([{ id: "template", metadata: { notes: "internal", features: ["Support"] } }]),
    };
    mocks.where.mockReturnValue(query);
    mocks.select.mockReturnValue(query);
  });

  it("allows a client to read active templates without exposing internal creator fields", async () => {
    const r = await getCatalog(new Request("https://portal.test/api/catalog"));
    expect(r.status).toBe(200);
    const where = new PgDialect().sqlToQuery(mocks.where.mock.calls[0][0]);
    expect(where.sql).toContain('"is_active" =');
    expect(where.params).toEqual([true]);
    expect(mocks.select.mock.calls[0][0]).not.toHaveProperty("createdBy");
    if (_name === "services") expect((await r.json()).data[0].metadata).not.toHaveProperty("notes");
  });

  it("requires authentication before querying templates", async () => {
    mocks.guard.mockResolvedValue({ error: NextResponse.json({ error: "Unauthorized" }, { status: 401 }) });
    expect((await getCatalog(new Request("https://portal.test/api/catalog"))).status).toBe(401);
    expect(mocks.select).not.toHaveBeenCalled();
  });
});
