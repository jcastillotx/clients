-- Migration: Create service_templates table
-- Description: Adds admin-managed service offerings used to generate proposals.

CREATE TABLE IF NOT EXISTS public.service_templates (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  name TEXT NOT NULL,
  description TEXT,
  category TEXT,
  is_active BOOLEAN NOT NULL DEFAULT true,
  currency TEXT NOT NULL DEFAULT 'USD' CHECK (currency IN ('USD', 'EUR', 'GBP', 'CAD')),
  line_items JSONB NOT NULL,
  total_amount DECIMAL(10, 2) NOT NULL,
  terms TEXT,
  metadata JSONB,
  created_by UUID NOT NULL REFERENCES public.users(id),
  created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

ALTER TABLE public.proposals
  ADD COLUMN IF NOT EXISTS service_template_id UUID;

DO $$
BEGIN
  IF NOT EXISTS (
    SELECT 1
    FROM pg_constraint
    WHERE conname = 'proposals_service_template_id_service_templates_id_fk'
  ) THEN
    ALTER TABLE public.proposals
      ADD CONSTRAINT proposals_service_template_id_service_templates_id_fk
      FOREIGN KEY (service_template_id)
      REFERENCES public.service_templates(id);
  END IF;
END $$;

CREATE INDEX IF NOT EXISTS idx_service_templates_active
  ON public.service_templates(is_active);

CREATE INDEX IF NOT EXISTS idx_service_templates_category
  ON public.service_templates(category)
  WHERE category IS NOT NULL;

CREATE INDEX IF NOT EXISTS idx_service_templates_created_by
  ON public.service_templates(created_by);

CREATE INDEX IF NOT EXISTS idx_proposals_service_template_id
  ON public.proposals(service_template_id)
  WHERE service_template_id IS NOT NULL;

CREATE OR REPLACE FUNCTION update_application_tables_updated_at()
RETURNS TRIGGER AS $$
BEGIN
  NEW.updated_at = NOW();
  RETURN NEW;
END;
$$ LANGUAGE plpgsql;

ALTER TABLE public.service_templates ENABLE ROW LEVEL SECURITY;

DROP POLICY IF EXISTS "Staff can view service templates" ON public.service_templates;
CREATE POLICY "Staff can view service templates" ON public.service_templates
  FOR SELECT
  TO authenticated
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
      JOIN public.roles r ON r.id = ur.role_id
      WHERE ur.user_id = auth.uid()
        AND r.name IN ('admin', 'super_admin', 'staff')
    )
  );

DROP POLICY IF EXISTS "Admins can manage service templates" ON public.service_templates;
CREATE POLICY "Admins can manage service templates" ON public.service_templates
  FOR ALL
  TO authenticated
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
      JOIN public.roles r ON r.id = ur.role_id
      WHERE ur.user_id = auth.uid()
        AND r.name IN ('admin', 'super_admin')
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
      JOIN public.roles r ON r.id = ur.role_id
      WHERE ur.user_id = auth.uid()
        AND r.name IN ('admin', 'super_admin')
    )
  );

DROP TRIGGER IF EXISTS trigger_service_templates_updated_at ON public.service_templates;
CREATE TRIGGER trigger_service_templates_updated_at
  BEFORE UPDATE ON public.service_templates
  FOR EACH ROW
  EXECUTE FUNCTION update_application_tables_updated_at();

GRANT ALL ON public.service_templates TO authenticated;
GRANT ALL ON public.service_templates TO service_role;

COMMENT ON TABLE public.service_templates IS 'Admin-managed service offerings clients can request to generate proposals';
COMMENT ON COLUMN public.service_templates.line_items IS 'Array of service line items with description, quantity, price, and amount (JSONB)';
