BEGIN;
CREATE TABLE IF NOT EXISTS public.folders (
 id uuid PRIMARY KEY DEFAULT gen_random_uuid(), name text NOT NULL, description text,
 client_id uuid NOT NULL REFERENCES public.clients(id), type text,
 created_at timestamptz DEFAULT now(), updated_at timestamptz DEFAULT now()
);
ALTER TABLE public.folders ENABLE ROW LEVEL SECURITY;
CREATE POLICY folders_tenant_read ON public.folders FOR SELECT TO authenticated
 USING (client_id = public.get_current_user_client_id() OR public.is_staff_or_above());
CREATE POLICY folders_staff_manage ON public.folders FOR ALL TO authenticated
 USING (public.is_staff_or_above()) WITH CHECK (public.is_staff_or_above());
CREATE POLICY folders_active_accounts ON public.folders AS RESTRICTIVE FOR ALL TO authenticated
 USING (public.is_active_account()) WITH CHECK (public.is_active_account());
GRANT ALL ON public.folders TO authenticated, service_role;
CREATE UNIQUE INDEX IF NOT EXISTS folders_brand_management_client_unique ON public.folders(client_id)
 WHERE type = 'brand_management';
ALTER TABLE public.documents ADD COLUMN IF NOT EXISTS folder_id uuid REFERENCES public.folders(id);
NOTIFY pgrst, 'reload schema';
COMMIT;
