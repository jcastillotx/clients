-- Migration: Create Core Application Tables
-- Description: Creates invoices, requests, time tracking, projects, and proposals tables
-- Created: 2026-02-15
-- Order: Run after 004

-- ============================================================================
-- INVOICES & BILLING
-- ============================================================================

-- Create invoices table
CREATE TABLE IF NOT EXISTS public.invoices (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  invoice_number TEXT NOT NULL UNIQUE,
  client_id UUID NOT NULL REFERENCES public.clients(id) ON DELETE CASCADE,
  amount DECIMAL(10, 2) NOT NULL,
  status TEXT NOT NULL DEFAULT 'draft' CHECK (status IN ('draft', 'sent', 'paid', 'overdue', 'cancelled')),
  due_date TIMESTAMPTZ,
  paid_at TIMESTAMPTZ,
  notes TEXT,
  items JSONB,
  is_recurring BOOLEAN DEFAULT false,
  recurring_interval TEXT CHECK (recurring_interval IN ('weekly', 'monthly', 'quarterly', 'yearly')),
  next_recurring_date TIMESTAMPTZ,
  created_by UUID REFERENCES public.users(id),
  created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  deleted_at TIMESTAMPTZ
);

-- Create invoice_items table
CREATE TABLE IF NOT EXISTS public.invoice_items (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  invoice_id UUID NOT NULL REFERENCES public.invoices(id) ON DELETE CASCADE,
  description TEXT NOT NULL,
  quantity DECIMAL(10, 2) NOT NULL DEFAULT 1,
  unit_price DECIMAL(10, 2) NOT NULL,
  amount DECIMAL(10, 2) NOT NULL,
  created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- ============================================================================
-- SERVICE REQUESTS
-- ============================================================================

-- Create requests table
CREATE TABLE IF NOT EXISTS public.requests (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  client_id UUID NOT NULL REFERENCES public.clients(id) ON DELETE CASCADE,
  title TEXT NOT NULL,
  description TEXT,
  status TEXT NOT NULL DEFAULT 'pending' CHECK (status IN ('pending', 'in_progress', 'completed', 'cancelled', 'on_hold', 'awaiting_approval', 'approved', 'rejected')),
  priority TEXT DEFAULT 'medium' CHECK (priority IN ('low', 'medium', 'high')),
  created_by UUID NOT NULL REFERENCES public.users(id),
  assigned_to UUID REFERENCES public.users(id),
  due_date TIMESTAMPTZ,
  custom_fields JSONB,
  created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  deleted_at TIMESTAMPTZ
);

-- Create request_comments table
CREATE TABLE IF NOT EXISTS public.request_comments (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  request_id UUID NOT NULL REFERENCES public.requests(id) ON DELETE CASCADE,
  user_id UUID NOT NULL REFERENCES public.users(id),
  content TEXT NOT NULL,
  created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- ============================================================================
-- TIME TRACKING
-- ============================================================================

-- Create time_entries table
CREATE TABLE IF NOT EXISTS public.time_entries (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  user_id UUID NOT NULL REFERENCES public.users(id) ON DELETE CASCADE,
  client_id UUID REFERENCES public.clients(id) ON DELETE SET NULL,
  request_id UUID REFERENCES public.requests(id) ON DELETE SET NULL,
  task_id UUID,
  project_id UUID,
  description TEXT,
  started_at TIMESTAMPTZ,
  ended_at TIMESTAMPTZ,
  duration_minutes INTEGER,
  is_billable BOOLEAN NOT NULL DEFAULT true,
  hourly_rate DECIMAL(10, 2),
  total_amount DECIMAL(10, 2),
  status TEXT NOT NULL DEFAULT 'pending' CHECK (status IN ('pending', 'approved', 'billed', 'rejected')),
  locked_at TIMESTAMPTZ,
  locked_by UUID REFERENCES public.users(id) ON DELETE SET NULL,
  approved_by UUID REFERENCES public.users(id) ON DELETE SET NULL,
  approved_at TIMESTAMPTZ,
  billed_at TIMESTAMPTZ,
  metadata JSONB,
  created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- ============================================================================
-- PROJECTS
-- ============================================================================

-- Create projects table
CREATE TABLE IF NOT EXISTS public.projects (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  client_id UUID NOT NULL REFERENCES public.clients(id) ON DELETE CASCADE,
  name TEXT NOT NULL,
  description TEXT,
  status TEXT NOT NULL DEFAULT 'planning' CHECK (status IN ('planning', 'active', 'on_hold', 'completed', 'cancelled')),
  start_date DATE,
  end_date DATE,
  budget DECIMAL(12, 2),
  currency TEXT DEFAULT 'USD',
  project_manager_id UUID REFERENCES public.users(id),
  tags JSONB,
  custom_fields JSONB,
  created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  deleted_at TIMESTAMPTZ
);

-- ============================================================================
-- PROPOSALS
-- ============================================================================

-- Create proposals table
CREATE TABLE IF NOT EXISTS public.proposals (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  client_id UUID NOT NULL REFERENCES public.clients(id) ON DELETE CASCADE,
  title TEXT NOT NULL,
  description TEXT,
  status TEXT NOT NULL DEFAULT 'draft' CHECK (status IN ('draft', 'sent', 'viewed', 'accepted', 'rejected', 'expired')),
  total_amount DECIMAL(12, 2),
  currency TEXT DEFAULT 'USD',
  valid_until TIMESTAMPTZ,
  accepted_at TIMESTAMPTZ,
  rejected_at TIMESTAMPTZ,
  rejection_reason TEXT,
  sections JSONB,
  pricing_options JSONB,
  terms TEXT,
  signature_data TEXT,
  signed_by TEXT,
  signed_at TIMESTAMPTZ,
  created_by UUID REFERENCES public.users(id),
  created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  deleted_at TIMESTAMPTZ
);

-- ============================================================================
-- INDEXES
-- ============================================================================

-- Invoices indexes
CREATE INDEX IF NOT EXISTS idx_invoices_client_id ON public.invoices(client_id) WHERE deleted_at IS NULL;
CREATE INDEX IF NOT EXISTS idx_invoices_status ON public.invoices(status) WHERE deleted_at IS NULL;
CREATE INDEX IF NOT EXISTS idx_invoices_due_date ON public.invoices(due_date) WHERE deleted_at IS NULL;
CREATE INDEX IF NOT EXISTS idx_invoices_invoice_number ON public.invoices(invoice_number);

-- Invoice items indexes
CREATE INDEX IF NOT EXISTS idx_invoice_items_invoice_id ON public.invoice_items(invoice_id);

-- Requests indexes
CREATE INDEX IF NOT EXISTS idx_requests_client_id ON public.requests(client_id) WHERE deleted_at IS NULL;
CREATE INDEX IF NOT EXISTS idx_requests_created_by ON public.requests(created_by) WHERE deleted_at IS NULL;
CREATE INDEX IF NOT EXISTS idx_requests_assigned_to ON public.requests(assigned_to) WHERE deleted_at IS NULL;
CREATE INDEX IF NOT EXISTS idx_requests_status ON public.requests(status) WHERE deleted_at IS NULL;
CREATE INDEX IF NOT EXISTS idx_requests_priority ON public.requests(priority) WHERE deleted_at IS NULL;

-- Request comments indexes
CREATE INDEX IF NOT EXISTS idx_request_comments_request_id ON public.request_comments(request_id);
CREATE INDEX IF NOT EXISTS idx_request_comments_user_id ON public.request_comments(user_id);

-- Time entries indexes
CREATE INDEX IF NOT EXISTS idx_time_entries_user_id ON public.time_entries(user_id);
CREATE INDEX IF NOT EXISTS idx_time_entries_client_id ON public.time_entries(client_id);
CREATE INDEX IF NOT EXISTS idx_time_entries_request_id ON public.time_entries(request_id);
CREATE INDEX IF NOT EXISTS idx_time_entries_status ON public.time_entries(status);
CREATE INDEX IF NOT EXISTS idx_time_entries_started_at ON public.time_entries(started_at);

-- Projects indexes
CREATE INDEX IF NOT EXISTS idx_projects_client_id ON public.projects(client_id) WHERE deleted_at IS NULL;
CREATE INDEX IF NOT EXISTS idx_projects_status ON public.projects(status) WHERE deleted_at IS NULL;
CREATE INDEX IF NOT EXISTS idx_projects_project_manager_id ON public.projects(project_manager_id);

-- Proposals indexes
CREATE INDEX IF NOT EXISTS idx_proposals_client_id ON public.proposals(client_id) WHERE deleted_at IS NULL;
CREATE INDEX IF NOT EXISTS idx_proposals_status ON public.proposals(status) WHERE deleted_at IS NULL;
CREATE INDEX IF NOT EXISTS idx_proposals_created_by ON public.proposals(created_by);

-- ============================================================================
-- ROW LEVEL SECURITY
-- ============================================================================

-- Enable RLS
ALTER TABLE public.invoices ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.invoice_items ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.requests ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.request_comments ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.time_entries ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.projects ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.proposals ENABLE ROW LEVEL SECURITY;

-- Invoices RLS Policies
CREATE POLICY "Users can view their client's invoices" ON public.invoices
  FOR SELECT
  USING (
    client_id IN (SELECT client_id FROM public.users WHERE id = auth.uid())
    OR
    EXISTS (
      SELECT 1 FROM user_roles ur
      JOIN roles r ON ur.role_id = r.id
      WHERE ur.user_id = auth.uid() AND r.name IN ('super_admin', 'admin', 'account_manager')
    )
  );

CREATE POLICY "Staff can manage invoices" ON public.invoices
  FOR ALL
  USING (
    EXISTS (
      SELECT 1 FROM user_roles ur
      JOIN roles r ON ur.role_id = r.id
      WHERE ur.user_id = auth.uid() AND r.name IN ('super_admin', 'admin', 'account_manager')
    )
  );

-- Invoice Items RLS Policies
-- Users can view invoice items for their client's invoices
CREATE POLICY "Users can view invoice items" ON public.invoice_items
  FOR SELECT
  USING (
    invoice_id IN (
      SELECT id FROM public.invoices
      WHERE client_id IN (SELECT client_id FROM public.users WHERE id = auth.uid())
    )
    OR
    EXISTS (
      SELECT 1 FROM user_roles ur
      JOIN roles r ON ur.role_id = r.id
      WHERE ur.user_id = auth.uid() AND r.name IN ('super_admin', 'admin', 'account_manager')
    )
  );

-- Users can insert invoice items for their client's invoices
CREATE POLICY "Users can insert invoice items" ON public.invoice_items
  FOR INSERT
  WITH CHECK (
    invoice_id IN (
      SELECT id FROM public.invoices
      WHERE client_id IN (SELECT client_id FROM public.users WHERE id = auth.uid())
    )
    OR
    EXISTS (
      SELECT 1 FROM user_roles ur
      JOIN roles r ON ur.role_id = r.id
      WHERE ur.user_id = auth.uid() AND r.name IN ('super_admin', 'admin', 'account_manager')
    )
  );

-- Users can update invoice items for their client's invoices
CREATE POLICY "Users can update invoice items" ON public.invoice_items
  FOR UPDATE
  USING (
    invoice_id IN (
      SELECT id FROM public.invoices
      WHERE client_id IN (SELECT client_id FROM public.users WHERE id = auth.uid())
    )
    OR
    EXISTS (
      SELECT 1 FROM user_roles ur
      JOIN roles r ON ur.role_id = r.id
      WHERE ur.user_id = auth.uid() AND r.name IN ('super_admin', 'admin', 'account_manager')
    )
  );

-- Users can delete invoice items for their client's invoices
CREATE POLICY "Users can delete invoice items" ON public.invoice_items
  FOR DELETE
  USING (
    invoice_id IN (
      SELECT id FROM public.invoices
      WHERE client_id IN (SELECT client_id FROM public.users WHERE id = auth.uid())
    )
    OR
    EXISTS (
      SELECT 1 FROM user_roles ur
      JOIN roles r ON ur.role_id = r.id
      WHERE ur.user_id = auth.uid() AND r.name IN ('super_admin', 'admin', 'account_manager')
    )
  );

-- Requests RLS Policies
CREATE POLICY "Users can view their client's requests" ON public.requests
  FOR SELECT
  USING (
    client_id IN (SELECT client_id FROM public.users WHERE id = auth.uid())
    OR
    EXISTS (
      SELECT 1 FROM user_roles ur
      JOIN roles r ON ur.role_id = r.id
      WHERE ur.user_id = auth.uid() AND r.name IN ('super_admin', 'admin')
    )
  );

CREATE POLICY "Users can create requests for their client" ON public.requests
  FOR INSERT
  WITH CHECK (
    client_id IN (SELECT client_id FROM public.users WHERE id = auth.uid())
    OR
    EXISTS (
      SELECT 1 FROM user_roles ur
      JOIN roles r ON ur.role_id = r.id
      WHERE ur.user_id = auth.uid() AND r.name IN ('super_admin', 'admin', 'account_manager')
    )
  );

CREATE POLICY "Users can update their client's requests" ON public.requests
  FOR UPDATE
  USING (
    client_id IN (SELECT client_id FROM public.users WHERE id = auth.uid())
    OR
    EXISTS (
      SELECT 1 FROM user_roles ur
      JOIN roles r ON ur.role_id = r.id
      WHERE ur.user_id = auth.uid() AND r.name IN ('super_admin', 'admin', 'account_manager')
    )
  );

-- Request Comments RLS
CREATE POLICY "Users can view comments on their client's requests" ON public.request_comments
  FOR SELECT
  USING (
    request_id IN (
      SELECT id FROM public.requests
      WHERE client_id IN (SELECT client_id FROM public.users WHERE id = auth.uid())
    )
    OR
    EXISTS (
      SELECT 1 FROM user_roles ur
      JOIN roles r ON ur.role_id = r.id
      WHERE ur.user_id = auth.uid() AND r.name IN ('super_admin', 'admin')
    )
  );

CREATE POLICY "Users can create comments" ON public.request_comments
  FOR INSERT
  WITH CHECK (
    request_id IN (
      SELECT id FROM public.requests
      WHERE client_id IN (SELECT client_id FROM public.users WHERE id = auth.uid())
    )
    OR
    EXISTS (
      SELECT 1 FROM user_roles ur
      JOIN roles r ON ur.role_id = r.id
      WHERE ur.user_id = auth.uid() AND r.name IN ('super_admin', 'admin')
    )
  );

-- Users can update their own comments
CREATE POLICY "Users can update own comments" ON public.request_comments
  FOR UPDATE
  USING (
    user_id = auth.uid()
    OR
    EXISTS (
      SELECT 1 FROM user_roles ur
      JOIN roles r ON ur.role_id = r.id
      WHERE ur.user_id = auth.uid() AND r.name IN ('super_admin', 'admin')
    )
  );

-- Users can delete their own comments
CREATE POLICY "Users can delete own comments" ON public.request_comments
  FOR DELETE
  USING (
    user_id = auth.uid()
    OR
    EXISTS (
      SELECT 1 FROM user_roles ur
      JOIN roles r ON ur.role_id = r.id
      WHERE ur.user_id = auth.uid() AND r.name IN ('super_admin', 'admin')
    )
  );

-- Time Entries RLS
CREATE POLICY "Users can view their own time entries" ON public.time_entries
  FOR SELECT
  USING (
    user_id = auth.uid()
    OR
    client_id IN (SELECT client_id FROM public.users WHERE id = auth.uid())
    OR
    EXISTS (
      SELECT 1 FROM user_roles ur
      JOIN roles r ON ur.role_id = r.id
      WHERE ur.user_id = auth.uid() AND r.name IN ('super_admin', 'admin', 'account_manager')
    )
  );

CREATE POLICY "Users can create their own time entries" ON public.time_entries
  FOR INSERT
  WITH CHECK (
    user_id = auth.uid()
    OR
    EXISTS (
      SELECT 1 FROM user_roles ur
      JOIN roles r ON ur.role_id = r.id
      WHERE ur.user_id = auth.uid() AND r.name IN ('super_admin', 'admin', 'account_manager')
    )
  );

CREATE POLICY "Users can update their own time entries" ON public.time_entries
  FOR UPDATE
  USING (
    (user_id = auth.uid() AND locked_at IS NULL)
    OR
    EXISTS (
      SELECT 1 FROM user_roles ur
      JOIN roles r ON ur.role_id = r.id
      WHERE ur.user_id = auth.uid() AND r.name IN ('super_admin', 'admin', 'account_manager')
    )
  );

-- Projects RLS
CREATE POLICY "Users can view their client's projects" ON public.projects
  FOR SELECT
  USING (
    client_id IN (SELECT client_id FROM public.users WHERE id = auth.uid())
    OR
    EXISTS (
      SELECT 1 FROM user_roles ur
      JOIN roles r ON ur.role_id = r.id
      WHERE ur.user_id = auth.uid() AND r.name IN ('super_admin', 'admin', 'account_manager')
    )
  );

CREATE POLICY "Staff can manage projects" ON public.projects
  FOR ALL
  USING (
    EXISTS (
      SELECT 1 FROM user_roles ur
      JOIN roles r ON ur.role_id = r.id
      WHERE ur.user_id = auth.uid() AND r.name IN ('super_admin', 'admin', 'account_manager')
    )
  );

-- Proposals RLS
CREATE POLICY "Users can view their client's proposals" ON public.proposals
  FOR SELECT
  USING (
    client_id IN (SELECT client_id FROM public.users WHERE id = auth.uid())
    OR
    EXISTS (
      SELECT 1 FROM user_roles ur
      JOIN roles r ON ur.role_id = r.id
      WHERE ur.user_id = auth.uid() AND r.name IN ('super_admin', 'admin', 'account_manager')
    )
  );

CREATE POLICY "Staff can manage proposals" ON public.proposals
  FOR ALL
  USING (
    EXISTS (
      SELECT 1 FROM user_roles ur
      JOIN roles r ON ur.role_id = r.id
      WHERE ur.user_id = auth.uid() AND r.name IN ('super_admin', 'admin', 'account_manager')
    )
  );

-- ============================================================================
-- TRIGGERS
-- ============================================================================

-- Create update trigger function if it doesn't exist
CREATE OR REPLACE FUNCTION update_application_tables_updated_at()
RETURNS TRIGGER AS $$
BEGIN
  NEW.updated_at = NOW();
  RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- Add triggers
DROP TRIGGER IF EXISTS trigger_invoices_updated_at ON public.invoices;
CREATE TRIGGER trigger_invoices_updated_at
  BEFORE UPDATE ON public.invoices
  FOR EACH ROW
  EXECUTE FUNCTION update_application_tables_updated_at();

DROP TRIGGER IF EXISTS trigger_invoice_items_updated_at ON public.invoice_items;
CREATE TRIGGER trigger_invoice_items_updated_at
  BEFORE UPDATE ON public.invoice_items
  FOR EACH ROW
  EXECUTE FUNCTION update_application_tables_updated_at();

DROP TRIGGER IF EXISTS trigger_requests_updated_at ON public.requests;
CREATE TRIGGER trigger_requests_updated_at
  BEFORE UPDATE ON public.requests
  FOR EACH ROW
  EXECUTE FUNCTION update_application_tables_updated_at();

DROP TRIGGER IF EXISTS trigger_request_comments_updated_at ON public.request_comments;
CREATE TRIGGER trigger_request_comments_updated_at
  BEFORE UPDATE ON public.request_comments
  FOR EACH ROW
  EXECUTE FUNCTION update_application_tables_updated_at();

DROP TRIGGER IF EXISTS trigger_time_entries_updated_at ON public.time_entries;
CREATE TRIGGER trigger_time_entries_updated_at
  BEFORE UPDATE ON public.time_entries
  FOR EACH ROW
  EXECUTE FUNCTION update_application_tables_updated_at();

DROP TRIGGER IF EXISTS trigger_projects_updated_at ON public.projects;
CREATE TRIGGER trigger_projects_updated_at
  BEFORE UPDATE ON public.projects
  FOR EACH ROW
  EXECUTE FUNCTION update_application_tables_updated_at();

DROP TRIGGER IF EXISTS trigger_proposals_updated_at ON public.proposals;
CREATE TRIGGER trigger_proposals_updated_at
  BEFORE UPDATE ON public.proposals
  FOR EACH ROW
  EXECUTE FUNCTION update_application_tables_updated_at();

-- ============================================================================
-- PERMISSIONS
-- ============================================================================

GRANT ALL ON public.invoices TO authenticated;
GRANT ALL ON public.invoice_items TO authenticated;
GRANT ALL ON public.requests TO authenticated;
GRANT ALL ON public.request_comments TO authenticated;
GRANT ALL ON public.time_entries TO authenticated;
GRANT ALL ON public.projects TO authenticated;
GRANT ALL ON public.proposals TO authenticated;

GRANT ALL ON public.invoices TO service_role;
GRANT ALL ON public.invoice_items TO service_role;
GRANT ALL ON public.requests TO service_role;
GRANT ALL ON public.request_comments TO service_role;
GRANT ALL ON public.time_entries TO service_role;
GRANT ALL ON public.projects TO service_role;
GRANT ALL ON public.proposals TO service_role;

-- ============================================================================
-- COMMENTS
-- ============================================================================

COMMENT ON TABLE public.invoices IS 'Client invoices with recurring billing support';
COMMENT ON TABLE public.invoice_items IS 'Line items for invoices';
COMMENT ON TABLE public.requests IS 'Service requests from clients';
COMMENT ON TABLE public.request_comments IS 'Comments on service requests';
COMMENT ON TABLE public.time_entries IS 'Time tracking entries for billable work';
COMMENT ON TABLE public.projects IS 'Client projects';
COMMENT ON TABLE public.proposals IS 'Client proposals with e-signature support';
