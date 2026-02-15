-- Migration: Create Support Tickets Tables
-- Description: Creates support_tickets and support_ticket_comments tables with SLA tracking
-- Created: 2026-02-15

-- Create support_tickets table
CREATE TABLE IF NOT EXISTS public.support_tickets (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  
  -- References
  client_id UUID NOT NULL REFERENCES public.clients(id) ON DELETE CASCADE,
  maintenance_plan_id UUID,
  created_by UUID NOT NULL REFERENCES public.users(id),
  assigned_to UUID REFERENCES public.users(id),
  invoice_id UUID,
  
  -- Ticket details
  ticket_number TEXT NOT NULL UNIQUE,
  subject TEXT NOT NULL,
  description TEXT NOT NULL,
  category TEXT NOT NULL CHECK (category IN ('technical', 'billing', 'general', 'feature_request', 'bug_report', 'security', 'performance')),
  status TEXT NOT NULL DEFAULT 'open' CHECK (status IN ('open', 'in_progress', 'waiting_on_client', 'waiting_on_vendor', 'resolved', 'closed')),
  priority TEXT NOT NULL DEFAULT 'medium' CHECK (priority IN ('low', 'medium', 'high', 'urgent')),
  
  -- Billing
  is_billable BOOLEAN NOT NULL DEFAULT true,
  estimated_hours DECIMAL(10, 2),
  actual_hours DECIMAL(10, 2),
  hourly_rate DECIMAL(10, 2),
  
  -- Timeline
  first_response_at TIMESTAMPTZ,
  resolved_at TIMESTAMPTZ,
  closed_at TIMESTAMPTZ,
  
  -- SLA tracking
  sla_response_due_at TIMESTAMPTZ,
  sla_resolution_due_at TIMESTAMPTZ,
  sla_response_breached BOOLEAN NOT NULL DEFAULT false,
  sla_resolution_breached BOOLEAN NOT NULL DEFAULT false,
  sla_response_breached_at TIMESTAMPTZ,
  sla_resolution_breached_at TIMESTAMPTZ,
  
  -- SLA pause tracking
  sla_paused BOOLEAN NOT NULL DEFAULT false,
  sla_paused_duration_minutes INTEGER NOT NULL DEFAULT 0,
  
  -- Escalation
  escalation_level INTEGER NOT NULL DEFAULT 0,
  last_escalated_at TIMESTAMPTZ,
  
  -- Metadata
  metadata JSONB,
  
  -- Timestamps
  created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  deleted_at TIMESTAMPTZ
);

-- Create support_ticket_comments table
CREATE TABLE IF NOT EXISTS public.support_ticket_comments (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  
  -- References
  support_ticket_id UUID NOT NULL REFERENCES public.support_tickets(id) ON DELETE CASCADE,
  user_id UUID NOT NULL REFERENCES public.users(id),
  
  -- Comment content
  comment TEXT NOT NULL,
  is_internal BOOLEAN NOT NULL DEFAULT false,
  
  -- Attachments
  attachments JSONB,
  
  -- Timestamps
  created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  deleted_at TIMESTAMPTZ
);

-- Create indexes for performance
CREATE INDEX IF NOT EXISTS idx_support_tickets_client_id ON public.support_tickets(client_id);
CREATE INDEX IF NOT EXISTS idx_support_tickets_created_by ON public.support_tickets(created_by);
CREATE INDEX IF NOT EXISTS idx_support_tickets_assigned_to ON public.support_tickets(assigned_to);
CREATE INDEX IF NOT EXISTS idx_support_tickets_status ON public.support_tickets(status);
CREATE INDEX IF NOT EXISTS idx_support_tickets_priority ON public.support_tickets(priority);
CREATE INDEX IF NOT EXISTS idx_support_tickets_category ON public.support_tickets(category);
CREATE INDEX IF NOT EXISTS idx_support_tickets_ticket_number ON public.support_tickets(ticket_number);
CREATE INDEX IF NOT EXISTS idx_support_tickets_created_at ON public.support_tickets(created_at);
CREATE INDEX IF NOT EXISTS idx_support_tickets_deleted_at ON public.support_tickets(deleted_at) WHERE deleted_at IS NULL;

CREATE INDEX IF NOT EXISTS idx_support_ticket_comments_ticket_id ON public.support_ticket_comments(support_ticket_id);
CREATE INDEX IF NOT EXISTS idx_support_ticket_comments_user_id ON public.support_ticket_comments(user_id);
CREATE INDEX IF NOT EXISTS idx_support_ticket_comments_created_at ON public.support_ticket_comments(created_at);

-- Create trigger to auto-update updated_at timestamp
CREATE OR REPLACE FUNCTION update_support_tickets_updated_at()
RETURNS TRIGGER AS $$
BEGIN
  NEW.updated_at = NOW();
  RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trigger_support_tickets_updated_at
  BEFORE UPDATE ON public.support_tickets
  FOR EACH ROW
  EXECUTE FUNCTION update_support_tickets_updated_at();

CREATE TRIGGER trigger_support_ticket_comments_updated_at
  BEFORE UPDATE ON public.support_ticket_comments
  FOR EACH ROW
  EXECUTE FUNCTION update_support_tickets_updated_at();

-- Row Level Security (RLS) Policies
ALTER TABLE public.support_tickets ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.support_ticket_comments ENABLE ROW LEVEL SECURITY;

-- Policy: Users can only see their client's support tickets
CREATE POLICY "Users can view their client's support tickets"
  ON public.support_tickets
  FOR SELECT
  USING (
    client_id IN (
      SELECT client_id FROM public.users WHERE id = auth.uid()
    )
  );

-- Policy: Users can create support tickets for their client
CREATE POLICY "Users can create support tickets for their client"
  ON public.support_tickets
  FOR INSERT
  WITH CHECK (
    client_id IN (
      SELECT client_id FROM public.users WHERE id = auth.uid()
    )
  );

-- Policy: Users can update their client's support tickets
CREATE POLICY "Users can update their client's support tickets"
  ON public.support_tickets
  FOR UPDATE
  USING (
    client_id IN (
      SELECT client_id FROM public.users WHERE id = auth.uid()
    )
  );

-- Policy: Users can delete their client's support tickets (soft delete)
CREATE POLICY "Users can delete their client's support tickets"
  ON public.support_tickets
  FOR DELETE
  USING (
    client_id IN (
      SELECT client_id FROM public.users WHERE id = auth.uid()
    )
  );

-- Policy: Users can view comments on their client's tickets
CREATE POLICY "Users can view comments on their client's tickets"
  ON public.support_ticket_comments
  FOR SELECT
  USING (
    support_ticket_id IN (
      SELECT id FROM public.support_tickets
      WHERE client_id IN (
        SELECT client_id FROM public.users WHERE id = auth.uid()
      )
    )
  );

-- Policy: Users can create comments on their client's tickets
CREATE POLICY "Users can create comments on their client's tickets"
  ON public.support_ticket_comments
  FOR INSERT
  WITH CHECK (
    support_ticket_id IN (
      SELECT id FROM public.support_tickets
      WHERE client_id IN (
        SELECT client_id FROM public.users WHERE id = auth.uid()
      )
    )
  );

-- Policy: Users can update their own comments
CREATE POLICY "Users can update their own comments"
  ON public.support_ticket_comments
  FOR UPDATE
  USING (user_id = auth.uid());

-- Policy: Users can delete their own comments
CREATE POLICY "Users can delete their own comments"
  ON public.support_ticket_comments
  FOR DELETE
  USING (user_id = auth.uid());

-- Grant permissions
GRANT ALL ON public.support_tickets TO authenticated;
GRANT ALL ON public.support_ticket_comments TO authenticated;
GRANT ALL ON public.support_tickets TO service_role;
GRANT ALL ON public.support_ticket_comments TO service_role;

-- Add comments for documentation
COMMENT ON TABLE public.support_tickets IS 'Support tickets with SLA tracking, escalation, and billing integration';
COMMENT ON TABLE public.support_ticket_comments IS 'Comments and internal notes on support tickets';
COMMENT ON COLUMN public.support_tickets.ticket_number IS 'Unique ticket number (e.g., TKT-2024-001)';
COMMENT ON COLUMN public.support_tickets.sla_response_due_at IS 'When the first response is due according to SLA';
COMMENT ON COLUMN public.support_tickets.sla_resolution_due_at IS 'When the ticket resolution is due according to SLA';
COMMENT ON COLUMN public.support_tickets.sla_paused IS 'Whether SLA timer is currently paused (e.g., waiting on client)';
COMMENT ON COLUMN public.support_tickets.escalation_level IS 'Number of times ticket has been escalated (0 = not escalated)';
