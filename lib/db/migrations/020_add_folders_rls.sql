-- Migration: Add RLS policies for folders
-- Description: Enables RLS and adds policies for the folders table
-- Created: 2026-03-24

-- Enable RLS
ALTER TABLE public.folders ENABLE ROW LEVEL SECURITY;

-- Folders: Users can view their own client's folders
CREATE POLICY "Users can view their client's folders" ON public.folders
  FOR SELECT
  USING (
    client_id = public.get_current_user_client_id()
  );

-- Folders: Admins can manage all folders
CREATE POLICY "Admins can manage all folders" ON public.folders
  FOR ALL
  USING (
    EXISTS (
      SELECT 1 FROM public.user_roles ur
      JOIN public.roles r ON ur.role_id = r.id
      WHERE ur.user_id = auth.uid() AND r.name IN ('super_admin', 'admin')
    )
  );
