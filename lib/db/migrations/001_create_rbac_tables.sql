-- Create roles table
CREATE TABLE IF NOT EXISTS roles (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  name TEXT NOT NULL UNIQUE,
  description TEXT,
  is_system BOOLEAN DEFAULT FALSE NOT NULL,
  created_at TIMESTAMPTZ DEFAULT NOW() NOT NULL,
  updated_at TIMESTAMPTZ DEFAULT NOW() NOT NULL
);

-- Create permissions table
CREATE TABLE IF NOT EXISTS permissions (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  name TEXT NOT NULL UNIQUE,
  description TEXT,
  resource TEXT NOT NULL,
  action TEXT NOT NULL,
  created_at TIMESTAMPTZ DEFAULT NOW() NOT NULL
);

-- Create role_permissions junction table
CREATE TABLE IF NOT EXISTS role_permissions (
  role_id UUID NOT NULL REFERENCES roles(id) ON DELETE CASCADE,
  permission_id UUID NOT NULL REFERENCES permissions(id) ON DELETE CASCADE,
  created_at TIMESTAMPTZ DEFAULT NOW() NOT NULL,
  PRIMARY KEY (role_id, permission_id)
);

-- Create user_roles junction table
CREATE TABLE IF NOT EXISTS user_roles (
  user_id UUID NOT NULL REFERENCES auth.users(id) ON DELETE CASCADE,
  role_id UUID NOT NULL REFERENCES roles(id) ON DELETE CASCADE,
  assigned_by UUID REFERENCES auth.users(id),
  created_at TIMESTAMPTZ DEFAULT NOW() NOT NULL,
  PRIMARY KEY (user_id, role_id)
);

-- Create indexes for performance
CREATE INDEX IF NOT EXISTS idx_user_roles_user_id ON user_roles(user_id);
CREATE INDEX IF NOT EXISTS idx_user_roles_role_id ON user_roles(role_id);
CREATE INDEX IF NOT EXISTS idx_role_permissions_role_id ON role_permissions(role_id);
CREATE INDEX IF NOT EXISTS idx_role_permissions_permission_id ON role_permissions(permission_id);

-- Insert system roles
INSERT INTO roles (name, description, is_system) VALUES
  ('super_admin', 'Super Administrator with full system access', TRUE),
  ('admin', 'Administrator with management capabilities', TRUE),
  ('account_manager', 'Manages client accounts and projects', TRUE),
  ('staff', 'Staff member with limited access', TRUE),
  ('client', 'Client user with read-only access to their data', TRUE)
ON CONFLICT (name) DO NOTHING;

-- Insert permissions for different resources
INSERT INTO permissions (name, description, resource, action) VALUES
  -- Client permissions
  ('clients.create', 'Create new clients', 'clients', 'create'),
  ('clients.read', 'View client information', 'clients', 'read'),
  ('clients.update', 'Update client information', 'clients', 'update'),
  ('clients.delete', 'Delete clients', 'clients', 'delete'),

  -- Invoice permissions
  ('invoices.create', 'Create new invoices', 'invoices', 'create'),
  ('invoices.read', 'View invoices', 'invoices', 'read'),
  ('invoices.update', 'Update invoices', 'invoices', 'update'),
  ('invoices.delete', 'Delete invoices', 'invoices', 'delete'),
  ('invoices.send', 'Send invoices to clients', 'invoices', 'send'),
  ('invoices.pay', 'Process invoice payments', 'invoices', 'pay'),

  -- Request permissions
  ('requests.create', 'Create new requests', 'requests', 'create'),
  ('requests.read', 'View requests', 'requests', 'read'),
  ('requests.update', 'Update requests', 'requests', 'update'),
  ('requests.delete', 'Delete requests', 'requests', 'delete'),
  ('requests.assign', 'Assign requests to staff', 'requests', 'assign'),

  -- User permissions
  ('users.create', 'Create new users', 'users', 'create'),
  ('users.read', 'View user information', 'users', 'read'),
  ('users.update', 'Update user information', 'users', 'update'),
  ('users.delete', 'Delete users', 'users', 'delete'),
  ('users.assign_roles', 'Assign roles to users', 'users', 'assign_roles'),

  -- Role permissions
  ('roles.create', 'Create new roles', 'roles', 'create'),
  ('roles.read', 'View roles', 'roles', 'read'),
  ('roles.update', 'Update roles', 'roles', 'update'),
  ('roles.delete', 'Delete roles', 'roles', 'delete'),

  -- Document permissions
  ('documents.create', 'Upload documents', 'documents', 'create'),
  ('documents.read', 'View documents', 'documents', 'read'),
  ('documents.update', 'Update documents', 'documents', 'update'),
  ('documents.delete', 'Delete documents', 'documents', 'delete'),

  -- Report permissions
  ('reports.financial', 'Access financial reports', 'reports', 'financial'),
  ('reports.analytics', 'Access analytics reports', 'reports', 'analytics'),
  ('reports.export', 'Export reports', 'reports', 'export'),

  -- Settings permissions
  ('settings.read', 'View system settings', 'settings', 'read'),
  ('settings.update', 'Update system settings', 'settings', 'update'),
  ('settings.manage', 'Full settings management access', 'settings', 'manage'),

  -- Contract permissions
  ('contracts.create', 'Create new contracts', 'contracts', 'create'),
  ('contracts.read', 'View contracts', 'contracts', 'read'),
  ('contracts.update', 'Update contracts', 'contracts', 'update'),
  ('contracts.delete', 'Delete contracts', 'contracts', 'delete'),

  -- User management aggregate permission
  ('users.manage', 'Full user management access', 'users', 'manage')
ON CONFLICT (name) DO NOTHING;

-- Assign permissions to super_admin role (all permissions)
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
CROSS JOIN permissions p
WHERE r.name = 'super_admin'
ON CONFLICT DO NOTHING;

-- Assign permissions to admin role (most permissions except system-critical ones)
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
CROSS JOIN permissions p
WHERE r.name = 'admin'
  AND p.name NOT IN ('settings.update', 'settings.manage', 'roles.delete')
ON CONFLICT DO NOTHING;

-- Assign permissions to account_manager role
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
CROSS JOIN permissions p
WHERE r.name = 'account_manager'
  AND p.name IN (
    'clients.create', 'clients.read', 'clients.update',
    'invoices.create', 'invoices.read', 'invoices.update', 'invoices.send',
    'requests.create', 'requests.read', 'requests.update', 'requests.assign',
    'documents.create', 'documents.read', 'documents.update',
    'contracts.create', 'contracts.read', 'contracts.update',
    'reports.financial', 'reports.analytics', 'reports.export'
  )
ON CONFLICT DO NOTHING;

-- Assign permissions to staff role
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
CROSS JOIN permissions p
WHERE r.name = 'staff'
  AND p.name IN (
    'clients.read',
    'invoices.read',
    'requests.read', 'requests.update',
    'documents.read',
    'contracts.read'
  )
ON CONFLICT DO NOTHING;

-- Assign permissions to client role (read-only for their own data)
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
CROSS JOIN permissions p
WHERE r.name = 'client'
  AND p.name IN (
    'clients.read',
    'invoices.read', 'invoices.pay',
    'requests.create', 'requests.read',
    'documents.read',
    'contracts.read'
  )
ON CONFLICT DO NOTHING;

-- Helper function to check if user has permission
CREATE OR REPLACE FUNCTION user_has_permission(p_user_id UUID, p_permission_name TEXT)
RETURNS BOOLEAN AS $$
BEGIN
  RETURN EXISTS (
    SELECT 1
    FROM user_roles ur
    JOIN role_permissions rp ON ur.role_id = rp.role_id
    JOIN permissions p ON rp.permission_id = p.id
    WHERE ur.user_id = p_user_id
      AND p.name = p_permission_name
  );
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- Helper function to check if user has role
CREATE OR REPLACE FUNCTION user_has_role(p_user_id UUID, p_role_name TEXT)
RETURNS BOOLEAN AS $$
BEGIN
  RETURN EXISTS (
    SELECT 1
    FROM user_roles ur
    JOIN roles r ON ur.role_id = r.id
    WHERE ur.user_id = p_user_id
      AND r.name = p_role_name
  );
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- Helper function to get user permissions
CREATE OR REPLACE FUNCTION get_user_permissions(p_user_id UUID)
RETURNS TABLE(permission_name TEXT, resource TEXT, action TEXT) AS $$
BEGIN
  RETURN QUERY
  SELECT DISTINCT p.name, p.resource, p.action
  FROM user_roles ur
  JOIN role_permissions rp ON ur.role_id = rp.role_id
  JOIN permissions p ON rp.permission_id = p.id
  WHERE ur.user_id = p_user_id;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- Helper function to get user roles
CREATE OR REPLACE FUNCTION get_user_roles(p_user_id UUID)
RETURNS TABLE(role_id UUID, role_name TEXT, role_description TEXT) AS $$
BEGIN
  RETURN QUERY
  SELECT r.id, r.name, r.description
  FROM user_roles ur
  JOIN roles r ON ur.role_id = r.id
  WHERE ur.user_id = p_user_id;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- Enable Row Level Security on RBAC tables
ALTER TABLE roles ENABLE ROW LEVEL SECURITY;
ALTER TABLE permissions ENABLE ROW LEVEL SECURITY;
ALTER TABLE role_permissions ENABLE ROW LEVEL SECURITY;
ALTER TABLE user_roles ENABLE ROW LEVEL SECURITY;

-- RLS Policies for roles table
CREATE POLICY "Anyone can view roles" ON roles FOR SELECT USING (true);
CREATE POLICY "Only admins can manage roles" ON roles FOR ALL
  USING (user_has_permission(auth.uid(), 'roles.update'));

-- RLS Policies for permissions table
CREATE POLICY "Anyone can view permissions" ON permissions FOR SELECT USING (true);

-- RLS Policies for role_permissions table
CREATE POLICY "Anyone can view role permissions" ON role_permissions FOR SELECT USING (true);
CREATE POLICY "Only admins can manage role permissions" ON role_permissions FOR ALL
  USING (user_has_permission(auth.uid(), 'roles.update'));

-- RLS Policies for user_roles table
CREATE POLICY "Users can view their own roles" ON user_roles FOR SELECT
  USING (user_id = auth.uid() OR user_has_permission(auth.uid(), 'users.read'));

CREATE POLICY "Only admins can assign roles" ON user_roles FOR INSERT
  WITH CHECK (user_has_permission(auth.uid(), 'users.assign_roles'));

CREATE POLICY "Only admins can remove roles" ON user_roles FOR DELETE
  USING (user_has_permission(auth.uid(), 'users.assign_roles'));
