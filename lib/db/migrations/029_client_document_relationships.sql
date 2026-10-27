-- Restore relationships required by PostgREST document and contract embeds.
BEGIN;
ALTER TABLE public.documents ADD CONSTRAINT documents_client_id_fkey
  FOREIGN KEY (client_id) REFERENCES public.clients(id);
ALTER TABLE public.documents ADD CONSTRAINT documents_uploaded_by_fkey
  FOREIGN KEY (uploaded_by) REFERENCES public.users(id);
ALTER TABLE public.contracts ADD CONSTRAINT contracts_client_id_fkey
  FOREIGN KEY (client_id) REFERENCES public.clients(id);
NOTIFY pgrst, 'reload schema';
COMMIT;
