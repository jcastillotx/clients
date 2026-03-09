-- Migration: Create system_settings table
-- Stores admin-configured platform-level settings (SMTP, etc.).
-- Sensitive values are AES-256-GCM encrypted (lib/encryption.ts).

CREATE TABLE IF NOT EXISTS system_settings (
  id           UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  category     TEXT NOT NULL,           -- e.g. 'email'
  key          TEXT NOT NULL,           -- e.g. 'provider', 'host', 'password'
  value        TEXT NOT NULL,           -- plaintext or encrypted value
  is_encrypted BOOLEAN NOT NULL DEFAULT false,
  updated_by   UUID REFERENCES users(id) ON DELETE SET NULL,
  created_at   TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  updated_at   TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE UNIQUE INDEX IF NOT EXISTS system_settings_category_key_idx
  ON system_settings (category, key);

-- Only service role (server-side) has access — no RLS policy needed,
-- but enable RLS so anon/authenticated roles cannot access it at all.
ALTER TABLE system_settings ENABLE ROW LEVEL SECURITY;

-- No SELECT/INSERT/UPDATE/DELETE policies — only the service role bypasses RLS.
