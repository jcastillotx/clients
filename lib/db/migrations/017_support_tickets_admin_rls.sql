-- Migration: Add admin/staff access policies for support tickets
-- Description: Allows privileged roles to view and manage support tickets/comments across clients under authenticated RLS
-- Created: 2026-03-06

ALTER TABLE public.support_tickets ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.support_ticket_comments ENABLE ROW LEVEL SECURITY;

DROP POLICY IF EXISTS "Admins can view all support tickets" ON public.support_tickets;
CREATE POLICY "Admins can view all support tickets"
  ON public.support_tickets
  FOR SELECT
  USING (
    EXISTS (
      SELECT 1
      FROM public.users u
      WHERE u.id = auth.uid()
        AND u.is_super_admin = true
    )
    OR EXISTS (
      SELECT 1
      FROM public.user_roles ur
      JOIN public.roles r ON ur.role_id = r.id
      WHERE ur.user_id = auth.uid()
        AND r.name IN ('super_admin', 'admin', 'account_manager', 'staff')
    )
  );

DROP POLICY IF EXISTS "Admins can manage all support tickets" ON public.support_tickets;
CREATE POLICY "Admins can manage all support tickets"
  ON public.support_tickets
  FOR ALL
  USING (
    EXISTS (
      SELECT 1
      FROM public.users u
      WHERE u.id = auth.uid()
        AND u.is_super_admin = true
    )
    OR EXISTS (
      SELECT 1
      FROM public.user_roles ur
      JOIN public.roles r ON ur.role_id = r.id
      WHERE ur.user_id = auth.uid()
        AND r.name IN ('super_admin', 'admin', 'account_manager', 'staff')
    )
  )
  WITH CHECK (
    EXISTS (
      SELECT 1
      FROM public.users u
      WHERE u.id = auth.uid()
        AND u.is_super_admin = true
    )
    OR EXISTS (
      SELECT 1
      FROM public.user_roles ur
      JOIN public.roles r ON ur.role_id = r.id
      WHERE ur.user_id = auth.uid()
        AND r.name IN ('super_admin', 'admin', 'account_manager', 'staff')
    )
  );

DROP POLICY IF EXISTS "Admins can view all support ticket comments" ON public.support_ticket_comments;
CREATE POLICY "Admins can view all support ticket comments"
  ON public.support_ticket_comments
  FOR SELECT
  USING (
    EXISTS (
      SELECT 1
      FROM public.users u
      WHERE u.id = auth.uid()
        AND u.is_super_admin = true
    )
    OR EXISTS (
      SELECT 1
      FROM public.user_roles ur
      JOIN public.roles r ON ur.role_id = r.id
      WHERE ur.user_id = auth.uid()
        AND r.name IN ('super_admin', 'admin', 'account_manager', 'staff')
    )
  );

DROP POLICY IF EXISTS "Admins can manage all support ticket comments" ON public.support_ticket_comments;
CREATE POLICY "Admins can manage all support ticket comments"
  ON public.support_ticket_comments
  FOR ALL
  USING (
    EXISTS (
      SELECT 1
      FROM public.users u
      WHERE u.id = auth.uid()
        AND u.is_super_admin = true
    )
    OR EXISTS (
      SELECT 1
      FROM public.user_roles ur
      JOIN public.roles r ON ur.role_id = r.id
      WHERE ur.user_id = auth.uid()
        AND r.name IN ('super_admin', 'admin', 'account_manager', 'staff')
    )
  )
  WITH CHECK (
    EXISTS (
      SELECT 1
      FROM public.users u
      WHERE u.id = auth.uid()
        AND u.is_super_admin = true
    )
    OR EXISTS (
      SELECT 1
      FROM public.user_roles ur
      JOIN public.roles r ON ur.role_id = r.id
      WHERE ur.user_id = auth.uid()
        AND r.name IN ('super_admin', 'admin', 'account_manager', 'staff')
    )
  );
