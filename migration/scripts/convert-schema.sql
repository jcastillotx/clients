-- Laravel to PostgreSQL Schema Conversion Script
-- Run after initial pgloader conversion to add Laravel-specific optimizations

-- ============================================================================
-- 1. CREATE ENUMS (Replace VARCHAR/ENUM Columns)
-- ============================================================================

-- User status enum
CREATE TYPE user_status AS ENUM ('active', 'inactive', 'suspended');

-- Client status enum
CREATE TYPE client_status AS ENUM ('active', 'inactive', 'pending', 'suspended');

-- Request status enum
CREATE TYPE request_status AS ENUM (
  'pending', 'in_progress', 'completed', 'cancelled',
  'on_hold', 'awaiting_approval', 'approved', 'rejected'
);

-- Invoice status enum
CREATE TYPE invoice_status AS ENUM (
  'draft', 'sent', 'paid', 'partial', 'overdue', 'cancelled', 'refunded'
);

-- Contract status enum
CREATE TYPE contract_status AS ENUM (
  'draft', 'pending_signature', 'signed', 'active', 'expired', 'terminated'
);

-- Document status enum
CREATE TYPE document_status AS ENUM (
  'draft', 'pending_review', 'approved', 'published', 'archived'
);

-- Payment method enum
CREATE TYPE payment_method AS ENUM (
  'credit_card', 'bank_transfer', 'paypal', 'check', 'cash', 'other'
);

-- ============================================================================
-- 2. ALTER TABLES TO USE ENUMS
-- ============================================================================

-- Users table
ALTER TABLE users
  ALTER COLUMN status TYPE user_status USING status::user_status;

-- Clients table
ALTER TABLE clients
  ALTER COLUMN status TYPE client_status USING status::client_status;

-- Requests table
ALTER TABLE requests
  ALTER COLUMN status TYPE request_status USING status::request_status;

-- Invoices table
ALTER TABLE invoices
  ALTER COLUMN status TYPE invoice_status USING status::invoice_status;

-- Contracts table
ALTER TABLE contracts
  ALTER COLUMN status TYPE contract_status USING status::contract_status;

-- Documents table
ALTER TABLE documents
  ALTER COLUMN status TYPE document_status USING status::document_status;

-- ============================================================================
-- 3. CONVERT JSON TO JSONB (Better Performance)
-- ============================================================================

-- Clients table
ALTER TABLE clients
  ALTER COLUMN enabled_features TYPE jsonb USING enabled_features::jsonb,
  ALTER COLUMN google_search_console_data TYPE jsonb USING google_search_console_data::jsonb,
  ALTER COLUMN marketing_strategy TYPE jsonb USING marketing_strategy::jsonb;

-- Users table
ALTER TABLE users
  ALTER COLUMN manual_permissions TYPE jsonb USING manual_permissions::jsonb,
  ALTER COLUMN security_settings TYPE jsonb USING security_settings::jsonb;

-- Requests table
ALTER TABLE requests
  ALTER COLUMN custom_fields TYPE jsonb USING custom_fields::jsonb;

-- Invoices table
ALTER TABLE invoices
  ALTER COLUMN items TYPE jsonb USING items::jsonb,
  ALTER COLUMN tax_details TYPE jsonb USING tax_details::jsonb;

-- AI providers table
ALTER TABLE ai_providers
  ALTER COLUMN config TYPE jsonb USING config::jsonb;

-- Automation rules table
ALTER TABLE automation_rules
  ALTER COLUMN conditions TYPE jsonb USING conditions::jsonb,
  ALTER COLUMN actions TYPE jsonb USING actions::jsonb;

-- ============================================================================
-- 4. CREATE GIN INDEXES FOR JSONB (Fast Queries)
-- ============================================================================

-- Clients
CREATE INDEX idx_clients_enabled_features ON clients USING gin(enabled_features);
CREATE INDEX idx_clients_gsc_data ON clients USING gin(google_search_console_data);

-- Users
CREATE INDEX idx_users_manual_permissions ON users USING gin(manual_permissions);

-- Requests
CREATE INDEX idx_requests_custom_fields ON requests USING gin(custom_fields);

-- Invoices
CREATE INDEX idx_invoices_items ON invoices USING gin(items);

-- Automation rules
CREATE INDEX idx_automation_rules_conditions ON automation_rules USING gin(conditions);
CREATE INDEX idx_automation_rules_actions ON automation_rules USING gin(actions);

-- ============================================================================
-- 5. CREATE FULL-TEXT SEARCH INDEXES
-- ============================================================================

-- SEO keywords (keyword column)
CREATE INDEX idx_seo_keywords_keyword_fts
  ON seo_keywords
  USING gin(to_tsvector('english', keyword));

-- Knowledge base articles (title + content)
CREATE INDEX idx_kb_articles_search_fts
  ON knowledge_base_articles
  USING gin(to_tsvector('english', title || ' ' || content));

-- Requests (title + description)
CREATE INDEX idx_requests_search_fts
  ON requests
  USING gin(to_tsvector('english', title || ' ' || COALESCE(description, '')));

-- ============================================================================
-- 6. CREATE COMPOSITE INDEXES (Common Queries)
-- ============================================================================

-- Activity logs (polymorphic relationships)
CREATE INDEX idx_activity_logs_subject ON activity_logs(subject_type, subject_id);
CREATE INDEX idx_activity_logs_causer ON activity_logs(causer_type, causer_id);

-- Documents (polymorphic relationships)
CREATE INDEX idx_documents_documentable ON documents(documentable_type, documentable_id);

-- Taggables (polymorphic relationships)
CREATE INDEX idx_taggables_taggable ON taggables(taggable_type, taggable_id);

-- Requests (common filters)
CREATE INDEX idx_requests_client_status ON requests(client_id, status);
CREATE INDEX idx_requests_assigned_to ON requests(assigned_to, status);
CREATE INDEX idx_requests_due_date ON requests(due_date) WHERE due_date IS NOT NULL;

-- Invoices (common filters)
CREATE INDEX idx_invoices_client_status ON invoices(client_id, status);
CREATE INDEX idx_invoices_due_date ON invoices(due_date) WHERE due_date IS NOT NULL;
CREATE INDEX idx_invoices_overdue ON invoices(status, due_date)
  WHERE status IN ('sent', 'partial') AND due_date < CURRENT_DATE;

-- Tasks (project + status)
CREATE INDEX idx_tasks_project_status ON tasks(project_id, status);

-- Messages (conversation + read status)
CREATE INDEX idx_messages_conversation_read ON messages(conversation_id, is_read);

-- ============================================================================
-- 7. ADD CONSTRAINTS FOR DATA INTEGRITY
-- ============================================================================

-- Ensure email uniqueness across users
CREATE UNIQUE INDEX idx_users_email_unique ON users(LOWER(email));

-- Ensure invoice numbers are unique
CREATE UNIQUE INDEX idx_invoices_number_unique ON invoices(invoice_number);

-- Ensure contract numbers are unique
CREATE UNIQUE INDEX idx_contracts_number_unique ON contracts(contract_number);

-- Positive amounts only
ALTER TABLE invoices
  ADD CONSTRAINT chk_invoices_amount_positive CHECK (amount > 0);

ALTER TABLE invoice_items
  ADD CONSTRAINT chk_invoice_items_amount_positive CHECK (amount > 0);

-- Valid date ranges
ALTER TABLE contracts
  ADD CONSTRAINT chk_contracts_valid_dates
  CHECK (start_date <= end_date);

ALTER TABLE projects
  ADD CONSTRAINT chk_projects_valid_dates
  CHECK (start_date <= end_date);

-- ============================================================================
-- 8. CREATE FUNCTIONS FOR COMMON OPERATIONS
-- ============================================================================

-- Function to get client_id from user
CREATE OR REPLACE FUNCTION get_user_client_id(p_user_id uuid)
RETURNS uuid AS $$
BEGIN
  RETURN (SELECT client_id FROM users WHERE id = p_user_id);
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- Function to check if user is staff
CREATE OR REPLACE FUNCTION is_staff_user(p_user_id uuid)
RETURNS boolean AS $$
BEGIN
  RETURN EXISTS (
    SELECT 1 FROM staff_assignments WHERE staff_user_id = p_user_id
  );
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- Function to check if user is super admin
CREATE OR REPLACE FUNCTION is_super_admin(p_user_id uuid)
RETURNS boolean AS $$
BEGIN
  RETURN EXISTS (
    SELECT 1 FROM users
    WHERE id = p_user_id
      AND is_super_admin = true
      AND is_active = true
  );
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- ============================================================================
-- 9. CREATE TRIGGERS FOR UPDATED_AT
-- ============================================================================

-- Function to update updated_at timestamp
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
  NEW.updated_at = NOW();
  RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- Apply to all tables with updated_at
CREATE TRIGGER update_users_updated_at BEFORE UPDATE ON users
  FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_clients_updated_at BEFORE UPDATE ON clients
  FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_requests_updated_at BEFORE UPDATE ON requests
  FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_invoices_updated_at BEFORE UPDATE ON invoices
  FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_contracts_updated_at BEFORE UPDATE ON contracts
  FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_documents_updated_at BEFORE UPDATE ON documents
  FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_projects_updated_at BEFORE UPDATE ON projects
  FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_tasks_updated_at BEFORE UPDATE ON tasks
  FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- ============================================================================
-- 10. PERFORMANCE OPTIMIZATIONS
-- ============================================================================

-- Analyze tables for query planner statistics
ANALYZE users;
ANALYZE clients;
ANALYZE requests;
ANALYZE invoices;
ANALYZE contracts;
ANALYZE documents;
ANALYZE projects;
ANALYZE tasks;
ANALYZE messages;
ANALYZE activity_logs;

-- Set autovacuum settings for high-traffic tables
ALTER TABLE activity_logs SET (autovacuum_vacuum_scale_factor = 0.01);
ALTER TABLE messages SET (autovacuum_vacuum_scale_factor = 0.05);

-- ============================================================================
-- VERIFICATION QUERIES
-- ============================================================================

-- Count rows in key tables (compare with MySQL)
SELECT
  'users' as table_name, COUNT(*) as row_count FROM users
UNION ALL
SELECT 'clients', COUNT(*) FROM clients
UNION ALL
SELECT 'requests', COUNT(*) FROM requests
UNION ALL
SELECT 'invoices', COUNT(*) FROM invoices
UNION ALL
SELECT 'contracts', COUNT(*) FROM contracts
UNION ALL
SELECT 'documents', COUNT(*) FROM documents;

-- Verify indexes created
SELECT
  schemaname,
  tablename,
  indexname,
  indexdef
FROM pg_indexes
WHERE schemaname = 'public'
ORDER BY tablename, indexname;

-- Check table sizes
SELECT
  schemaname,
  tablename,
  pg_size_pretty(pg_total_relation_size(schemaname||'.'||tablename)) as size
FROM pg_tables
WHERE schemaname = 'public'
ORDER BY pg_total_relation_size(schemaname||'.'||tablename) DESC
LIMIT 20;
