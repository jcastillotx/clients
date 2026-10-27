BEGIN;
ALTER TABLE public.clients ADD COLUMN IF NOT EXISTS contact_name text;
ALTER TABLE public.invoices ADD COLUMN IF NOT EXISTS reminded_due_7_at timestamptz;
ALTER TABLE public.invoices ADD COLUMN IF NOT EXISTS reminded_overdue_at timestamptz;
NOTIFY pgrst, 'reload schema';
COMMIT;
