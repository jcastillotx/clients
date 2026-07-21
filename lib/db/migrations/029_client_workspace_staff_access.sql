-- Migration 029: Let authenticated platform staff work across client workspaces.
-- Staff access is read-only for clients and full management for client files.

ALTER TABLE IF EXISTS public.clients ENABLE ROW LEVEL SECURITY;
ALTER TABLE IF EXISTS public.documents ENABLE ROW LEVEL SECURITY;
ALTER TABLE IF EXISTS public.folders ENABLE ROW LEVEL SECURITY;

DROP POLICY IF EXISTS "staff_view_all_clients" ON public.clients;
CREATE POLICY "staff_view_all_clients" ON public.clients
  FOR SELECT
  USING (public.is_staff_or_above());

DROP POLICY IF EXISTS "staff_manage_client_documents" ON public.documents;
CREATE POLICY "staff_manage_client_documents" ON public.documents
  FOR ALL
  USING (public.is_staff_or_above())
  WITH CHECK (public.is_staff_or_above());

DROP POLICY IF EXISTS "staff_manage_client_folders" ON public.folders;
CREATE POLICY "staff_manage_client_folders" ON public.folders
  FOR ALL
  USING (public.is_staff_or_above())
  WITH CHECK (public.is_staff_or_above());
