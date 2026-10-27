BEGIN;
CREATE OR REPLACE FUNCTION public.is_active_account() RETURNS boolean
LANGUAGE sql STABLE SECURITY DEFINER SET search_path = public AS $$
  SELECT EXISTS (SELECT 1 FROM public.users
    WHERE id = auth.uid() AND is_active = true AND deleted_at IS NULL AND status = 'active');
$$;
DO $$ DECLARE item record; BEGIN
  FOR item IN SELECT c.relname FROM pg_class c JOIN pg_namespace n ON n.oid=c.relnamespace
    WHERE n.nspname='public' AND c.relkind='r' AND c.relrowsecurity LOOP
    EXECUTE format('DROP POLICY IF EXISTS active_accounts_only ON public.%I', item.relname);
    EXECUTE format('CREATE POLICY active_accounts_only ON public.%I AS RESTRICTIVE FOR ALL TO authenticated USING (public.is_active_account()) WITH CHECK (public.is_active_account())', item.relname);
  END LOOP;
END $$;
NOTIFY pgrst, 'reload schema';
COMMIT;
