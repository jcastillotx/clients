-- Migration: Add RBAC-Based Policies to Core Tables
-- Description: Adds admin/role-based policies after RBAC tables exist
-- Created: 2026-02-15
-- Order: Run after 001 (requires roles and user_roles tables)
-- Dependencies: Requires 000 (clients, users) and 001 (roles, user_roles)

-- ============================================================================
-- CREATE HELPER FUNCTIONS FIRST (before policies that use them)
-- ============================================================================

-- Create helper function to prevent recursion
-- This must be created BEFORE policies that reference it
CREATE OR REPLACE FUNCTION public.get_current_user_client_id()
RETURNS UUID
LANGUAGE sql
SECURITY DEFINER
STABLE
AS $$
  SELECT client_id FROM public.users WHERE id = auth.uid() LIMIT 1;
$$;

-- ============================================================================
-- ADD RBAC-BASED POLICIES TO CLIENTS TABLE
-- ============================================================================

-- Drop simple policies from migration 000 and replace with RBAC-aware versions
DROP POLICY IF EXISTS "Users can view their own client" ON public.clients;
DROP POLICY IF EXISTS "Users can manage their own client" ON public.clients;

-- Clients: Users can view their own client OR admins can view all
CREATE POLICY "Users can view their client or admins view all" ON public.clients
  FOR SELECT
  USING (
    -- Own client (use helper function to prevent recursion)
    id = public.get_current_user_client_id()
    OR
    -- Admins can see all
    EXISTS (
      SELECT 1 FROM public.user_roles ur
      JOIN public.roles r ON ur.role_id = r.id
      WHERE ur.user_id = auth.uid() AND r.name IN ('super_admin', 'admin')
    )
  );

-- Clients: Admins can manage all clients
CREATE POLICY "Admins can manage clients" ON public.clients
  FOR ALL
  USING (
    EXISTS (
      SELECT 1 FROM public.user_roles ur
      JOIN public.roles r ON ur.role_id = r.id
      WHERE ur.user_id = auth.uid() AND r.name IN ('super_admin', 'admin')
    )
  );

-- ============================================================================
-- ADD RBAC-BASED POLICIES TO USERS TABLE
-- ============================================================================

-- Drop simple policies from migration 000
DROP POLICY IF EXISTS "Users can view themselves" ON public.users;
DROP POLICY IF EXISTS "Users can view same client" ON public.users;
DROP POLICY IF EXISTS "Users can update themselves" ON public.users;

-- Users: Can view their own record
CREATE POLICY "users_select_own" ON public.users
  FOR SELECT
  USING (id = auth.uid());

-- Users: Can view users from same client
CREATE POLICY "users_select_same_client" ON public.users
  FOR SELECT
  USING (client_id = public.get_current_user_client_id());

-- Users: Admins can view all users
CREATE POLICY "users_select_admin" ON public.users
  FOR SELECT
  USING (
    EXISTS (
      SELECT 1 FROM public.user_roles ur
      JOIN public.roles r ON ur.role_id = r.id
      WHERE ur.user_id = auth.uid() 
      AND r.name IN ('super_admin', 'admin')
    )
  );

-- Users: Can update their own record
CREATE POLICY "users_update_own" ON public.users
  FOR UPDATE
  USING (id = auth.uid());

-- Users: Admins can manage all users
CREATE POLICY "users_all_for_admins" ON public.users
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
-- COMMENTS
-- ============================================================================

COMMENT ON FUNCTION public.get_current_user_client_id() IS 'Helper function to get current user client_id without RLS recursion';
COMMENT ON POLICY "Users can view their client or admins view all" ON public.clients IS 'RBAC-based policy added in migration 001.5';
COMMENT ON POLICY "users_select_admin" ON public.users IS 'RBAC-based policy added in migration 001.5';
