import { describe, expect, it, vi } from "vitest";
vi.mock("@/lib/db", () => ({ db: { select: vi.fn() } }));
import { projectPublicCatalog } from "@/lib/catalog/public-catalog";
const item = {
  id: "care-growth",
  name: "Care Growth",
  group: "Website care",
  fit: "An active website needing regular care.",
  includes: ["Agreed support allowance"],
  published: true,
};
const row = {
  id: "service-id",
  name: "Website care",
  category: "Websites",
  description: "Scoped care",
  metadata: { notes: "PRIVATE", publicPackages: [item] },
  basePrice: "PRIVATE_PRICE",
  clientId: "PRIVATE_TENANT",
};
describe("public catalog boundary", () => {
  it("returns only approved public fields", () => {
    const result = projectPublicCatalog([row]);
    expect(result.packages[0]).toEqual({
      id: item.id,
      name: item.name,
      group: item.group,
      fit: item.fit,
      includes: item.includes,
      service: row.name,
      serviceId: row.id,
    });
    expect(JSON.stringify(result)).not.toContain("PRIVATE");
    expect(JSON.stringify(result)).not.toContain("published");
  });
  it("excludes drafts and malformed packages", () => {
    expect(
      projectPublicCatalog([
        {
          ...row,
          metadata: {
            publicPackages: [
              { ...item, published: false },
              { ...item, id: "bad url" },
              { ...item, secret: "not allowed" },
            ],
          },
        },
      ]).packages,
    ).toEqual([]);
  });
  it("rejects duplicate identifiers rather than choosing an ambiguous package", () => {
    expect(() =>
      projectPublicCatalog([row, { ...row, id: "another" }]),
    ).toThrow("Duplicate");
  });
  it("changes the version only when public data changes", () => {
    const initial = projectPublicCatalog([row]).version;
    expect(
      projectPublicCatalog([
        {
          ...row,
          metadata: { ...row.metadata, notes: "Different private notes" },
        },
      ]).version,
    ).toBe(initial);
    expect(
      projectPublicCatalog([
        {
          ...row,
          metadata: { publicPackages: [{ ...item, name: "Revised care" }] },
        },
      ]).version,
    ).not.toBe(initial);
  });
});
