import { describe, expect, it } from "vitest";
import {
  clientBrandGuideContentSchema,
  createDefaultClientBrandGuide,
  makeClientBrandGuideSlug,
  parseClientBrandGuideContent,
} from "./client-brand-guide";

describe("client brand guide helpers", () => {
  it("creates a client-specific empty guide", () => {
    expect(
      createDefaultClientBrandGuide("Acme Co", "https://example.com/logo.png"),
    ).toMatchObject({
      title: "Acme Co Brand Guide",
      logoUrl: "https://example.com/logo.png",
      colors: [],
    });
  });

  it("rejects malformed logo URLs and colors", () => {
    const result = clientBrandGuideContentSchema.safeParse({
      ...createDefaultClientBrandGuide("Acme Co"),
      logoUrl: "logo.png",
      colors: [{ id: "1", name: "Primary", hex: "blue", usage: "Buttons" }],
    });

    expect(result.success).toBe(false);
  });

  it("falls back safely when stored metadata is incomplete", () => {
    expect(parseClientBrandGuideContent({ title: 123 }, "Acme Co")).toEqual(
      createDefaultClientBrandGuide("Acme Co"),
    );
  });

  it("builds a stable client-scoped slug", () => {
    expect(
      makeClientBrandGuideSlug(
        "Kre8iv Designs & Company",
        "a9c41562-2b73-4c41-a8b0-2a496f1aad81",
      ),
    ).toBe("kre8iv-designs-company-a9c41562-brand-guide");
  });
});
