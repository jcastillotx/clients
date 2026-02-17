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
  project_id UUID, -- FK added after projects table is created
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

-- Create time_entry_locks table
-- Prevents editing time entries for locked periods (typically weekly locks for payroll/billing)
CREATE TABLE IF NOT EXISTS public.time_entry_locks (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  user_id UUID NOT NULL REFERENCES public.users(id) ON DELETE CASCADE,
  period_start DATE NOT NULL,
  period_end DATE NOT NULL,
  locked_at TIMESTAMPTZ NOT NULL,
  locked_by UUID NOT NULL REFERENCES public.users(id) ON DELETE CASCADE,
  reason TEXT,
  created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  UNIQUE (user_id, period_start)
);

-- Create request_time_entries table
-- Simplified time tracking specifically for requests (when detailed start/end not needed)
CREATE TABLE IF NOT EXISTS public.request_time_entries (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  request_id UUID NOT NULL REFERENCES public.requests(id) ON DELETE CASCADE,
  user_id UUID NOT NULL REFERENCES public.users(id) ON DELETE CASCADE,
  hours DECIMAL(5, 2) NOT NULL,
  note TEXT,
  logged_at TIMESTAMPTZ NOT NULL,
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
  start_date TIMESTAMPTZ,
  end_date TIMESTAMPTZ,
  estimated_hours DECIMAL(10, 2),
  actual_hours DECIMAL(10, 2) DEFAULT 0,
  budget_amount DECIMAL(12, 2),
  spent_amount DECIMAL(12, 2) DEFAULT 0,
  currency TEXT NOT NULL DEFAULT 'USD' CHECK (currency IN ('USD', 'EUR', 'GBP', 'CAD', 'AUD')),
  project_manager_id UUID REFERENCES public.users(id),
  progress_percent INTEGER DEFAULT 0,
  team_members JSONB,
  metadata JSONB,
  created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  deleted_at TIMESTAMPTZ
);

-- Add deferred foreign key from time_entries to projects (projects table now exists)
ALTER TABLE public.time_entries
  ADD CONSTRAINT fk_time_entries_project_id
  FOREIGN KEY (project_id) REFERENCES public.projects(id) ON DELETE SET NULL;

-- Create project_budgets table
-- Breakdown of project budget by category for detailed expense tracking
CREATE TABLE IF NOT EXISTS public.project_budgets (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  project_id UUID NOT NULL REFERENCES public.projects(id) ON DELETE CASCADE,
  category TEXT NOT NULL CHECK (category IN ('development', 'design', 'marketing', 'infrastructure', 'other')),
  allocated_amount DECIMAL(12, 2) NOT NULL,
  spent_amount DECIMAL(12, 2) DEFAULT 0,
  currency TEXT NOT NULL DEFAULT 'USD' CHECK (currency IN ('USD', 'EUR', 'GBP', 'CAD', 'AUD')),
  notes TEXT,
  created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- Create project_cost_entries table
-- Individual cost/expense entries for tracking project spending
CREATE TABLE IF NOT EXISTS public.project_cost_entries (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  project_id UUID NOT NULL REFERENCES public.projects(id) ON DELETE CASCADE,
  budget_id UUID REFERENCES public.project_budgets(id),
  user_id UUID REFERENCES public.users(id),
  description TEXT NOT NULL,
  amount DECIMAL(12, 2) NOT NULL,
  entry_date TIMESTAMPTZ NOT NULL,
  approved_by UUID REFERENCES public.users(id),
  approved_at TIMESTAMPTZ,
  metadata JSONB,
  created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- Create project_milestones table
-- Major project milestones with progress tracking
CREATE TABLE IF NOT EXISTS public.project_milestones (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  project_id UUID NOT NULL REFERENCES public.projects(id) ON DELETE CASCADE,
  title TEXT NOT NULL,
  description TEXT,
  due_date TIMESTAMPTZ,
  completed_at TIMESTAMPTZ,
  completion_percentage INTEGER DEFAULT 0,
  sort_order INTEGER DEFAULT 0,
  metadata JSONB,
  created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- Create project_deliverables table
-- Specific deliverables within projects or milestones
CREATE TABLE IF NOT EXISTS public.project_deliverables (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  project_id UUID NOT NULL REFERENCES public.projects(id) ON DELETE CASCADE,
  milestone_id UUID REFERENCES public.project_milestones(id),
  title TEXT NOT NULL,
  description TEXT,
  status TEXT NOT NULL DEFAULT 'pending' CHECK (status IN ('pending', 'in_progress', 'review', 'completed', 'rejected')),
  due_date TIMESTAMPTZ,
  delivered_at TIMESTAMPTZ,
  document_id UUID,
  sort_order INTEGER DEFAULT 0,
  metadata JSONB,
  created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
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
  total_amount DECIMAL(10, 2) NOT NULL,
  currency TEXT NOT NULL DEFAULT 'USD' CHECK (currency IN ('USD', 'EUR', 'GBP', 'CAD')),
  valid_until TIMESTAMPTZ,
  created_by UUID NOT NULL REFERENCES public.users(id),
  sent_at TIMESTAMPTZ,
  viewed_at TIMESTAMPTZ,
  accepted_at TIMESTAMPTZ,
  rejected_at TIMESTAMPTZ,
  signature_data JSONB,
  terms TEXT,
  line_items JSONB NOT NULL,
  metadata JSONB,
  created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  deleted_at TIMESTAMPTZ
);

-- Create proposal_selections table
-- Stores client selections for proposal sections with multiple options
CREATE TABLE IF NOT EXISTS public.proposal_selections (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  proposal_id UUID NOT NULL REFERENCES public.proposals(id) ON DELETE CASCADE,
  section_name TEXT NOT NULL,
  selected_option TEXT NOT NULL,
  created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- Create proposal_views table
-- Tracks when and by whom proposals are viewed (analytics)
CREATE TABLE IF NOT EXISTS public.proposal_views (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  proposal_id UUID NOT NULL REFERENCES public.proposals(id) ON DELETE CASCADE,
  viewed_by_ip TEXT,
  viewed_by_user_id UUID REFERENCES public.users(id),
  viewed_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
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
CREATE INDEX IF NOT EXISTS idx_time_entries_user_started_at ON public.time_entries(user_id, started_at);
CREATE INDEX IF NOT EXISTS idx_time_entries_request_started_at ON public.time_entries(request_id, started_at);
CREATE INDEX IF NOT EXISTS idx_time_entries_client_started_at ON public.time_entries(client_id, started_at);

-- Time entry locks indexes
CREATE INDEX IF NOT EXISTS idx_time_entry_locks_user_id ON public.time_entry_locks(user_id);
CREATE INDEX IF NOT EXISTS idx_time_entry_locks_period ON public.time_entry_locks(period_start, period_end);

-- Request time entries indexes
CREATE INDEX IF NOT EXISTS idx_request_time_entries_request_id ON public.request_time_entries(request_id);
CREATE INDEX IF NOT EXISTS idx_request_time_entries_user_id ON public.request_time_entries(user_id);
CREATE INDEX IF NOT EXISTS idx_request_time_entries_request_logged_at ON public.request_time_entries(request_id, logged_at);
CREATE INDEX IF NOT EXISTS idx_request_time_entries_user_logged_at ON public.request_time_entries(user_id, logged_at);

-- Projects indexes
CREATE INDEX IF NOT EXISTS idx_projects_client_id ON public.projects(client_id) WHERE deleted_at IS NULL;
CREATE INDEX IF NOT EXISTS idx_projects_status ON public.projects(status) WHERE deleted_at IS NULL;
CREATE INDEX IF NOT EXISTS idx_projects_project_manager_id ON public.projects(project_manager_id);
CREATE INDEX IF NOT EXISTS idx_projects_start_date ON public.projects(start_date) WHERE deleted_at IS NULL;
CREATE INDEX IF NOT EXISTS idx_projects_end_date ON public.projects(end_date) WHERE deleted_at IS NULL;

-- Project budgets indexes
CREATE INDEX IF NOT EXISTS idx_project_budgets_project_id ON public.project_budgets(project_id);
CREATE INDEX IF NOT EXISTS idx_project_budgets_category ON public.project_budgets(category);

-- Project cost entries indexes
CREATE INDEX IF NOT EXISTS idx_project_cost_entries_project_id ON public.project_cost_entries(project_id);
CREATE INDEX IF NOT EXISTS idx_project_cost_entries_budget_id ON public.project_cost_entries(budget_id);
CREATE INDEX IF NOT EXISTS idx_project_cost_entries_user_id ON public.project_cost_entries(user_id);
CREATE INDEX IF NOT EXISTS idx_project_cost_entries_entry_date ON public.project_cost_entries(entry_date);

-- Project milestones indexes
CREATE INDEX IF NOT EXISTS idx_project_milestones_project_id ON public.project_milestones(project_id);
CREATE INDEX IF NOT EXISTS idx_project_milestones_due_date ON public.project_milestones(due_date);
CREATE INDEX IF NOT EXISTS idx_project_milestones_sort_order ON public.project_milestones(sort_order);

-- Project deliverables indexes
CREATE INDEX IF NOT EXISTS idx_project_deliverables_project_id ON public.project_deliverables(project_id);
CREATE INDEX IF NOT EXISTS idx_project_deliverables_milestone_id ON public.project_deliverables(milestone_id);
CREATE INDEX IF NOT EXISTS idx_project_deliverables_status ON public.project_deliverables(status);
CREATE INDEX IF NOT EXISTS idx_project_deliverables_due_date ON public.project_deliverables(due_date);

-- Proposals indexes
CREATE INDEX IF NOT EXISTS idx_proposals_client_id ON public.proposals(client_id) WHERE deleted_at IS NULL;
CREATE INDEX IF NOT EXISTS idx_proposals_status ON public.proposals(status) WHERE deleted_at IS NULL;
CREATE INDEX IF NOT EXISTS idx_proposals_created_by ON public.proposals(created_by);
CREATE INDEX IF NOT EXISTS idx_proposals_sent_at ON public.proposals(sent_at) WHERE sent_at IS NOT NULL;
CREATE INDEX IF NOT EXISTS idx_proposals_valid_until ON public.proposals(valid_until) WHERE valid_until IS NOT NULL;

-- Proposal selections indexes
CREATE INDEX IF NOT EXISTS idx_proposal_selections_proposal_id ON public.proposal_selections(proposal_id);

-- Proposal views indexes
CREATE INDEX IF NOT EXISTS idx_proposal_views_proposal_id ON public.proposal_views(proposal_id);
CREATE INDEX IF NOT EXISTS idx_proposal_views_user_id ON public.proposal_views(viewed_by_user_id);
CREATE INDEX IF NOT EXISTS idx_proposal_views_ip ON public.proposal_views(viewed_by_ip);
CREATE INDEX IF NOT EXISTS idx_proposal_views_viewed_at ON public.proposal_views(viewed_at);

-- ============================================================================
-- ROW LEVEL SECURITY
-- ============================================================================

-- Enable RLS
ALTER TABLE public.invoices ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.invoice_items ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.requests ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.request_comments ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.time_entries ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.time_entry_locks ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.request_time_entries ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.projects ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.project_budgets ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.project_cost_entries ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.project_milestones ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.project_deliverables ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.proposals ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.proposal_selections ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.proposal_views ENABLE ROW LEVEL SECURITY;

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

-- Users can delete their own time entries (if not locked/billed)
CREATE POLICY "Users can delete their own time entries" ON public.time_entries
  FOR DELETE
  USING (
    (user_id = auth.uid() AND locked_at IS NULL AND billed_at IS NULL)
    OR
    EXISTS (
      SELECT 1 FROM user_roles ur
      JOIN roles r ON ur.role_id = r.id
      WHERE ur.user_id = auth.uid() AND r.name IN ('super_admin', 'admin', 'account_manager')
    )
  );

-- Time Entry Locks RLS Policies
CREATE POLICY "Users can view their own locks" ON public.time_entry_locks
  FOR SELECT
  USING (
    user_id = auth.uid()
    OR
    EXISTS (
      SELECT 1 FROM user_roles ur
      JOIN roles r ON ur.role_id = r.id
      WHERE ur.user_id = auth.uid() AND r.name IN ('super_admin', 'admin', 'account_manager')
    )
  );

CREATE POLICY "Admins can create period locks" ON public.time_entry_locks
  FOR INSERT
  WITH CHECK (
    EXISTS (
      SELECT 1 FROM user_roles ur
      JOIN roles r ON ur.role_id = r.id
      WHERE ur.user_id = auth.uid() AND r.name IN ('super_admin', 'admin', 'account_manager')
    )
  );

CREATE POLICY "Admins can manage locks" ON public.time_entry_locks
  FOR ALL
  USING (
    EXISTS (
      SELECT 1 FROM user_roles ur
      JOIN roles r ON ur.role_id = r.id
      WHERE ur.user_id = auth.uid() AND r.name IN ('super_admin', 'admin', 'account_manager')
    )
  );

-- Request Time Entries RLS Policies
CREATE POLICY "Users can view request time entries" ON public.request_time_entries
  FOR SELECT
  USING (
    user_id = auth.uid()
    OR
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

CREATE POLICY "Users can create request time entries" ON public.request_time_entries
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

CREATE POLICY "Users can update their own request time entries" ON public.request_time_entries
  FOR UPDATE
  USING (
    user_id = auth.uid()
    OR
    EXISTS (
      SELECT 1 FROM user_roles ur
      JOIN roles r ON ur.role_id = r.id
      WHERE ur.user_id = auth.uid() AND r.name IN ('super_admin', 'admin', 'account_manager')
    )
  );

CREATE POLICY "Users can delete their own request time entries" ON public.request_time_entries
  FOR DELETE
  USING (
    user_id = auth.uid()
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

-- Project Budgets RLS
CREATE POLICY "Users can view budgets for their client's projects" ON public.project_budgets
  FOR SELECT
  USING (
    project_id IN (
      SELECT id FROM public.projects
      WHERE client_id IN (SELECT client_id FROM public.users WHERE id = auth.uid())
    )
    OR
    EXISTS (
      SELECT 1 FROM user_roles ur
      JOIN roles r ON ur.role_id = r.id
      WHERE ur.user_id = auth.uid() AND r.name IN ('super_admin', 'admin', 'account_manager')
    )
  );

CREATE POLICY "Staff can manage project budgets" ON public.project_budgets
  FOR ALL
  USING (
    EXISTS (
      SELECT 1 FROM user_roles ur
      JOIN roles r ON ur.role_id = r.id
      WHERE ur.user_id = auth.uid() AND r.name IN ('super_admin', 'admin', 'account_manager')
    )
  );

-- Project Cost Entries RLS
CREATE POLICY "Users can view cost entries for their client's projects" ON public.project_cost_entries
  FOR SELECT
  USING (
    project_id IN (
      SELECT id FROM public.projects
      WHERE client_id IN (SELECT client_id FROM public.users WHERE id = auth.uid())
    )
    OR
    EXISTS (
      SELECT 1 FROM user_roles ur
      JOIN roles r ON ur.role_id = r.id
      WHERE ur.user_id = auth.uid() AND r.name IN ('super_admin', 'admin', 'account_manager')
    )
  );

CREATE POLICY "Staff can manage cost entries" ON public.project_cost_entries
  FOR ALL
  USING (
    EXISTS (
      SELECT 1 FROM user_roles ur
      JOIN roles r ON ur.role_id = r.id
      WHERE ur.user_id = auth.uid() AND r.name IN ('super_admin', 'admin', 'account_manager')
    )
  );

-- Project Milestones RLS
CREATE POLICY "Users can view milestones for their client's projects" ON public.project_milestones
  FOR SELECT
  USING (
    project_id IN (
      SELECT id FROM public.projects
      WHERE client_id IN (SELECT client_id FROM public.users WHERE id = auth.uid())
    )
    OR
    EXISTS (
      SELECT 1 FROM user_roles ur
      JOIN roles r ON ur.role_id = r.id
      WHERE ur.user_id = auth.uid() AND r.name IN ('super_admin', 'admin', 'account_manager')
    )
  );

CREATE POLICY "Staff can manage milestones" ON public.project_milestones
  FOR ALL
  USING (
    EXISTS (
      SELECT 1 FROM user_roles ur
      JOIN roles r ON ur.role_id = r.id
      WHERE ur.user_id = auth.uid() AND r.name IN ('super_admin', 'admin', 'account_manager')
    )
  );

-- Project Deliverables RLS
CREATE POLICY "Users can view deliverables for their client's projects" ON public.project_deliverables
  FOR SELECT
  USING (
    project_id IN (
      SELECT id FROM public.projects
      WHERE client_id IN (SELECT client_id FROM public.users WHERE id = auth.uid())
    )
    OR
    EXISTS (
      SELECT 1 FROM user_roles ur
      JOIN roles r ON ur.role_id = r.id
      WHERE ur.user_id = auth.uid() AND r.name IN ('super_admin', 'admin', 'account_manager')
    )
  );

CREATE POLICY "Staff can manage deliverables" ON public.project_deliverables
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

-- Proposal Selections RLS
CREATE POLICY "Users can view selections for their client's proposals" ON public.proposal_selections
  FOR SELECT
  USING (
    proposal_id IN (
      SELECT id FROM public.proposals
      WHERE client_id IN (SELECT client_id FROM public.users WHERE id = auth.uid())
    )
    OR
    EXISTS (
      SELECT 1 FROM user_roles ur
      JOIN roles r ON ur.role_id = r.id
      WHERE ur.user_id = auth.uid() AND r.name IN ('super_admin', 'admin', 'account_manager')
    )
  );

CREATE POLICY "Staff can manage proposal selections" ON public.proposal_selections
  FOR ALL
  USING (
    EXISTS (
      SELECT 1 FROM user_roles ur
      JOIN roles r ON ur.role_id = r.id
      WHERE ur.user_id = auth.uid() AND r.name IN ('super_admin', 'admin', 'account_manager')
    )
  );

-- Proposal Views RLS (public viewing tracking)
CREATE POLICY "Anyone can insert proposal views" ON public.proposal_views
  FOR INSERT
  WITH CHECK (true);  -- Allow anonymous view tracking

CREATE POLICY "Users can view their client's proposal views" ON public.proposal_views
  FOR SELECT
  USING (
    proposal_id IN (
      SELECT id FROM public.proposals
      WHERE client_id IN (SELECT client_id FROM public.users WHERE id = auth.uid())
    )
    OR
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

DROP TRIGGER IF EXISTS trigger_time_entry_locks_updated_at ON public.time_entry_locks;
CREATE TRIGGER trigger_time_entry_locks_updated_at
  BEFORE UPDATE ON public.time_entry_locks
  FOR EACH ROW
  EXECUTE FUNCTION update_application_tables_updated_at();

DROP TRIGGER IF EXISTS trigger_request_time_entries_updated_at ON public.request_time_entries;
CREATE TRIGGER trigger_request_time_entries_updated_at
  BEFORE UPDATE ON public.request_time_entries
  FOR EACH ROW
  EXECUTE FUNCTION update_application_tables_updated_at();

DROP TRIGGER IF EXISTS trigger_projects_updated_at ON public.projects;
CREATE TRIGGER trigger_projects_updated_at
  BEFORE UPDATE ON public.projects
  FOR EACH ROW
  EXECUTE FUNCTION update_application_tables_updated_at();

DROP TRIGGER IF EXISTS trigger_project_budgets_updated_at ON public.project_budgets;
CREATE TRIGGER trigger_project_budgets_updated_at
  BEFORE UPDATE ON public.project_budgets
  FOR EACH ROW
  EXECUTE FUNCTION update_application_tables_updated_at();

DROP TRIGGER IF EXISTS trigger_project_cost_entries_updated_at ON public.project_cost_entries;
CREATE TRIGGER trigger_project_cost_entries_updated_at
  BEFORE UPDATE ON public.project_cost_entries
  FOR EACH ROW
  EXECUTE FUNCTION update_application_tables_updated_at();

DROP TRIGGER IF EXISTS trigger_project_milestones_updated_at ON public.project_milestones;
CREATE TRIGGER trigger_project_milestones_updated_at
  BEFORE UPDATE ON public.project_milestones
  FOR EACH ROW
  EXECUTE FUNCTION update_application_tables_updated_at();

DROP TRIGGER IF EXISTS trigger_project_deliverables_updated_at ON public.project_deliverables;
CREATE TRIGGER trigger_project_deliverables_updated_at
  BEFORE UPDATE ON public.project_deliverables
  FOR EACH ROW
  EXECUTE FUNCTION update_application_tables_updated_at();

DROP TRIGGER IF EXISTS trigger_proposals_updated_at ON public.proposals;
CREATE TRIGGER trigger_proposals_updated_at
  BEFORE UPDATE ON public.proposals
  FOR EACH ROW
  EXECUTE FUNCTION update_application_tables_updated_at();

DROP TRIGGER IF EXISTS trigger_proposal_selections_updated_at ON public.proposal_selections;
CREATE TRIGGER trigger_proposal_selections_updated_at
  BEFORE UPDATE ON public.proposal_selections
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
GRANT ALL ON public.time_entry_locks TO authenticated;
GRANT ALL ON public.request_time_entries TO authenticated;
GRANT ALL ON public.projects TO authenticated;
GRANT ALL ON public.project_budgets TO authenticated;
GRANT ALL ON public.project_cost_entries TO authenticated;
GRANT ALL ON public.project_milestones TO authenticated;
GRANT ALL ON public.project_deliverables TO authenticated;
GRANT ALL ON public.proposals TO authenticated;
GRANT ALL ON public.proposal_selections TO authenticated;
GRANT ALL ON public.proposal_views TO authenticated;
GRANT ALL ON public.proposal_views TO anon;  -- Allow anonymous view tracking

GRANT ALL ON public.invoices TO service_role;
GRANT ALL ON public.invoice_items TO service_role;
GRANT ALL ON public.requests TO service_role;
GRANT ALL ON public.request_comments TO service_role;
GRANT ALL ON public.time_entries TO service_role;
GRANT ALL ON public.time_entry_locks TO service_role;
GRANT ALL ON public.request_time_entries TO service_role;
GRANT ALL ON public.projects TO service_role;
GRANT ALL ON public.project_budgets TO service_role;
GRANT ALL ON public.project_cost_entries TO service_role;
GRANT ALL ON public.project_milestones TO service_role;
GRANT ALL ON public.project_deliverables TO service_role;
GRANT ALL ON public.proposals TO service_role;
GRANT ALL ON public.proposal_selections TO service_role;
GRANT ALL ON public.proposal_views TO service_role;

-- ============================================================================
-- COMMENTS
-- ============================================================================

COMMENT ON TABLE public.invoices IS 'Client invoices with recurring billing support';
COMMENT ON TABLE public.invoice_items IS 'Line items for invoices';
COMMENT ON TABLE public.requests IS 'Service requests from clients';
COMMENT ON TABLE public.request_comments IS 'Comments on service requests';
COMMENT ON TABLE public.time_entries IS 'Time tracking entries for billable work';
COMMENT ON TABLE public.time_entry_locks IS 'Period locks for time entries (prevents editing locked periods for payroll/billing)';
COMMENT ON TABLE public.request_time_entries IS 'Simplified time tracking for service requests';
COMMENT ON TABLE public.projects IS 'Client projects with budget tracking, milestones, and team management';
COMMENT ON TABLE public.project_budgets IS 'Project budget breakdown by category for detailed expense tracking';
COMMENT ON TABLE public.project_cost_entries IS 'Individual cost/expense entries for project spending tracking';
COMMENT ON TABLE public.project_milestones IS 'Major project milestones with progress tracking';
COMMENT ON TABLE public.project_deliverables IS 'Specific deliverables within projects or milestones';
COMMENT ON TABLE public.proposals IS 'Client proposals with e-signature support, line items, and tracking';
COMMENT ON COLUMN public.projects.team_members IS 'Array of team members with userId, name, role, hourlyRate (JSONB)';
COMMENT ON COLUMN public.projects.metadata IS 'Additional metadata including tags, priority, repository, slackChannel (JSONB)';
COMMENT ON TABLE public.proposal_selections IS 'Client selections for proposal sections with multiple options';
COMMENT ON TABLE public.proposal_views IS 'Tracks when and by whom proposals are viewed for analytics';
COMMENT ON COLUMN public.proposals.line_items IS 'Array of line items with description, quantity, price (JSONB)';
COMMENT ON COLUMN public.proposals.metadata IS 'Additional metadata including notes, tags, attachments (JSONB)';
COMMENT ON COLUMN public.proposals.signature_data IS 'E-signature data including image, signer info, timestamp (JSONB)';
