BEGIN;
ALTER TABLE public.invoices ADD COLUMN IF NOT EXISTS stripe_payment_intent_id text;
ALTER TABLE public.invoices ADD COLUMN IF NOT EXISTS payment_method text;
ALTER TABLE public.invoices ADD COLUMN IF NOT EXISTS refunded_at timestamptz;
ALTER TABLE public.clients ADD COLUMN IF NOT EXISTS stripe_customer_id text;
ALTER TABLE public.invoices DROP CONSTRAINT IF EXISTS invoices_status_check;
ALTER TABLE public.invoices ADD CONSTRAINT invoices_status_check CHECK
  (status IN ('draft','sent','paid','overdue','cancelled','refunded'));
NOTIFY pgrst, 'reload schema';
COMMIT;
