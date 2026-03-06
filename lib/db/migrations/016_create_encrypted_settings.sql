-- Migration: Create encrypted_settings table
-- Stores AES-256-GCM encrypted API keys, OAuth credentials, and integration settings

CREATE TABLE IF NOT EXISTS encrypted_settings (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  client_id UUID NOT NULL REFERENCES clients(id) ON DELETE CASCADE,
  provider TEXT NOT NULL,
  category TEXT NOT NULL,
  setting_key TEXT NOT NULL,
  encrypted_value TEXT NOT NULL,
  is_active BOOLEAN NOT NULL DEFAULT true,
  label TEXT,
  last_rotated_at TIMESTAMPTZ,
  last_verified_at TIMESTAMPTZ,
  updated_by UUID REFERENCES users(id),
  created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
  updated_at TIMESTAMPTZ NOT NULL DEFAULT now()
);

-- Unique constraint: one value per client + provider + key combination
CREATE UNIQUE INDEX IF NOT EXISTS encrypted_settings_client_provider_key
  ON encrypted_settings (client_id, provider, setting_key);

-- Index for quick lookups by client
CREATE INDEX IF NOT EXISTS idx_encrypted_settings_client_id
  ON encrypted_settings (client_id);

-- Index for provider filtering
CREATE INDEX IF NOT EXISTS idx_encrypted_settings_provider
  ON encrypted_settings (client_id, provider);

-- Index for category filtering
CREATE INDEX IF NOT EXISTS idx_encrypted_settings_category
  ON encrypted_settings (client_id, category);

-- RLS policies
ALTER TABLE encrypted_settings ENABLE ROW LEVEL SECURITY;

-- Users can only view/manage their own client's settings
CREATE POLICY "Users can view own client encrypted settings"
  ON encrypted_settings
  FOR SELECT
  USING (
    client_id IN (
      SELECT client_id FROM users WHERE id = auth.uid()
    )
  );

CREATE POLICY "Users can insert own client encrypted settings"
  ON encrypted_settings
  FOR INSERT
  WITH CHECK (
    client_id IN (
      SELECT client_id FROM users WHERE id = auth.uid()
    )
  );

CREATE POLICY "Users can update own client encrypted settings"
  ON encrypted_settings
  FOR UPDATE
  USING (
    client_id IN (
      SELECT client_id FROM users WHERE id = auth.uid()
    )
  );

CREATE POLICY "Users can delete own client encrypted settings"
  ON encrypted_settings
  FOR DELETE
  USING (
    client_id IN (
      SELECT client_id FROM users WHERE id = auth.uid()
    )
  );

-- Comment
COMMENT ON TABLE encrypted_settings IS 'Stores encrypted API keys and OAuth credentials for third-party integrations (AES-256-GCM encrypted)';
COMMENT ON COLUMN encrypted_settings.encrypted_value IS 'AES-256-GCM encrypted value with salt+iv+authTag prepended';
COMMENT ON COLUMN encrypted_settings.provider IS 'Integration provider (e.g., anthropic, stripe, zapier, google_analytics)';
COMMENT ON COLUMN encrypted_settings.category IS 'Provider category (ai, payments, email, social, analytics, automation, storage)';
