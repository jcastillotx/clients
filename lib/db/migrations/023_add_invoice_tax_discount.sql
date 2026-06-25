-- Migration: Add tax, discount, and line item details to invoices
-- Description: Supports location-based tax, invoice-level discounts, and richer line items
-- Created: 2026-06-23

ALTER TABLE public.invoices
  ADD COLUMN IF NOT EXISTS subtotal DECIMAL(10, 2),
  ADD COLUMN IF NOT EXISTS tax_rate DECIMAL(5, 3) DEFAULT 0,
  ADD COLUMN IF NOT EXISTS tax_amount DECIMAL(10, 2) DEFAULT 0,
  ADD COLUMN IF NOT EXISTS discount_type TEXT DEFAULT 'none'
    CHECK (discount_type IN ('none', 'percentage', 'fixed')),
  ADD COLUMN IF NOT EXISTS discount_value DECIMAL(10, 2) DEFAULT 0,
  ADD COLUMN IF NOT EXISTS discount_amount DECIMAL(10, 2) DEFAULT 0;

ALTER TABLE public.invoice_items
  ADD COLUMN IF NOT EXISTS details TEXT;
