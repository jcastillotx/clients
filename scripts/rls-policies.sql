-- Row-Level Security (RLS) Policies for Multi-Tenant Isolation
-- Replaces Laravel middleware authorization with database-level security

-- ============================================================================
-- 1. ENABLE RLS ON ALL CLIENT-SCOPED TABLES
-- ============================================================================

-- Core tables
ALTER TABLE clients ENABLE ROW LEVEL SECURITY;
ALTER TABLE users ENABLE ROW LEVEL SECURITY;
ALTER TABLE requests ENABLE ROW LEVEL SECURITY;
ALTER TABLE invoices ENABLE ROW LEVEL SECURITY;
ALTER TABLE contracts ENABLE ROW LEVEL SECURITY;
ALTER TABLE documents ENABLE ROW LEVEL SECURITY;
ALTER TABLE projects ENABLE ROW LEVEL SECURITY;
ALTER TABLE tasks ENABLE ROW LEVEL SECURITY;

-- Relationship tables
ALTER TABLE request_comments ENABLE ROW LEVEL SECURITY;
ALTER TABLE invoice_items ENABLE ROW LEVEL SECURITY;
ALTER TABLE project_members ENABLE ROW LEVEL SECURITY;
ALTER TABLE task_assignments ENABLE ROW LEVEL SECURITY;
ALTER TABLE conversations ENABLE ROW LEVEL SECURITY;
ALTER TABLE messages ENABLE ROW LEVEL SECURITY;
ALTER TABLE activity_logs ENABLE ROW LEVEL SECURITY;

-- Additional tables
ALTER TABLE client_contacts ENABLE ROW LEVEL SECURITY;
ALTER TABLE client_notes ENABLE ROW LEVEL SECURITY;
ALTER TABLE seo_keywords ENABLE ROW LEVEL SECURITY;
ALTER TABLE seo_recommendations ENABLE ROW LEVEL SECURITY;
ALTER TABLE website_audits ENABLE ROW LEVEL SECURITY;

-- ============================================================================
-- 2. HELPER FUNCTIONS FOR RLS POLICIES
-- ============================================================================

-- Get current user's ID
CREATE OR REPLACE FUNCTION auth.user_id()
RETURNS uuid AS $$
  SELECT auth.uid();
$$ LANGUAGE sql STABLE;

-- Get current user's client_id
CREATE OR REPLACE FUNCTION auth.user_client_id()
RETURNS uuid AS $$
  SELECT client_id FROM users WHERE id = auth.uid();
$$ LANGUAGE sql STABLE SECURITY DEFINER;

-- Check if user is super admin
CREATE OR REPLACE FUNCTION auth.is_super_admin()
RETURNS boolean AS $$
  SELECT EXISTS (
    SELECT 1 FROM users
    WHERE id = auth.uid()
      AND is_super_admin = true
      AND is_active = true
  );
$$ LANGUAGE sql STABLE SECURITY DEFINER;

-- Check if user is staff
CREATE OR REPLACE FUNCTION auth.is_staff()
RETURNS boolean AS $$
  SELECT EXISTS (
    SELECT 1 FROM staff_assignments
    WHERE staff_user_id = auth.uid()
  );
$$ LANGUAGE sql STABLE SECURITY DEFINER;

-- Get user's assigned client IDs (for staff)
CREATE OR REPLACE FUNCTION auth.user_assigned_client_ids()
RETURNS TABLE(client_id uuid) AS $$
  SELECT client_id FROM staff_assignments WHERE staff_user_id = auth.uid();
$$ LANGUAGE sql STABLE SECURITY DEFINER;

-- ============================================================================
-- 3. CLIENTS TABLE POLICIES
-- ============================================================================

-- Super admins can see all clients
CREATE POLICY super_admin_all_clients ON clients
  FOR ALL
  USING (auth.is_super_admin());

-- Users can see their own client
CREATE POLICY own_client ON clients
  FOR SELECT
  USING (id = auth.user_client_id());

-- Staff can see assigned clients
CREATE POLICY staff_assigned_clients ON clients
  FOR SELECT
  USING (
    id IN (SELECT client_id FROM auth.user_assigned_client_ids())
  );

-- ============================================================================
-- 4. USERS TABLE POLICIES
-- ============================================================================

-- Super admins can see all users
CREATE POLICY super_admin_all_users ON users
  FOR ALL
  USING (auth.is_super_admin());

-- Users can see themselves
CREATE POLICY own_user ON users
  FOR SELECT
  USING (id = auth.uid());

-- Users can update themselves (limited fields via application logic)
CREATE POLICY update_own_user ON users
  FOR UPDATE
  USING (id = auth.uid());

-- Users can see users from their client
CREATE POLICY same_client_users ON users
  FOR SELECT
  USING (client_id = auth.user_client_id());

-- Staff can see users from assigned clients
CREATE POLICY staff_assigned_client_users ON users
  FOR SELECT
  USING (
    client_id IN (SELECT client_id FROM auth.user_assigned_client_ids())
  );

-- ============================================================================
-- 5. REQUESTS TABLE POLICIES
-- ============================================================================

-- Super admins can see all requests
CREATE POLICY super_admin_all_requests ON requests
  FOR ALL
  USING (auth.is_super_admin());

-- Users can see requests from their client
CREATE POLICY own_client_requests ON requests
  FOR SELECT
  USING (client_id = auth.user_client_id());

-- Staff can see requests from assigned clients
CREATE POLICY staff_assigned_client_requests ON requests
  FOR SELECT
  USING (
    client_id IN (SELECT client_id FROM auth.user_assigned_client_ids())
  );

-- Users with 'manage_requests' permission can create requests
CREATE POLICY create_requests ON requests
  FOR INSERT
  WITH CHECK (
    client_id = auth.user_client_id()
    AND auth.user_has_permission('manage_requests')
  );

-- Request creator or assigned user can update
CREATE POLICY update_own_requests ON requests
  FOR UPDATE
  USING (
    client_id = auth.user_client_id()
    AND (created_by = auth.uid() OR assigned_to = auth.uid())
  );

-- ============================================================================
-- 6. INVOICES TABLE POLICIES
-- ============================================================================

-- Super admins can see all invoices
CREATE POLICY super_admin_all_invoices ON invoices
  FOR ALL
  USING (auth.is_super_admin());

-- Users can see invoices for their client
CREATE POLICY own_client_invoices ON invoices
  FOR SELECT
  USING (client_id = auth.user_client_id());

-- Staff can see invoices for assigned clients
CREATE POLICY staff_assigned_client_invoices ON invoices
  FOR SELECT
  USING (
    client_id IN (SELECT client_id FROM auth.user_assigned_client_ids())
  );

-- Only staff with 'manage_invoices' permission can create/update
CREATE POLICY staff_manage_invoices ON invoices
  FOR ALL
  USING (
    auth.is_staff()
    AND client_id IN (SELECT client_id FROM auth.user_assigned_client_ids())
    AND auth.user_has_permission('manage_invoices')
  );

-- ============================================================================
-- 7. CONTRACTS TABLE POLICIES
-- ============================================================================

-- Super admins can see all contracts
CREATE POLICY super_admin_all_contracts ON contracts
  FOR ALL
  USING (auth.is_super_admin());

-- Users can see contracts for their client
CREATE POLICY own_client_contracts ON contracts
  FOR SELECT
  USING (client_id = auth.user_client_id());

-- Staff can see contracts for assigned clients
CREATE POLICY staff_assigned_client_contracts ON contracts
  FOR SELECT
  USING (
    client_id IN (SELECT client_id FROM auth.user_assigned_client_ids())
  );

-- Only staff with 'manage_contracts' permission can create/update
CREATE POLICY staff_manage_contracts ON contracts
  FOR ALL
  USING (
    auth.is_staff()
    AND client_id IN (SELECT client_id FROM auth.user_assigned_client_ids())
    AND auth.user_has_permission('manage_contracts')
  );

-- ============================================================================
-- 8. DOCUMENTS TABLE POLICIES
-- ============================================================================

-- Super admins can see all documents
CREATE POLICY super_admin_all_documents ON documents
  FOR ALL
  USING (auth.is_super_admin());

-- Users can see documents for their client
CREATE POLICY own_client_documents ON documents
  FOR SELECT
  USING (client_id = auth.user_client_id());

-- Staff can see documents for assigned clients
CREATE POLICY staff_assigned_client_documents ON documents
  FOR SELECT
  USING (
    client_id IN (SELECT client_id FROM auth.user_assigned_client_ids())
  );

-- Document uploader can update/delete
CREATE POLICY manage_own_documents ON documents
  FOR ALL
  USING (
    client_id = auth.user_client_id()
    AND uploaded_by = auth.uid()
  );

-- ============================================================================
-- 9. PROJECTS TABLE POLICIES
-- ============================================================================

-- Super admins can see all projects
CREATE POLICY super_admin_all_projects ON projects
  FOR ALL
  USING (auth.is_super_admin());

-- Users can see projects for their client
CREATE POLICY own_client_projects ON projects
  FOR SELECT
  USING (client_id = auth.user_client_id());

-- Staff can see projects for assigned clients
CREATE POLICY staff_assigned_client_projects ON projects
  FOR SELECT
  USING (
    client_id IN (SELECT client_id FROM auth.user_assigned_client_ids())
  );

-- Project members can update
CREATE POLICY project_members_update ON projects
  FOR UPDATE
  USING (
    id IN (
      SELECT project_id FROM project_members WHERE user_id = auth.uid()
    )
  );

-- ============================================================================
-- 10. TASKS TABLE POLICIES
-- ============================================================================

-- Super admins can see all tasks
CREATE POLICY super_admin_all_tasks ON tasks
  FOR ALL
  USING (auth.is_super_admin());

-- Users can see tasks from projects they're members of
CREATE POLICY project_member_tasks ON tasks
  FOR SELECT
  USING (
    project_id IN (
      SELECT project_id FROM project_members WHERE user_id = auth.uid()
    )
  );

-- Users can see tasks assigned to them
CREATE POLICY assigned_tasks ON tasks
  FOR SELECT
  USING (
    id IN (
      SELECT task_id FROM task_assignments WHERE user_id = auth.uid()
    )
  );

-- Task assignees can update
CREATE POLICY update_assigned_tasks ON tasks
  FOR UPDATE
  USING (
    id IN (
      SELECT task_id FROM task_assignments WHERE user_id = auth.uid()
    )
  );

-- ============================================================================
-- 11. REQUEST COMMENTS TABLE POLICIES
-- ============================================================================

-- Super admins can see all comments
CREATE POLICY super_admin_all_comments ON request_comments
  FOR ALL
  USING (auth.is_super_admin());

-- Users can see comments on requests they can see
CREATE POLICY see_request_comments ON request_comments
  FOR SELECT
  USING (
    request_id IN (
      SELECT id FROM requests
      WHERE client_id = auth.user_client_id()
        OR client_id IN (SELECT client_id FROM auth.user_assigned_client_ids())
    )
  );

-- Users can create comments on requests they can see
CREATE POLICY create_request_comments ON request_comments
  FOR INSERT
  WITH CHECK (
    request_id IN (
      SELECT id FROM requests WHERE client_id = auth.user_client_id()
    )
    AND user_id = auth.uid()
  );

-- Comment author can update/delete
CREATE POLICY manage_own_comments ON request_comments
  FOR ALL
  USING (user_id = auth.uid());

-- ============================================================================
-- 12. MESSAGES TABLE POLICIES (Real-time Chat)
-- ============================================================================

-- Super admins can see all messages
CREATE POLICY super_admin_all_messages ON messages
  FOR ALL
  USING (auth.is_super_admin());

-- Users can see messages in conversations they're part of
CREATE POLICY conversation_participant_messages ON messages
  FOR SELECT
  USING (
    conversation_id IN (
      SELECT conversation_id FROM conversation_participants
      WHERE user_id = auth.uid()
    )
  );

-- Users can create messages in their conversations
CREATE POLICY create_conversation_messages ON messages
  FOR INSERT
  WITH CHECK (
    conversation_id IN (
      SELECT conversation_id FROM conversation_participants
      WHERE user_id = auth.uid()
    )
    AND sender_id = auth.uid()
  );

-- Users can update read status of messages sent to them
CREATE POLICY update_message_read_status ON messages
  FOR UPDATE
  USING (
    conversation_id IN (
      SELECT conversation_id FROM conversation_participants
      WHERE user_id = auth.uid()
    )
  );

-- ============================================================================
-- 13. ACTIVITY LOGS TABLE POLICIES (Audit Trail)
-- ============================================================================

-- Super admins can see all activity logs
CREATE POLICY super_admin_all_activity_logs ON activity_logs
  FOR ALL
  USING (auth.is_super_admin());

-- Users can see activity logs related to their client
CREATE POLICY own_client_activity_logs ON activity_logs
  FOR SELECT
  USING (
    -- Activity on client records
    (subject_type = 'clients' AND subject_id::text = auth.user_client_id()::text)
    -- Activity by users in their client
    OR (causer_type = 'users' AND causer_id IN (
      SELECT id FROM users WHERE client_id = auth.user_client_id()
    ))
  );

-- Staff can see activity logs for assigned clients
CREATE POLICY staff_activity_logs ON activity_logs
  FOR SELECT
  USING (
    subject_id::text IN (
      SELECT client_id::text FROM auth.user_assigned_client_ids()
    )
  );

-- ============================================================================
-- 14. PERMISSION FUNCTIONS FOR APPLICATION LOGIC
-- ============================================================================

-- Check if user has specific permission
CREATE OR REPLACE FUNCTION auth.user_has_permission(p_permission_name text)
RETURNS boolean AS $$
BEGIN
  -- Super admins have all permissions
  IF auth.is_super_admin() THEN
    RETURN true;
  END IF;

  -- Check role-based permissions
  RETURN EXISTS (
    SELECT 1
    FROM user_roles ur
    JOIN role_permissions rp ON ur.role_id = rp.role_id
    JOIN permissions p ON rp.permission_id = p.id
    WHERE ur.user_id = auth.uid()
      AND p.name = p_permission_name
  )
  -- Check manual permissions
  OR EXISTS (
    SELECT 1
    FROM users
    WHERE id = auth.uid()
      AND manual_permissions ? p_permission_name
  );
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- ============================================================================
-- VERIFICATION QUERIES
-- ============================================================================

-- List all RLS policies
SELECT
  schemaname,
  tablename,
  policyname,
  permissive,
  roles,
  cmd,
  qual,
  with_check
FROM pg_policies
WHERE schemaname = 'public'
ORDER BY tablename, policyname;

-- Test RLS as a regular user (run after setting auth.uid())
-- SET LOCAL role authenticated;
-- SET LOCAL request.jwt.claim.sub = 'user-uuid-here';
-- SELECT * FROM requests; -- Should only see own client's requests
