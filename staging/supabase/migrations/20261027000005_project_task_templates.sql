BEGIN;
CREATE TABLE IF NOT EXISTS public.project_task_templates (
 id uuid PRIMARY KEY DEFAULT gen_random_uuid(), name text NOT NULL, description text,
 category text NOT NULL DEFAULT 'general', icon text DEFAULT 'ClipboardList', color text DEFAULT '#3b82f6',
 estimated_hours integer, is_system boolean NOT NULL DEFAULT false, is_active boolean NOT NULL DEFAULT true,
 created_by uuid REFERENCES public.users(id), phases jsonb NOT NULL, metadata jsonb,
 created_at timestamptz NOT NULL DEFAULT now(), updated_at timestamptz NOT NULL DEFAULT now(), deleted_at timestamptz
);
ALTER TABLE public.project_task_templates ENABLE ROW LEVEL SECURITY;
CREATE POLICY project_templates_staff_read ON public.project_task_templates FOR SELECT TO authenticated
 USING (public.is_staff_or_above() AND public.is_active_account());
CREATE POLICY project_templates_admin_write ON public.project_task_templates FOR ALL TO authenticated
 USING (public.is_admin_or_super_admin() AND public.is_active_account())
 WITH CHECK (public.is_admin_or_super_admin() AND public.is_active_account());
NOTIFY pgrst, 'reload schema';
COMMIT;
