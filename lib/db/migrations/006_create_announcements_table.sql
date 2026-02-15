-- Migration: Create Announcements Table for Client News Ticker
-- Description: Stores announcements and news items for client dashboard ticker
-- Created: 2026-02-15

-- Create announcements table
CREATE TABLE IF NOT EXISTS public.announcements (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  
  -- Content
  title TEXT NOT NULL,
  content TEXT NOT NULL,
  link_url TEXT,
  link_text TEXT,
  
  -- Targeting
  client_id UUID REFERENCES public.clients(id) ON DELETE CASCADE,
  -- NULL client_id means announcement is global (shown to all clients)
  
  -- Status
  is_active BOOLEAN NOT NULL DEFAULT true,
  priority INTEGER DEFAULT 0, -- Higher priority shows first
  
  -- Scheduling
  starts_at TIMESTAMPTZ,
  expires_at TIMESTAMPTZ,
  
  -- Metadata
  metadata JSONB,
  
  -- Audit
  created_by UUID REFERENCES public.users(id),
  created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  deleted_at TIMESTAMPTZ
);

-- Create indexes
CREATE INDEX IF NOT EXISTS idx_announcements_client_id ON public.announcements(client_id) WHERE deleted_at IS NULL;
CREATE INDEX IF NOT EXISTS idx_announcements_is_active ON public.announcements(is_active) WHERE is_active = true AND deleted_at IS NULL;
CREATE INDEX IF NOT EXISTS idx_announcements_priority ON public.announcements(priority DESC) WHERE is_active = true AND deleted_at IS NULL;
CREATE INDEX IF NOT EXISTS idx_announcements_created_at ON public.announcements(created_at DESC) WHERE deleted_at IS NULL;
CREATE INDEX IF NOT EXISTS idx_announcements_expires_at ON public.announcements(expires_at) WHERE expires_at > NOW() AND deleted_at IS NULL;

-- Enable RLS
ALTER TABLE public.announcements ENABLE ROW LEVEL SECURITY;

-- RLS Policies
DROP POLICY IF EXISTS "Clients can view their announcements" ON public.announcements;
CREATE POLICY "Clients can view their announcements" ON public.announcements
  FOR SELECT
  USING (
    -- Global announcements (client_id is NULL)
    client_id IS NULL
    OR
    -- Client-specific announcements (use helper function to prevent recursion)
    client_id = public.get_current_user_client_id()
    OR
    -- Staff can see all
    EXISTS (
      SELECT 1 FROM public.user_roles ur
      JOIN public.roles r ON ur.role_id = r.id
      WHERE ur.user_id = auth.uid() 
      AND r.name IN ('super_admin', 'admin', 'account_manager', 'staff')
    )
  );

DROP POLICY IF EXISTS "Staff can manage announcements" ON public.announcements;
CREATE POLICY "Staff can manage announcements" ON public.announcements
  FOR ALL
  USING (
    EXISTS (
      SELECT 1 FROM public.user_roles ur
      JOIN public.roles r ON ur.role_id = r.id
      WHERE ur.user_id = auth.uid() 
      AND r.name IN ('super_admin', 'admin', 'account_manager')
    )
  );

-- Create trigger for updated_at
CREATE OR REPLACE FUNCTION update_announcements_updated_at()
RETURNS TRIGGER AS $$
BEGIN
  NEW.updated_at = NOW();
  RETURN NEW;
END;
$$ LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS trigger_announcements_updated_at ON public.announcements;
CREATE TRIGGER trigger_announcements_updated_at
  BEFORE UPDATE ON public.announcements
  FOR EACH ROW
  EXECUTE FUNCTION update_announcements_updated_at();

-- Grant permissions
GRANT ALL ON public.announcements TO authenticated;
GRANT ALL ON public.announcements TO service_role;

-- Add comments
COMMENT ON TABLE public.announcements IS 'Announcements and news items shown in client dashboard news ticker';
COMMENT ON COLUMN public.announcements.client_id IS 'NULL for global announcements, specific client_id for targeted announcements';
COMMENT ON COLUMN public.announcements.priority IS 'Higher numbers show first (0 = normal, 10 = high priority)';

-- Insert sample announcements
INSERT INTO public.announcements (title, content, is_active, priority)
VALUES 
  ('Welcome!', 'Welcome to your client dashboard. We are here to help you succeed.', true, 10),
  ('New Features', 'Check out our new time tracking and project management tools', true, 5),
  ('Support Available', 'Need help? Create a support ticket anytime from the Support section', true, 3),
  ('Monthly Reports', 'Your monthly performance reports are now available in the Reports section', true, 0)
ON CONFLICT DO NOTHING;
