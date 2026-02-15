-- Fix missing foreign key constraint between invoices.created_by and users.id
-- This constraint is required by PostgREST to resolve the relationship hint
-- used in Supabase queries like: users!invoices_created_by_fkey(id, name)

DO $$
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.table_constraints
    WHERE constraint_name = 'invoices_created_by_fkey'
    AND table_name = 'invoices'
  ) THEN
    ALTER TABLE public.invoices
    ADD CONSTRAINT invoices_created_by_fkey
    FOREIGN KEY (created_by)
    REFERENCES public.users(id);

    RAISE NOTICE 'Added foreign key: invoices_created_by_fkey';
  ELSE
    RAISE NOTICE 'Foreign key already exists: invoices_created_by_fkey';
  END IF;
END $$;

-- Add index for performance
CREATE INDEX IF NOT EXISTS idx_invoices_created_by ON public.invoices(created_by);
