-- Migration: Add template_id to maintenance_plans
-- Description: Links subscribed maintenance plans back to their admin-created template.
-- Created: 2026-06-25

ALTER TABLE public.maintenance_plans
  ADD COLUMN IF NOT EXISTS template_id UUID;

DO $$
BEGIN
  IF NOT EXISTS (
    SELECT 1
    FROM pg_constraint
    WHERE conname = 'maintenance_plans_template_id_maintenance_plan_templates_id_fk'
  ) THEN
    ALTER TABLE public.maintenance_plans
      ADD CONSTRAINT maintenance_plans_template_id_maintenance_plan_templates_id_fk
      FOREIGN KEY (template_id)
      REFERENCES public.maintenance_plan_templates(id)
      ON DELETE SET NULL;
  END IF;
END $$;

CREATE INDEX IF NOT EXISTS idx_maintenance_plans_template_id
  ON public.maintenance_plans(template_id)
  WHERE template_id IS NOT NULL;
