import { describe, expect, it, beforeEach, afterEach } from "vitest";
import {
  buildPublicPayInvoiceUrl,
  formatInvoiceAmount,
  formatInvoiceDate,
  resolveAppBaseUrl,
  resolveInvoiceClientDisplayName,
  resolveInvoiceRecipientEmail,
  resolveSenderCompanyName,
} from "./invoice-email-context";

describe("invoice-email-context", () => {
  const prevAppUrl = process.env.NEXT_PUBLIC_APP_URL;
  const prevAppName = process.env.NEXT_PUBLIC_APP_NAME;
  const prevFromName = process.env.RESEND_FROM_NAME;

  beforeEach(() => {
    delete process.env.NEXT_PUBLIC_APP_URL;
    delete process.env.NEXT_PUBLIC_APP_NAME;
    delete process.env.RESEND_FROM_NAME;
  });

  afterEach(() => {
    if (prevAppUrl === undefined) delete process.env.NEXT_PUBLIC_APP_URL;
    else process.env.NEXT_PUBLIC_APP_URL = prevAppUrl;
    if (prevAppName === undefined) delete process.env.NEXT_PUBLIC_APP_NAME;
    else process.env.NEXT_PUBLIC_APP_NAME = prevAppName;
    if (prevFromName === undefined) delete process.env.RESEND_FROM_NAME;
    else process.env.RESEND_FROM_NAME = prevFromName;
  });

  it("resolveAppBaseUrl strips trailing slashes and defaults", () => {
    expect(resolveAppBaseUrl()).toBe("http://localhost:3000");
    process.env.NEXT_PUBLIC_APP_URL = "https://app.example.com/";
    expect(resolveAppBaseUrl()).toBe("https://app.example.com");
  });

  it("buildPublicPayInvoiceUrl encodes the invoice number", () => {
    process.env.NEXT_PUBLIC_APP_URL = "https://app.example.com";
    expect(buildPublicPayInvoiceUrl("INV-100")).toBe(
      "https://app.example.com/pay-invoice?invoice=INV-100",
    );
    expect(buildPublicPayInvoiceUrl("A B")).toBe(
      "https://app.example.com/pay-invoice?invoice=A%20B",
    );
  });

  it("formatInvoiceAmount handles strings and numbers", () => {
    expect(formatInvoiceAmount("12.5")).toBe("12.50");
    expect(formatInvoiceAmount(99)).toBe("99.00");
    expect(formatInvoiceAmount("nope")).toBe("0.00");
  });

  it("formatInvoiceDate returns em dash for empty input", () => {
    expect(formatInvoiceDate(null)).toBe("—");
    expect(formatInvoiceDate(undefined)).toBe("—");
  });

  it("resolveSenderCompanyName prefers NEXT_PUBLIC_APP_NAME", () => {
    process.env.NEXT_PUBLIC_APP_NAME = "Acme";
    expect(resolveSenderCompanyName()).toBe("Acme");
    process.env.RESEND_FROM_NAME = "Resend";
    expect(resolveSenderCompanyName()).toBe("Acme");
  });

  it("resolveSenderCompanyName falls back to RESEND_FROM_NAME then default", () => {
    process.env.RESEND_FROM_NAME = "Billing";
    expect(resolveSenderCompanyName()).toBe("Billing");
    delete process.env.RESEND_FROM_NAME;
    expect(resolveSenderCompanyName()).toBe("Your team");
  });

  it("resolveInvoiceRecipientEmail prefers primary contact", () => {
    expect(
      resolveInvoiceRecipientEmail(
        { email: "client@x.com" },
        { name: "A", email: "primary@x.com" },
      ),
    ).toBe("primary@x.com");
    expect(resolveInvoiceRecipientEmail({ email: "  client@x.com  " }, null)).toBe("client@x.com");
    expect(resolveInvoiceRecipientEmail({}, null)).toBeNull();
  });

  it("resolveInvoiceClientDisplayName follows name priority", () => {
    expect(
      resolveInvoiceClientDisplayName(
        { company_name: "Co", contact_name: "Contact" },
        { name: "Primary", email: "a@b.c" },
      ),
    ).toBe("Primary");
    expect(resolveInvoiceClientDisplayName({ company_name: "Co", contact_name: "Contact" }, null)).toBe(
      "Contact",
    );
    expect(resolveInvoiceClientDisplayName({ company_name: "Co" }, null)).toBe("Co");
    expect(resolveInvoiceClientDisplayName({}, null)).toBe("Client");
  });
});
