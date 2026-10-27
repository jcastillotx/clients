BEGIN;
-- User-editable authentication metadata is presentation data, never authority.
CREATE OR REPLACE FUNCTION public.handle_new_user() RETURNS trigger
LANGUAGE plpgsql SECURITY DEFINER SET search_path = public AS $$
BEGIN
  INSERT INTO public.users (id, name, email, client_id, is_super_admin)
  VALUES (NEW.id, COALESCE(NEW.raw_user_meta_data->>'name', NEW.email), NEW.email, NULL, false)
  ON CONFLICT (id) DO UPDATE SET email = EXCLUDED.email, updated_at = NOW();
  RETURN NEW;
END;
$$;

CREATE OR REPLACE FUNCTION public.protect_user_authority() RETURNS trigger
LANGUAGE plpgsql SET search_path = public AS $$
BEGIN
  IF current_user IN ('postgres', 'supabase_admin', 'service_role') THEN RETURN NEW; END IF;
  IF (NEW.id, NEW.email, NEW.client_id, NEW.is_super_admin, NEW.is_active,
      NEW.status, NEW.deleted_at, NEW.manual_permissions, NEW.security_settings)
     IS DISTINCT FROM
     (OLD.id, OLD.email, OLD.client_id, OLD.is_super_admin, OLD.is_active,
      OLD.status, OLD.deleted_at, OLD.manual_permissions, OLD.security_settings)
     AND NOT public.user_has_role(auth.uid(), 'admin')
     AND NOT public.user_has_role(auth.uid(), 'super_admin') THEN
    RAISE EXCEPTION 'Only administrators can change account authority' USING ERRCODE = '42501';
  END IF;
  RETURN NEW;
END;
$$;
DROP TRIGGER IF EXISTS protect_user_authority ON public.users;
CREATE TRIGGER protect_user_authority BEFORE UPDATE ON public.users
FOR EACH ROW EXECUTE FUNCTION public.protect_user_authority();
COMMIT;
