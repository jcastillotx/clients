import { beforeEach, describe, expect, it, vi } from "vitest";
import { PgDialect } from "drizzle-orm/pg-core";
import type { SQL } from "drizzle-orm";
import { db } from "@/lib/db";
import { archiveClientWithRecords, restoreClientWithRecords } from "@/lib/clients/archive-client";
vi.mock("@/lib/db", () => ({ db: { transaction: vi.fn() } }));
const id = "00000000-0000-0000-0000-000000000001";
const archivedAt = new Date("2026-10-28T00:00:00.123Z");
const dialect = new PgDialect();
let existingDeletedAt: Date | null;
let queries: Array<{ sql: string; params: unknown[] }>;
beforeEach(() => {
  queries = [];
  existingDeletedAt = null;
  const select = { from: vi.fn().mockReturnThis(), where: vi.fn().mockReturnThis(), limit: vi.fn(async () => [{ id, companyName: "QA", deletedAt: existingDeletedAt }]) };
  const update = { set: vi.fn().mockReturnThis(), where: vi.fn().mockReturnThis(), returning: vi.fn(async () => [{ id, companyName: "QA", deletedAt: archivedAt }]) };
  const tx = { select: vi.fn(() => select), update: vi.fn(() => update), execute: vi.fn(async (query: SQL) => { queries.push(dialect.sqlToQuery(query)); return [{ count: 1 }]; }) };
  vi.mocked(db.transaction).mockImplementation(async callback => callback(tx as never));
});
describe("archive timestamp driver binding", () => {
  it("binds archive timestamps as PostgreSQL-compatible values rather than raw Date objects", async () => {
    await archiveClientWithRecords(id);
    expect(queries.length).toBeGreaterThan(1);
    const timestamps = queries.map(q => q.params[0]);
    expect(new Set(timestamps).size).toBe(1);
    expect(timestamps.every(value => typeof value === "string" && !Number.isNaN(Date.parse(value)))).toBe(true);
    expect(queries.every(q => q.params.includes(id) && q.params.every(value => !(value instanceof Date)))).toBe(true);
  });
  it("uses the exact same serialized timestamp to restore related rows", async () => {
    existingDeletedAt = archivedAt;
    await restoreClientWithRecords(id);
    expect(queries.length).toBeGreaterThan(1);
    expect(queries.every(q => q.params[0] === archivedAt.toISOString() && q.params.includes(id))).toBe(true);
  });
});
