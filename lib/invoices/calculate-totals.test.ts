import { describe, expect, it } from "vitest";
import { calculateInvoiceTotals } from "@/lib/invoices/calculate-totals";
import { getTaxRateForClient } from "@/lib/invoices/tax-rates";

describe("calculateInvoiceTotals", () => {
  it("calculates subtotal, tax, and total without discount", () => {
    const result = calculateInvoiceTotals({
      items: [{ quantity: 2, unitPrice: 50 }],
      taxRate: 10,
      discountType: "none",
      discountValue: 0,
    });

    expect(result.subtotal).toBe(100);
    expect(result.discountAmount).toBe(0);
    expect(result.taxAmount).toBe(10);
    expect(result.total).toBe(110);
  });

  it("applies percentage discount before tax", () => {
    const result = calculateInvoiceTotals({
      items: [{ quantity: 1, unitPrice: 200 }],
      taxRate: 8,
      discountType: "percentage",
      discountValue: 10,
    });

    expect(result.subtotal).toBe(200);
    expect(result.discountAmount).toBe(20);
    expect(result.taxableAmount).toBe(180);
    expect(result.taxAmount).toBe(14.4);
    expect(result.total).toBe(194.4);
  });

  it("caps fixed discount at subtotal", () => {
    const result = calculateInvoiceTotals({
      items: [{ quantity: 1, unitPrice: 50 }],
      taxRate: 0,
      discountType: "fixed",
      discountValue: 100,
    });

    expect(result.discountAmount).toBe(50);
    expect(result.total).toBe(0);
  });
});

describe("getTaxRateForClient", () => {
  it("returns state rate for US clients", () => {
    expect(getTaxRateForClient({ state: "CA", country: "US" })).toBe(8.82);
  });

  it("returns 0 for US clients without a recognized state", () => {
    expect(getTaxRateForClient({ state: "Unknown", country: "US" })).toBe(0);
  });

  it("returns country default for non-US clients", () => {
    expect(getTaxRateForClient({ country: "GB" })).toBe(20);
  });
});
