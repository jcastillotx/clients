-- Fix for Invoices Page Issues
-- Run this in Supabase SQL Editor to fix common invoices page errors

-- ============================================================================
-- STEP 1: Add missing foreign key constraint
-- ============================================================================

-- Add foreign key for created_by if it doesn't exist
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

-- ============================================================================
-- STEP 2: Fix users table RLS policies (remove recursion)
-- ============================================================================

-- Drop all existing policies on users table
DO $$ 
DECLARE 
    r RECORD;
BEGIN
    FOR r IN (SELECT policyname FROM pg_policies WHERE tablename = 'users' AND schemaname = 'public') LOOP
        EXECUTE 'DROP POLICY IF EXISTS ' || quote_ident(r.policyname) || ' ON public.users';
        RAISE NOTICE 'Dropped policy: %', r.policyname;
    END LOOP;
END $$;

-- Create helper function to get client_id (prevents recursion)
CREATE OR REPLACE FUNCTION public.get_current_user_client_id()
RETURNS UUID
LANGUAGE sql
SECURITY DEFINER
STABLE
AS $$
  SELECT client_id FROM public.users WHERE id = auth.uid() LIMIT 1;
$$;

-- Create simple non-recursive policies
CREATE POLICY "users_select_own"
  ON public.users
  FOR SELECT
  USING (id = auth.uid());

CREATE POLICY "users_select_same_client"
  ON public.users
  FOR SELECT
  USING (client_id = public.get_current_user_client_id());

CREATE POLICY "users_all_for_admins"
  ON public.users
  FOR ALL
  USING (
    EXISTS (
      SELECT 1 FROM public.user_roles ur
      JOIN public.roles r ON ur.role_id = r.id
      WHERE ur.user_id = auth.uid() 
      AND r.name IN ('super_admin', 'admin')
    )
  );

-- ============================================================================
-- STEP 3: Verify invoices table has proper indexes
-- ============================================================================

CREATE INDEX IF NOT EXISTS idx_invoices_created_by ON public.invoices(created_by);
CREATE INDEX IF NOT EXISTS idx_invoices_client_id ON public.invoices(client_id);
CREATE INDEX IF NOT EXISTS idx_invoices_status ON public.invoices(status);

-- ============================================================================
-- STEP 4: Verify RLS policies on invoices
-- ============================================================================

-- Ensure RLS is enabled
ALTER TABLE public.invoices ENABLE ROW LEVEL SECURITY;

-- Drop and recreate invoices policies
DROP POLICY IF EXISTS "Users can view their client's invoices" ON public.invoices;
CREATE POLICY "Users can view their client's invoices" ON public.invoices
  FOR SELECT
  USING (
    client_id = public.get_current_user_client_id()
    OR
    EXISTS (
      SELECT 1 FROM public.user_roles ur
      JOIN public.roles r ON ur.role_id = r.id
      WHERE ur.user_id = auth.uid() 
      AND r.name IN ('super_admin', 'admin', 'account_manager')
    )
  );

-- ============================================================================
-- STEP 5: Test the fix
-- ============================================================================

-- Test query (should return invoices without error)
SELECT 
  i.*,
  c.company_name,
  u.name as created_by_name
FROM public.invoices i
LEFT JOIN public.clients c ON i.client_id = c.id
LEFT JOIN public.users u ON i.created_by = u.id
LIMIT 5;

-- Check foreign keys
SELECT 
    tc.constraint_name, 
    kcu.column_name,
    ccu.table_name AS foreign_table_name
FROM information_schema.table_constraints AS tc 
JOIN information_schema.key_column_usage AS kcu
  ON tc.constraint_name = kcu.constraint_name
JOIN information_schema.constraint_column_usage AS ccu
  ON ccu.constraint_name = tc.constraint_name
WHERE tc.constraint_type = 'FOREIGN KEY' 
  AND tc.table_name = 'invoices'
ORDER BY tc.constraint_name;

-- ============================================================================
-- SUCCESS MESSAGE
-- ============================================================================

DO $$
BEGIN
  RAISE NOTICE '✅ Invoices page fix complete!';
  RAISE NOTICE 'Next steps:';
  RAISE NOTICE '1. Refresh your /invoices page';
  RAISE NOTICE '2. The error should be gone';
  RAISE NOTICE '3. You should see the invoices list (or empty state)';
END $$;
