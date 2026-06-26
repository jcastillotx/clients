-- Migration: Create project review surfaces and comments
-- Description: Adds website/image review items and pinned client comments for project feedback.
-- Created: 2026-06-26

CREATE OR REPLACE FUNCTION update_application_tables_updated_at()
RETURNS TRIGGER AS $$
BEGIN
  NEW.updated_at = NOW();
  RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TABLE IF NOT EXISTS public.project_review_items (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  project_id UUID NOT NULL REFERENCES public.projects(id) ON DELETE CASCADE,
  type TEXT NOT NULL CHECK (type IN ('website', 'image')),
  title TEXT NOT NULL,
  website_url TEXT,
  image_storage_path TEXT,
  image_file_name TEXT,
  image_mime_type TEXT,
  image_size INTEGER,
  status TEXT NOT NULL DEFAULT 'open' CHECK (status IN ('open', 'in_review', 'resolved', 'archived')),
  created_by UUID REFERENCES public.users(id),
  metadata JSONB,
  created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  CONSTRAINT project_review_items_id_project_id_unique UNIQUE (id, project_id),
  CONSTRAINT project_review_items_source_check CHECK (
    (type = 'website' AND website_url IS NOT NULL)
    OR (type = 'image' AND image_storage_path IS NOT NULL)
  )
);

CREATE TABLE IF NOT EXISTS public.project_review_comments (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  review_item_id UUID NOT NULL REFERENCES public.project_review_items(id) ON DELETE CASCADE,
  project_id UUID NOT NULL REFERENCES public.projects(id) ON DELETE CASCADE,
  author_id UUID REFERENCES public.users(id),
  body TEXT NOT NULL,
  x_percent DECIMAL(6, 3),
  y_percent DECIMAL(6, 3),
  status TEXT NOT NULL DEFAULT 'open' CHECK (status IN ('open', 'in_review', 'resolved', 'archived')),
  metadata JSONB,
  created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  CONSTRAINT project_review_comments_coordinates_check CHECK (
    (x_percent IS NULL AND y_percent IS NULL)
    OR (
      x_percent IS NOT NULL
      AND y_percent IS NOT NULL
      AND x_percent >= 0
      AND x_percent <= 100
      AND y_percent >= 0
      AND y_percent <= 100
    )
  ),
  CONSTRAINT project_review_comments_review_project_fk
    FOREIGN KEY (review_item_id, project_id)
    REFERENCES public.project_review_items(id, project_id)
    ON DELETE CASCADE
);

CREATE UNIQUE INDEX IF NOT EXISTS project_review_items_id_project_id_idx
  ON public.project_review_items(id, project_id);

CREATE INDEX IF NOT EXISTS project_review_items_project_id_idx
  ON public.project_review_items(project_id);

CREATE INDEX IF NOT EXISTS project_review_items_status_idx
  ON public.project_review_items(status);

CREATE INDEX IF NOT EXISTS project_review_comments_review_item_id_idx
  ON public.project_review_comments(review_item_id);

CREATE INDEX IF NOT EXISTS project_review_comments_project_id_idx
  ON public.project_review_comments(project_id);

CREATE INDEX IF NOT EXISTS project_review_comments_status_idx
  ON public.project_review_comments(status);

ALTER TABLE public.project_review_items ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.project_review_comments ENABLE ROW LEVEL SECURITY;

DROP POLICY IF EXISTS "Project members can view review items" ON public.project_review_items;
CREATE POLICY "Project members can view review items" ON public.project_review_items
  FOR SELECT
  TO authenticated
  USING (
    EXISTS (
      SELECT 1
      FROM public.projects p
      JOIN public.users u ON u.id = auth.uid()
      WHERE p.id = project_review_items.project_id
        AND p.deleted_at IS NULL
        AND (
          u.is_super_admin = true
          OR u.client_id = p.client_id
          OR EXISTS (
            SELECT 1
            FROM public.user_roles ur
            JOIN public.roles r ON r.id = ur.role_id
            WHERE ur.user_id = auth.uid()
              AND r.name IN ('admin', 'super_admin', 'account_manager', 'staff')
          )
        )
    )
  );

DROP POLICY IF EXISTS "Project members can create review items" ON public.project_review_items;
CREATE POLICY "Project members can create review items" ON public.project_review_items
  FOR INSERT
  TO authenticated
  WITH CHECK (
    EXISTS (
      SELECT 1
      FROM public.projects p
      JOIN public.users u ON u.id = auth.uid()
      WHERE p.id = project_review_items.project_id
        AND p.deleted_at IS NULL
        AND (
          u.is_super_admin = true
          OR u.client_id = p.client_id
          OR EXISTS (
            SELECT 1
            FROM public.user_roles ur
            JOIN public.roles r ON r.id = ur.role_id
            WHERE ur.user_id = auth.uid()
              AND r.name IN ('admin', 'super_admin', 'account_manager', 'staff')
          )
        )
    )
  );

DROP POLICY IF EXISTS "Project members can update review items" ON public.project_review_items;
CREATE POLICY "Project members can update review items" ON public.project_review_items
  FOR UPDATE
  TO authenticated
  USING (
    EXISTS (
      SELECT 1
      FROM public.projects p
      JOIN public.users u ON u.id = auth.uid()
      WHERE p.id = project_review_items.project_id
        AND p.deleted_at IS NULL
        AND (
          u.is_super_admin = true
          OR u.client_id = p.client_id
          OR EXISTS (
            SELECT 1
            FROM public.user_roles ur
            JOIN public.roles r ON r.id = ur.role_id
            WHERE ur.user_id = auth.uid()
              AND r.name IN ('admin', 'super_admin', 'account_manager', 'staff')
          )
        )
    )
  )
  WITH CHECK (
    EXISTS (
      SELECT 1
      FROM public.projects p
      JOIN public.users u ON u.id = auth.uid()
      WHERE p.id = project_review_items.project_id
        AND p.deleted_at IS NULL
        AND (
          u.is_super_admin = true
          OR u.client_id = p.client_id
          OR EXISTS (
            SELECT 1
            FROM public.user_roles ur
            JOIN public.roles r ON r.id = ur.role_id
            WHERE ur.user_id = auth.uid()
              AND r.name IN ('admin', 'super_admin', 'account_manager', 'staff')
          )
        )
    )
  );

DROP POLICY IF EXISTS "Project members can view review comments" ON public.project_review_comments;
CREATE POLICY "Project members can view review comments" ON public.project_review_comments
  FOR SELECT
  TO authenticated
  USING (
    EXISTS (
      SELECT 1
      FROM public.projects p
      JOIN public.users u ON u.id = auth.uid()
      WHERE p.id = project_review_comments.project_id
        AND p.deleted_at IS NULL
        AND (
          u.is_super_admin = true
          OR u.client_id = p.client_id
          OR EXISTS (
            SELECT 1
            FROM public.user_roles ur
            JOIN public.roles r ON r.id = ur.role_id
            WHERE ur.user_id = auth.uid()
              AND r.name IN ('admin', 'super_admin', 'account_manager', 'staff')
          )
        )
    )
  );

DROP POLICY IF EXISTS "Project members can create review comments" ON public.project_review_comments;
CREATE POLICY "Project members can create review comments" ON public.project_review_comments
  FOR INSERT
  TO authenticated
  WITH CHECK (
    EXISTS (
      SELECT 1
      FROM public.projects p
      JOIN public.users u ON u.id = auth.uid()
      WHERE p.id = project_review_comments.project_id
        AND p.deleted_at IS NULL
        AND (
          u.is_super_admin = true
          OR u.client_id = p.client_id
          OR EXISTS (
            SELECT 1
            FROM public.user_roles ur
            JOIN public.roles r ON r.id = ur.role_id
            WHERE ur.user_id = auth.uid()
              AND r.name IN ('admin', 'super_admin', 'account_manager', 'staff')
          )
        )
    )
  );

DROP POLICY IF EXISTS "Project members can update review comments" ON public.project_review_comments;
CREATE POLICY "Project members can update review comments" ON public.project_review_comments
  FOR UPDATE
  TO authenticated
  USING (
    EXISTS (
      SELECT 1
      FROM public.projects p
      JOIN public.users u ON u.id = auth.uid()
      WHERE p.id = project_review_comments.project_id
        AND p.deleted_at IS NULL
        AND (
          u.is_super_admin = true
          OR u.client_id = p.client_id
          OR EXISTS (
            SELECT 1
            FROM public.user_roles ur
            JOIN public.roles r ON r.id = ur.role_id
            WHERE ur.user_id = auth.uid()
              AND r.name IN ('admin', 'super_admin', 'account_manager', 'staff')
          )
        )
    )
  )
  WITH CHECK (
    EXISTS (
      SELECT 1
      FROM public.projects p
      JOIN public.users u ON u.id = auth.uid()
      WHERE p.id = project_review_comments.project_id
        AND p.deleted_at IS NULL
        AND (
          u.is_super_admin = true
          OR u.client_id = p.client_id
          OR EXISTS (
            SELECT 1
            FROM public.user_roles ur
            JOIN public.roles r ON r.id = ur.role_id
            WHERE ur.user_id = auth.uid()
              AND r.name IN ('admin', 'super_admin', 'account_manager', 'staff')
          )
        )
    )
  );

DROP TRIGGER IF EXISTS trigger_project_review_items_updated_at ON public.project_review_items;
CREATE TRIGGER trigger_project_review_items_updated_at
  BEFORE UPDATE ON public.project_review_items
  FOR EACH ROW
  EXECUTE FUNCTION update_application_tables_updated_at();

DROP TRIGGER IF EXISTS trigger_project_review_comments_updated_at ON public.project_review_comments;
CREATE TRIGGER trigger_project_review_comments_updated_at
  BEFORE UPDATE ON public.project_review_comments
  FOR EACH ROW
  EXECUTE FUNCTION update_application_tables_updated_at();

GRANT ALL ON public.project_review_items TO authenticated;
GRANT ALL ON public.project_review_items TO service_role;
GRANT ALL ON public.project_review_comments TO authenticated;
GRANT ALL ON public.project_review_comments TO service_role;
