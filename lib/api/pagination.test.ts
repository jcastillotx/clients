import { describe, expect, it } from "vitest";
import {
  buildPaginationMeta,
  parsePaginationSearchParams,
} from "@/lib/api/pagination";

describe("parsePaginationSearchParams", () => {
  it("uses defaults when params are missing", () => {
    expect(parsePaginationSearchParams(new URLSearchParams())).toEqual({
      limit: 50,
      offset: 0,
    });
  });

  it("clamps limit to max and enforces minimum offset", () => {
    const params = parsePaginationSearchParams(
      new URLSearchParams("limit=500&offset=-5"),
      { maxLimit: 100 },
    );

    expect(params).toEqual({ limit: 100, offset: 0 });
  });

  it("respects custom defaults", () => {
    const params = parsePaginationSearchParams(new URLSearchParams(), {
      defaultLimit: 25,
    });

    expect(params).toEqual({ limit: 25, offset: 0 });
  });
});

describe("buildPaginationMeta", () => {
  it("computes hasMore from total count", () => {
    expect(
      buildPaginationMeta({ limit: 10, offset: 0 }, 25, 10),
    ).toMatchObject({
      total: 25,
      hasMore: true,
    });

    expect(
      buildPaginationMeta({ limit: 10, offset: 20 }, 25, 5),
    ).toMatchObject({
      total: 25,
      hasMore: false,
    });
  });

  it("falls back to page size when total is unknown", () => {
    expect(
      buildPaginationMeta({ limit: 10, offset: 0 }, null, 10),
    ).toMatchObject({
      hasMore: true,
    });

    expect(
      buildPaginationMeta({ limit: 10, offset: 0 }, null, 3),
    ).toMatchObject({
      hasMore: false,
    });
  });
});
