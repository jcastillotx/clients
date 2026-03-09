-- Migration: Create calendar_connections table
-- Stores per-user OAuth tokens for Google and Microsoft calendar integrations.
-- Access tokens and refresh tokens are stored AES-256-GCM encrypted (lib/encryption.ts).

CREATE TABLE IF NOT EXISTS calendar_connections (
  id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  user_id         UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  provider        TEXT NOT NULL CHECK (provider IN ('google', 'microsoft')),
  calendar_id     TEXT NOT NULL DEFAULT 'primary',
  calendar_name   TEXT,
  encrypted_access_token  TEXT NOT NULL,
  encrypted_refresh_token TEXT,
  token_expiry    TIMESTAMPTZ,
  is_active       BOOLEAN NOT NULL DEFAULT true,
  created_at      TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  updated_at      TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- One active connection per provider per user
CREATE UNIQUE INDEX IF NOT EXISTS calendar_connections_user_provider_idx
  ON calendar_connections (user_id, provider);

-- Row-Level Security: users can only see/manage their own connections
ALTER TABLE calendar_connections ENABLE ROW LEVEL SECURITY;

CREATE POLICY "Users can view their own calendar connections"
  ON calendar_connections FOR SELECT
  USING (user_id = auth.uid());

CREATE POLICY "Users can insert their own calendar connections"
  ON calendar_connections FOR INSERT
  WITH CHECK (user_id = auth.uid());

CREATE POLICY "Users can update their own calendar connections"
  ON calendar_connections FOR UPDATE
  USING (user_id = auth.uid());

CREATE POLICY "Users can delete their own calendar connections"
  ON calendar_connections FOR DELETE
  USING (user_id = auth.uid());

-- Service role bypass for server-side token refresh (no RLS restriction)
-- The service role key is only used server-side; it is never exposed to clients.
