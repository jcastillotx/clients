-- Migration 013: add client logo URL for create/edit forms
ALTER TABLE public.clients
  ADD COLUMN IF NOT EXISTS logo_url TEXT;

CREATE INDEX IF NOT EXISTS idx_clients_logo_url ON public.clients(logo_url) WHERE deleted_at IS NULL;

COMMENT ON COLUMN public.clients.logo_url IS 'Public logo image URL used in client pages and listings';
