-- Migration: Add owner_type column to storage_connections
-- Distinguishes company-level connections (managed by admin) from
-- client-level connections (each client's own Dropbox/Drive/etc.).

ALTER TABLE storage_connections
  ADD COLUMN IF NOT EXISTS owner_type TEXT NOT NULL DEFAULT 'client'
    CHECK (owner_type IN ('company', 'client'));
