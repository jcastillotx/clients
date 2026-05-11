import { describe, expect, it } from "vitest";

import { buildWebsiteSupportTriage } from "./website-ticket-triage";

describe("buildWebsiteSupportTriage", () => {
  it("marks simple content changes as Codex eligible", () => {
    const triage = buildWebsiteSupportTriage({
      subject: "Update homepage CTA button text",
      description: "Change Learn More to Book a Consultation on the homepage hero.",
      priority: "medium",
      intake: {
        isWebsiteSupport: true,
        clientName: "Kre8ivDesigns",
        websiteUrl: "https://example.com",
        affectedPageUrl: "https://example.com/",
        requestedChange: "CTA text is updated from Learn More to Book a Consultation.",
        problemDescription: "The homepage CTA copy is outdated.",
        platformBuilder: "WordPress",
        affectedAreas: ["seo"],
      },
    });

    expect(triage.ticketTitle).toBe("[Kre8ivDesigns] Update homepage CTA button text");
    expect(triage.riskLevel).toBe("Low Risk");
    expect(triage.recommendedRouting).toBe("Codex eligible");
    expect(triage.codexReadyPrompt).toContain("You are working on a WordPress support ticket");
  });

  it("routes builder and form changes through human review", () => {
    const triage = buildWebsiteSupportTriage({
      subject: "Add a phone number field to the contact form",
      description: "The contact form needs a new required phone field and notification update.",
      priority: "medium",
      intake: {
        isWebsiteSupport: true,
        websiteUrl: "https://example.com",
        affectedPageUrl: "https://example.com/contact",
        requestedChange: "Add a required phone field to the contact form.",
        problemDescription: "Sales team needs phone numbers in lead notifications.",
        platformBuilder: "Elementor",
        affectedAreas: ["forms"],
      },
    });

    expect(triage.riskLevel).toBe("Medium Risk");
    expect(triage.recommendedRouting).toBe("Codex eligible with human review");
    expect(triage.codexReadyPrompt).toContain("For Elementor or Divi layout changes");
  });

  it("does not generate a Codex development prompt for high risk tickets", () => {
    const triage = buildWebsiteSupportTriage({
      subject: "Fix checkout Stripe issue",
      description: "Checkout payments fail after entering card details.",
      priority: "high",
      intake: {
        isWebsiteSupport: true,
        websiteUrl: "https://store.example.com",
        affectedPageUrl: "https://store.example.com/checkout",
        requestedChange: "Investigate checkout failure.",
        problemDescription: "Customers cannot complete Stripe payments.",
        platformBuilder: "WooCommerce",
        affectedAreas: ["checkout", "payments"],
      },
    });

    expect(triage.riskLevel).toBe("High Risk");
    expect(triage.recommendedRouting).toBe("Human developer required");
    expect(triage.codexReadyPrompt).toBeNull();
  });
});
