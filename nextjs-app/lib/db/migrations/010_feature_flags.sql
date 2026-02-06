-- Feature Flags System Migration
-- Enables granular feature control at User, Role, and Client levels

-- Create features table
CREATE TABLE IF NOT EXISTS features (
  id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
  name TEXT NOT NULL UNIQUE,
  display_name TEXT NOT NULL,
  description TEXT,
  category TEXT NOT NULL,
  is_enabled_by_default BOOLEAN DEFAULT true,
  requires_setup BOOLEAN DEFAULT false,
  setup_instructions TEXT,
  dependencies JSONB,
  metadata JSONB,
  created_at TIMESTAMPTZ DEFAULT NOW(),
  updated_at TIMESTAMPTZ DEFAULT NOW()
);

-- Create client_features table
CREATE TABLE IF NOT EXISTS client_features (
  id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
  client_id UUID NOT NULL REFERENCES clients(id) ON DELETE CASCADE,
  feature_id UUID NOT NULL REFERENCES features(id) ON DELETE CASCADE,
  is_enabled BOOLEAN NOT NULL DEFAULT true,
  config JSONB,
  enabled_at TIMESTAMPTZ,
  enabled_by UUID REFERENCES users(id),
  disabled_at TIMESTAMPTZ,
  disabled_by UUID REFERENCES users(id),
  notes TEXT,
  created_at TIMESTAMPTZ DEFAULT NOW(),
  updated_at TIMESTAMPTZ DEFAULT NOW(),
  CONSTRAINT unique_client_feature UNIQUE (client_id, feature_id)
);

-- Create role_features table
CREATE TABLE IF NOT EXISTS role_features (
  id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
  role_id UUID NOT NULL REFERENCES roles(id) ON DELETE CASCADE,
  feature_id UUID NOT NULL REFERENCES features(id) ON DELETE CASCADE,
  is_enabled BOOLEAN NOT NULL DEFAULT true,
  created_at TIMESTAMPTZ DEFAULT NOW(),
  updated_at TIMESTAMPTZ DEFAULT NOW(),
  CONSTRAINT unique_role_feature UNIQUE (role_id, feature_id)
);

-- Create user_features table
CREATE TABLE IF NOT EXISTS user_features (
  id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
  user_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  feature_id UUID NOT NULL REFERENCES features(id) ON DELETE CASCADE,
  is_enabled BOOLEAN NOT NULL DEFAULT true,
  created_at TIMESTAMPTZ DEFAULT NOW(),
  updated_at TIMESTAMPTZ DEFAULT NOW(),
  CONSTRAINT unique_user_feature UNIQUE (user_id, feature_id)
);

-- Create indexes for performance
CREATE INDEX idx_client_features_client_id ON client_features(client_id);
CREATE INDEX idx_client_features_feature_id ON client_features(feature_id);
CREATE INDEX idx_role_features_role_id ON role_features(role_id);
CREATE INDEX idx_role_features_feature_id ON role_features(feature_id);
CREATE INDEX idx_user_features_user_id ON user_features(user_id);
CREATE INDEX idx_user_features_feature_id ON user_features(feature_id);
CREATE INDEX idx_features_category ON features(category);
CREATE INDEX idx_features_name ON features(name);

-- Add RLS policies
ALTER TABLE features ENABLE ROW LEVEL SECURITY;
ALTER TABLE client_features ENABLE ROW LEVEL SECURITY;
ALTER TABLE role_features ENABLE ROW LEVEL SECURITY;
ALTER TABLE user_features ENABLE ROW LEVEL SECURITY;

-- Admin can see all features
CREATE POLICY admin_all_features ON features
  FOR ALL
  USING (
    EXISTS (
      SELECT 1 FROM users
      WHERE users.id = auth.uid() AND users.is_super_admin = true
    )
  );

-- Users can see features
CREATE POLICY users_read_features ON features
  FOR SELECT
  USING (auth.uid() IS NOT NULL);

-- Admins can manage client features
CREATE POLICY admin_client_features ON client_features
  FOR ALL
  USING (
    EXISTS (
      SELECT 1 FROM users
      WHERE users.id = auth.uid() AND users.is_super_admin = true
    )
  );

-- Users can see their client's features
CREATE POLICY users_read_client_features ON client_features
  FOR SELECT
  USING (
    client_id IN (
      SELECT client_id FROM users WHERE id = auth.uid()
    )
  );
