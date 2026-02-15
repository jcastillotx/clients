-- ============================================================================
-- Migration 012: Create activity_logs and staff_assignments tables
-- These tables are referenced in application code but were never defined.
-- ============================================================================

-- ===========================================
-- TABLE: activity_logs
-- Used by: admin dashboard, Stripe webhooks, reports widgets
-- ===========================================
CREATE TABLE IF NOT EXISTS public.activity_logs (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  client_id UUID REFERENCES public.clients(id) ON DELETE CASCADE,
  causer_id UUID REFERENCES public.users(id) ON DELETE SET NULL,
  subject_type TEXT NOT NULL,
  subject_id UUID,
  description TEXT NOT NULL,
  properties JSONB DEFAULT '{}',
  created_at TIMESTAMPTZ DEFAULT NOW() NOT NULL
);

CREATE INDEX IF NOT EXISTS idx_activity_logs_client_id ON public.activity_logs(client_id);
CREATE INDEX IF NOT EXISTS idx_activity_logs_causer_id ON public.activity_logs(causer_id);
CREATE INDEX IF NOT EXISTS idx_activity_logs_subject_type ON public.activity_logs(subject_type);
CREATE INDEX IF NOT EXISTS idx_activity_logs_subject_id ON public.activity_logs(subject_id);
CREATE INDEX IF NOT EXISTS idx_activity_logs_created_at ON public.activity_logs(created_at DESC);

ALTER TABLE public.activity_logs ENABLE ROW LEVEL SECURITY;

CREATE POLICY "Users can view their client's activity logs" ON public.activity_logs
  FOR SELECT USING (
    client_id = public.get_current_user_client_id()
    OR EXISTS (
      SELECT 1 FROM public.user_roles ur
      JOIN public.roles r ON ur.role_id = r.id
      WHERE ur.user_id = auth.uid()
      AND r.name IN ('super_admin', 'admin')
    )
  );

CREATE POLICY "System can insert activity logs" ON public.activity_logs
  FOR INSERT WITH CHECK (true);

CREATE POLICY "Admins can delete activity logs" ON public.activity_logs
  FOR DELETE USING (
    EXISTS (
      SELECT 1 FROM public.user_roles ur
      JOIN public.roles r ON ur.role_id = r.id
      WHERE ur.user_id = auth.uid()
      AND r.name IN ('super_admin', 'admin')
    )
  );

-- ===========================================
-- TABLE: staff_assignments
-- Used by: client detail page, RLS policies for staff access
-- ===========================================
CREATE TABLE IF NOT EXISTS public.staff_assignments (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  client_id UUID NOT NULL REFERENCES public.clients(id) ON DELETE CASCADE,
  user_id UUID NOT NULL REFERENCES public.users(id) ON DELETE CASCADE,
  role TEXT DEFAULT 'member',
  created_at TIMESTAMPTZ DEFAULT NOW() NOT NULL,
  updated_at TIMESTAMPTZ DEFAULT NOW() NOT NULL,
  UNIQUE(client_id, user_id)
);

CREATE INDEX IF NOT EXISTS idx_staff_assignments_client_id ON public.staff_assignments(client_id);
CREATE INDEX IF NOT EXISTS idx_staff_assignments_user_id ON public.staff_assignments(user_id);

ALTER TABLE public.staff_assignments ENABLE ROW LEVEL SECURITY;

CREATE POLICY "Users can view staff assignments for their client" ON public.staff_assignments
  FOR SELECT USING (
    client_id = public.get_current_user_client_id()
    OR EXISTS (
      SELECT 1 FROM public.user_roles ur
      JOIN public.roles r ON ur.role_id = r.id
      WHERE ur.user_id = auth.uid()
      AND r.name IN ('super_admin', 'admin')
    )
  );

CREATE POLICY "Admins can manage staff assignments" ON public.staff_assignments
  FOR ALL USING (
    EXISTS (
      SELECT 1 FROM public.user_roles ur
      JOIN public.roles r ON ur.role_id = r.id
      WHERE ur.user_id = auth.uid()
      AND r.name IN ('super_admin', 'admin', 'account_manager')
    )
  );

-- Helper function for checking if user is assigned staff for a client
CREATE OR REPLACE FUNCTION public.is_staff_for_client(p_user_id UUID, p_client_id UUID)
RETURNS BOOLEAN
LANGUAGE sql
SECURITY DEFINER
STABLE
AS $$
  SELECT EXISTS (
    SELECT 1 FROM public.staff_assignments
    WHERE user_id = p_user_id AND client_id = p_client_id
  );
$$;
