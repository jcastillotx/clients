-- Create documents table
CREATE TABLE IF NOT EXISTS documents (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  name TEXT NOT NULL,
  description TEXT,
  file_name TEXT NOT NULL,
  file_size INTEGER NOT NULL,
  mime_type TEXT NOT NULL,
  storage_path TEXT NOT NULL,
  storage_url TEXT,
  client_id UUID NOT NULL,
  request_id UUID,
  uploaded_by UUID NOT NULL,
  version INTEGER DEFAULT 1 NOT NULL,
  parent_document_id UUID REFERENCES documents(id),
  is_latest_version BOOLEAN DEFAULT true NOT NULL,
  is_public BOOLEAN DEFAULT false NOT NULL,
  shared_with JSONB,
  tags JSONB,
  metadata JSONB,
  created_at TIMESTAMPTZ DEFAULT NOW(),
  updated_at TIMESTAMPTZ DEFAULT NOW(),
  deleted_at TIMESTAMPTZ
);

-- Create contracts table
CREATE TABLE IF NOT EXISTS contracts (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  title TEXT NOT NULL,
  description TEXT,
  contract_number TEXT NOT NULL UNIQUE,
  client_id UUID NOT NULL,
  type TEXT NOT NULL,
  status TEXT DEFAULT 'draft' NOT NULL,
  start_date TIMESTAMPTZ,
  end_date TIMESTAMPTZ,
  signed_date TIMESTAMPTZ,
  value INTEGER,
  currency TEXT DEFAULT 'USD',
  billing_cycle TEXT,
  document_id UUID REFERENCES documents(id),
  client_signed_by TEXT,
  client_signed_at TIMESTAMPTZ,
  company_signed_by UUID,
  company_signed_at TIMESTAMPTZ,
  terms JSONB,
  auto_renew BOOLEAN DEFAULT false,
  notice_required INTEGER,
  tags JSONB,
  custom_fields JSONB,
  created_at TIMESTAMPTZ DEFAULT NOW(),
  updated_at TIMESTAMPTZ DEFAULT NOW(),
  deleted_at TIMESTAMPTZ
);

-- Create document_shares table
CREATE TABLE IF NOT EXISTS document_shares (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  document_id UUID NOT NULL REFERENCES documents(id) ON DELETE CASCADE,
  shared_with_user_id UUID,
  shared_with_email TEXT,
  can_view BOOLEAN DEFAULT true NOT NULL,
  can_download BOOLEAN DEFAULT true NOT NULL,
  can_edit BOOLEAN DEFAULT false NOT NULL,
  expires_at TIMESTAMPTZ,
  shared_by UUID NOT NULL,
  last_accessed_at TIMESTAMPTZ,
  access_count INTEGER DEFAULT 0,
  created_at TIMESTAMPTZ DEFAULT NOW(),
  updated_at TIMESTAMPTZ DEFAULT NOW()
);

-- Create indexes for performance
CREATE INDEX IF NOT EXISTS idx_documents_client_id ON documents(client_id) WHERE deleted_at IS NULL;
CREATE INDEX IF NOT EXISTS idx_documents_request_id ON documents(request_id) WHERE deleted_at IS NULL;
CREATE INDEX IF NOT EXISTS idx_documents_uploaded_by ON documents(uploaded_by) WHERE deleted_at IS NULL;
CREATE INDEX IF NOT EXISTS idx_documents_parent_id ON documents(parent_document_id) WHERE deleted_at IS NULL;
CREATE INDEX IF NOT EXISTS idx_documents_is_latest ON documents(is_latest_version) WHERE is_latest_version = true AND deleted_at IS NULL;
CREATE INDEX IF NOT EXISTS idx_documents_created_at ON documents(created_at DESC) WHERE deleted_at IS NULL;

CREATE INDEX IF NOT EXISTS idx_contracts_client_id ON contracts(client_id) WHERE deleted_at IS NULL;
CREATE INDEX IF NOT EXISTS idx_contracts_status ON contracts(status) WHERE deleted_at IS NULL;
CREATE INDEX IF NOT EXISTS idx_contracts_type ON contracts(type) WHERE deleted_at IS NULL;
CREATE INDEX IF NOT EXISTS idx_contracts_end_date ON contracts(end_date) WHERE deleted_at IS NULL;

CREATE INDEX IF NOT EXISTS idx_document_shares_document_id ON document_shares(document_id);
CREATE INDEX IF NOT EXISTS idx_document_shares_user_id ON document_shares(shared_with_user_id);
CREATE INDEX IF NOT EXISTS idx_document_shares_email ON document_shares(shared_with_email);
CREATE INDEX IF NOT EXISTS idx_document_shares_expires ON document_shares(expires_at);

-- Enable RLS
ALTER TABLE documents ENABLE ROW LEVEL SECURITY;
ALTER TABLE contracts ENABLE ROW LEVEL SECURITY;
ALTER TABLE document_shares ENABLE ROW LEVEL SECURITY;

-- Documents RLS Policies (FIXED - removed staff_assignments references)
CREATE POLICY "Users can view documents for their client" ON documents
  FOR SELECT
  USING (
    -- User's own client documents
    client_id = (SELECT client_id FROM users WHERE id = auth.uid())
    OR
    -- Documents shared with the user
    id IN (
      SELECT document_id FROM document_shares
      WHERE shared_with_user_id = auth.uid()
      AND (expires_at IS NULL OR expires_at > NOW())
    )
    OR
    -- Admins can see all
    EXISTS (
      SELECT 1 FROM user_roles ur
      JOIN roles r ON ur.role_id = r.id
      WHERE ur.user_id = auth.uid() AND r.name IN ('super_admin', 'admin')
    )
  );

CREATE POLICY "Users can upload documents for their client" ON documents
  FOR INSERT
  WITH CHECK (
    client_id = (SELECT client_id FROM users WHERE id = auth.uid())
    OR
    EXISTS (
      SELECT 1 FROM user_roles ur
      JOIN roles r ON ur.role_id = r.id
      WHERE ur.user_id = auth.uid() AND r.name IN ('super_admin', 'admin', 'account_manager')
    )
  );

CREATE POLICY "Users can update their own documents" ON documents
  FOR UPDATE
  USING (
    uploaded_by = auth.uid()
    OR
    client_id = (SELECT client_id FROM users WHERE id = auth.uid())
    OR
    EXISTS (
      SELECT 1 FROM user_roles ur
      JOIN roles r ON ur.role_id = r.id
      WHERE ur.user_id = auth.uid() AND r.name IN ('super_admin', 'admin')
    )
  );

CREATE POLICY "Users can delete their own documents" ON documents
  FOR DELETE
  USING (
    uploaded_by = auth.uid()
    OR
    EXISTS (
      SELECT 1 FROM user_roles ur
      JOIN roles r ON ur.role_id = r.id
      WHERE ur.user_id = auth.uid() AND r.name IN ('super_admin', 'admin')
    )
  );

-- Contracts RLS Policies (FIXED - removed staff_assignments references)
CREATE POLICY "Users can view contracts for their client" ON contracts
  FOR SELECT
  USING (
    client_id = (SELECT client_id FROM users WHERE id = auth.uid())
    OR
    EXISTS (
      SELECT 1 FROM user_roles ur
      JOIN roles r ON ur.role_id = r.id
      WHERE ur.user_id = auth.uid() AND r.name IN ('super_admin', 'admin', 'account_manager')
    )
  );

CREATE POLICY "Staff can manage contracts" ON contracts
  FOR ALL
  USING (
    client_id = (SELECT client_id FROM users WHERE id = auth.uid())
    OR
    EXISTS (
      SELECT 1 FROM user_roles ur
      JOIN roles r ON ur.role_id = r.id
      WHERE ur.user_id = auth.uid() AND r.name IN ('super_admin', 'admin', 'account_manager')
    )
  );

-- Document Shares RLS Policies
CREATE POLICY "Users can view their document shares" ON document_shares
  FOR SELECT
  USING (
    shared_with_user_id = auth.uid()
    OR
    shared_by = auth.uid()
    OR
    EXISTS (
      SELECT 1 FROM user_roles ur
      JOIN roles r ON ur.role_id = r.id
      WHERE ur.user_id = auth.uid() AND r.name IN ('super_admin', 'admin')
    )
  );

CREATE POLICY "Document owners can create shares" ON document_shares
  FOR INSERT
  WITH CHECK (
    EXISTS (
      SELECT 1 FROM documents d
      WHERE d.id = document_id AND d.uploaded_by = auth.uid()
    )
    OR
    EXISTS (
      SELECT 1 FROM user_roles ur
      JOIN roles r ON ur.role_id = r.id
      WHERE ur.user_id = auth.uid() AND r.name IN ('super_admin', 'admin')
    )
  );

CREATE POLICY "Share creators can delete their shares" ON document_shares
  FOR DELETE
  USING (
    shared_by = auth.uid()
    OR
    EXISTS (
      SELECT 1 FROM user_roles ur
      JOIN roles r ON ur.role_id = r.id
      WHERE ur.user_id = auth.uid() AND r.name IN ('super_admin', 'admin')
    )
  );

-- Create or replace update trigger function
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
  NEW.updated_at = NOW();
  RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- Add updated_at triggers
DROP TRIGGER IF EXISTS update_documents_updated_at ON documents;
CREATE TRIGGER update_documents_updated_at
  BEFORE UPDATE ON documents
  FOR EACH ROW
  EXECUTE FUNCTION update_updated_at_column();

DROP TRIGGER IF EXISTS update_contracts_updated_at ON contracts;
CREATE TRIGGER update_contracts_updated_at
  BEFORE UPDATE ON contracts
  FOR EACH ROW
  EXECUTE FUNCTION update_updated_at_column();

DROP TRIGGER IF EXISTS update_document_shares_updated_at ON document_shares;
CREATE TRIGGER update_document_shares_updated_at
  BEFORE UPDATE ON document_shares
  FOR EACH ROW
  EXECUTE FUNCTION update_updated_at_column();

-- Function to generate contract number
CREATE OR REPLACE FUNCTION generate_contract_number()
RETURNS TEXT AS $$
DECLARE
  new_number TEXT;
  year TEXT;
  sequence INTEGER;
BEGIN
  year := TO_CHAR(NOW(), 'YYYY');

  SELECT COALESCE(MAX(CAST(SUBSTRING(contract_number FROM 'CON-' || year || '-(\d+)') AS INTEGER)), 0) + 1
  INTO sequence
  FROM contracts
  WHERE contract_number LIKE 'CON-' || year || '-%';

  new_number := 'CON-' || year || '-' || LPAD(sequence::TEXT, 4, '0');

  RETURN new_number;
END;
$$ LANGUAGE plpgsql;

-- Function to handle document versioning
CREATE OR REPLACE FUNCTION handle_document_version()
RETURNS TRIGGER AS $$
BEGIN
  -- If this is a new version of an existing document
  IF NEW.parent_document_id IS NOT NULL THEN
    -- Mark all previous versions as not latest
    UPDATE documents
    SET is_latest_version = false
    WHERE (id = NEW.parent_document_id OR parent_document_id = NEW.parent_document_id)
    AND id != NEW.id;

    -- Set the new version number
    SELECT COALESCE(MAX(version), 0) + 1
    INTO NEW.version
    FROM documents
    WHERE id = NEW.parent_document_id OR parent_document_id = NEW.parent_document_id;
  END IF;

  RETURN NEW;
END;
$$ LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS document_versioning ON documents;
CREATE TRIGGER document_versioning
  BEFORE INSERT ON documents
  FOR EACH ROW
  EXECUTE FUNCTION handle_document_version();

-- Grant permissions
GRANT ALL ON documents TO authenticated;
GRANT ALL ON contracts TO authenticated;
GRANT ALL ON document_shares TO authenticated;
GRANT ALL ON documents TO service_role;
GRANT ALL ON contracts TO service_role;
GRANT ALL ON document_shares TO service_role;
