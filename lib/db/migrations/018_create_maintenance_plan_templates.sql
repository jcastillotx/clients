-- Migration: Create Maintenance Plan Templates Table
-- Description: Creates maintenance_plan_templates table for admin-managed maintenance plan templates
-- Created: 2026-03-06

CREATE TABLE IF NOT EXISTS public.maintenance_plan_templates (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  name TEXT NOT NULL,
  description TEXT,
  plan_type TEXT NOT NULL DEFAULT 'standard',
  is_active BOOLEAN NOT NULL DEFAULT true,
  billing_cycle TEXT NOT NULL DEFAULT 'monthly',
  monthly_rate DECIMAL(10,2) NOT NULL,
  currency TEXT NOT NULL DEFAULT 'USD',
  included_hours DECIMAL(10,2) NOT NULL,
  hourly_rate_overage DECIMAL(10,2) NOT NULL,
  auto_renew BOOLEAN NOT NULL DEFAULT true,
  rollover_enabled BOOLEAN NOT NULL DEFAULT false,
  max_rollover_hours DECIMAL(10,2),
  overage_billing_enabled BOOLEAN NOT NULL DEFAULT true,
  overage_approval_required BOOLEAN NOT NULL DEFAULT false,
  overage_notification_threshold DECIMAL(5,2) NOT NULL DEFAULT 90,
  renewal_term_months INTEGER NOT NULL DEFAULT 12,
  services_included JSONB,
  metadata JSONB,
  created_by UUID NOT NULL REFERENCES public.users(id),
  created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
  updated_at TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE INDEX IF NOT EXISTS idx_maintenance_plan_templates_is_active
  ON public.maintenance_plan_templates(is_active);

CREATE INDEX IF NOT EXISTS idx_maintenance_plan_templates_plan_type
  ON public.maintenance_plan_templates(plan_type);

CREATE INDEX IF NOT EXISTS idx_maintenance_plan_templates_created_by
  ON public.maintenance_plan_templates(created_by);

CREATE INDEX IF NOT EXISTS idx_maintenance_plan_templates_created_at
  ON public.maintenance_plan_templates(created_at DESC);

CREATE TRIGGER update_maintenance_plan_templates_updated_at
  BEFORE UPDATE ON public.maintenance_plan_templates
  FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

ALTER TABLE IF EXISTS public.maintenance_plan_templates ENABLE ROW LEVEL SECURITY;

DROP POLICY IF EXISTS "view_maintenance_plan_templates" ON public.maintenance_plan_templates;
CREATE POLICY "view_maintenance_plan_templates"
  ON public.maintenance_plan_templates
  FOR SELECT
  USING (
    is_active = true
    OR public.is_staff_or_above()
  );

DROP POLICY IF EXISTS "manage_maintenance_plan_templates" ON public.maintenance_plan_templates;
CREATE POLICY "manage_maintenance_plan_templates"
  ON public.maintenance_plan_templates
  FOR ALL
  USING (public.is_staff_or_above())
  WITH CHECK (public.is_staff_or_above());

GRANT ALL ON public.maintenance_plan_templates TO authenticated;
GRANT ALL ON public.maintenance_plan_templates TO service_role;
