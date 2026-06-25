export type DiscountType = "none" | "percentage" | "fixed";

export interface InvoiceLineItemInput {
  quantity: number;
  unitPrice: number;
}

export interface InvoiceTotalsInput {
  items: InvoiceLineItemInput[];
  taxRate: number;
  discountType: DiscountType;
  discountValue: number;
}

export interface InvoiceTotals {
  subtotal: number;
  discountAmount: number;
  taxableAmount: number;
  taxAmount: number;
  total: number;
}

function roundCurrency(value: number): number {
  return Math.round(value * 100) / 100;
}

export function calculateInvoiceTotals(input: InvoiceTotalsInput): InvoiceTotals {
  const subtotal = roundCurrency(
    input.items.reduce((sum, item) => sum + item.quantity * item.unitPrice, 0),
  );

  let discountAmount = 0;
  if (input.discountType === "percentage" && input.discountValue > 0) {
    discountAmount = roundCurrency(subtotal * (input.discountValue / 100));
  } else if (input.discountType === "fixed" && input.discountValue > 0) {
    discountAmount = roundCurrency(Math.min(input.discountValue, subtotal));
  }

  const taxableAmount = roundCurrency(Math.max(subtotal - discountAmount, 0));
  const taxAmount = roundCurrency(taxableAmount * (input.taxRate / 100));
  const total = roundCurrency(taxableAmount + taxAmount);

  return { subtotal, discountAmount, taxableAmount, taxAmount, total };
}
