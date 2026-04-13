import { describe, expect, it } from "vitest";
import { buildNotificationTemplate } from "./templates";

describe("buildNotificationTemplate", () => {
  it("builds proposal_sent notification", () => {
    const t = buildNotificationTemplate("proposal_sent", { proposalTitle: "Q1 Proposal" });
    expect(t.subject).toContain("Proposal Sent");
    expect(t.message).toContain("Q1 Proposal");
    expect(t.html).toContain("Q1 Proposal");
  });

  it("builds proposal_accepted notification", () => {
    const template = buildNotificationTemplate("proposal_accepted", {
      proposalTitle: "Website Redesign",
    });

    expect(template.subject).toContain("Proposal Accepted");
    expect(template.message).toContain("Website Redesign");
    expect(template.html).toContain("Website Redesign");
  });

  it("builds proposal_rejected notification", () => {
    const t = buildNotificationTemplate("proposal_rejected", { proposalTitle: "Branding Package" });
    expect(t.subject).toContain("Proposal Rejected");
    expect(t.message).toContain("Branding Package");
    expect(t.html).toContain("Branding Package");
  });

  it("builds project_request_created notification", () => {
    const t = buildNotificationTemplate("project_request_created", { requestTitle: "New Landing Page" });
    expect(t.subject).toContain("New Project Request");
    expect(t.message).toContain("New Landing Page");
    expect(t.html).toContain("New Landing Page");
  });

  it("builds service_request_created notification", () => {
    const t = buildNotificationTemplate("service_request_created", { request_title: "Fix DNS" });
    expect(t.subject).toContain("Fix DNS");
    expect(t.message).toContain("Fix DNS");
  });

  it("builds staff_task_created notification", () => {
    const t = buildNotificationTemplate("staff_task_created", { task_title: "Deploy" });
    expect(t.subject).toContain("Deploy");
    expect(t.message).toContain("Deploy");
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

  it("builds invoice_paid notification without amount", () => {
    const t = buildNotificationTemplate("invoice_paid", { invoiceNumber: "INV-2000" });
    expect(t.subject).toContain("Invoice Paid");
    expect(t.message).toContain("INV-2000");
    expect(t.message).not.toContain("null");
  });

  it("builds invoice_payment_failed notification", () => {
    const t = buildNotificationTemplate("invoice_payment_failed", { invoiceNumber: "INV-3001" });
    expect(t.subject).toContain("Payment Failed");
    expect(t.message).toContain("INV-3001");
    expect(t.html).toContain("INV-3001");
  });

  it("builds invoice_refunded notification with amount", () => {
    const t = buildNotificationTemplate("invoice_refunded", { invoiceNumber: "INV-4001", amount: "$100.00" });
    expect(t.subject).toContain("Invoice Refunded");
    expect(t.message).toContain("INV-4001");
    expect(t.message).toContain("$100.00");
  });

  it("builds invoice_refunded notification without amount", () => {
    const t = buildNotificationTemplate("invoice_refunded", { invoiceNumber: "INV-4002" });
    expect(t.subject).toContain("Invoice Refunded");
    expect(t.message).toContain("INV-4002");
  });

  it("builds subscription_updated notification", () => {
    const t = buildNotificationTemplate("subscription_updated", {});
    expect(t.subject).toBe("Subscription Updated");
    expect(t.message).toContain("subscription");
    expect(t.html).toContain("Subscription Updated");
  });

  it("uses default title/number when data fields are absent", () => {
    const t = buildNotificationTemplate("proposal_accepted", {});
    expect(t.subject).toContain("Proposal");
    expect(t.message).toContain("Proposal");
  });
});
