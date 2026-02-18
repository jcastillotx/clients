-- ============================================================================
-- Migration 015: Add missing client domain/contact fields
-- Fixes PostgREST schema cache errors when inserting/updating clients with
-- `domain`, `industry`, and `primary_contact_id`.
-- Order: Run after 000_create_core_tables.sql
-- Created: 2026-02-18
-- ============================================================================

-- Add columns used by the clients UI/API payload
ALTER TABLE public.clients
  ADD COLUMN IF NOT EXISTS domain TEXT,
  ADD COLUMN IF NOT EXISTS industry TEXT,
  ADD COLUMN IF NOT EXISTS primary_contact_id UUID;

-- Add FK to users for the primary contact (nullable)
DO $$
BEGIN
  ALTER TABLE public.clients
    ADD CONSTRAINT fk_clients_primary_contact_id
    FOREIGN KEY (primary_contact_id)
    REFERENCES public.users(id)
    ON DELETE SET NULL;
EXCEPTION
  WHEN duplicate_object THEN
    NULL;
END $$;

-- Helpful indexes for filtering/search
CREATE INDEX IF NOT EXISTS idx_clients_domain ON public.clients(domain) WHERE deleted_at IS NULL;
CREATE INDEX IF NOT EXISTS idx_clients_industry ON public.clients(industry) WHERE deleted_at IS NULL;
CREATE INDEX IF NOT EXISTS idx_clients_primary_contact_id ON public.clients(primary_contact_id) WHERE deleted_at IS NULL;

COMMENT ON COLUMN public.clients.domain IS 'Primary website domain (e.g., example.com)';
COMMENT ON COLUMN public.clients.industry IS 'Client industry (free-form)';
COMMENT ON COLUMN public.clients.primary_contact_id IS 'Primary contact user id (public.users.id)';

