import { describe, expect, it } from "vitest";
import { buildNotificationTemplate } from "./templates";

describe("buildNotificationTemplate", () => {
  it("builds proposal accepted notification", () => {
    const template = buildNotificationTemplate("proposal_accepted", {
      proposalTitle: "Website Redesign",
    });

    expect(template.subject).toContain("Proposal Accepted");
    expect(template.message).toContain("Website Redesign");
    expect(template.html).toContain("Website Redesign");
  });

  it("builds invoice paid notification with amount", () => {
    const template = buildNotificationTemplate("invoice_paid", {
      invoiceNumber: "INV-1001",
      amount: "$250.00",
    });

    expect(template.subject).toContain("Invoice Paid");
    expect(template.message).toContain("INV-1001");
    expect(template.message).toContain("$250.00");
  });
});
