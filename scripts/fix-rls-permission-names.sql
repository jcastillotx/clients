-- Fix RLS Policy Permission Name Mismatches
-- The RLS policies referenced 'manage_requests', 'manage_invoices', 'manage_contracts'
-- but the actual permission names in the permissions table are:
--   requests.create, invoices.update, contracts.update
--
-- This script drops the old policies and recreates them with corrected permission names.

-- ============================================================================
-- 1. FIX REQUESTS INSERT POLICY
-- ============================================================================

DROP POLICY IF EXISTS create_requests ON requests;

CREATE POLICY create_requests ON requests
  FOR INSERT
  WITH CHECK (
    client_id = auth.user_client_id()
    AND auth.user_has_permission('requests.create')
  );

-- ============================================================================
-- 2. FIX INVOICES ALL POLICY (staff manage)
-- ============================================================================

DROP POLICY IF EXISTS staff_manage_invoices ON invoices;

CREATE POLICY staff_manage_invoices ON invoices
  FOR ALL
  USING (
    auth.is_staff()
    AND client_id IN (SELECT client_id FROM auth.user_assigned_client_ids())
    AND auth.user_has_permission('invoices.update')
  );

-- ============================================================================
-- 3. FIX CONTRACTS ALL POLICY (staff manage)
-- ============================================================================

DROP POLICY IF EXISTS staff_manage_contracts ON contracts;

CREATE POLICY staff_manage_contracts ON contracts
  FOR ALL
  USING (
    auth.is_staff()
    AND client_id IN (SELECT client_id FROM auth.user_assigned_client_ids())
    AND auth.user_has_permission('contracts.update')
  );
