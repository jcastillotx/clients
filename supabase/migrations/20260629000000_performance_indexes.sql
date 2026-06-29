-- 20260629000000_performance_indexes.sql
-- Performance indexes for high-traffic tables (database optimizer audit).
-- Partial WHERE deleted_at IS NULL only on tables that have that column.

CREATE INDEX IF NOT EXISTS idx_staff_task_boards_is_archived ON staff_task_boards (is_archived);
CREATE INDEX IF NOT EXISTS idx_staff_task_boards_is_default_sort_order ON staff_task_boards (is_default DESC, sort_order DESC);
CREATE INDEX IF NOT EXISTS idx_staff_task_columns_board_id_position ON staff_task_columns (board_id, position);
CREATE INDEX IF NOT EXISTS idx_staff_tasks_column_id_position ON staff_tasks (column_id, position);
CREATE INDEX IF NOT EXISTS idx_staff_tasks_board_id ON staff_tasks (board_id);
CREATE INDEX IF NOT EXISTS idx_staff_tasks_parent_id ON staff_tasks (parent_id) WHERE parent_id IS NOT NULL;
CREATE INDEX IF NOT EXISTS idx_staff_task_assignees_task_id ON staff_task_assignees (task_id);
CREATE INDEX IF NOT EXISTS idx_staff_task_assignees_user_id ON staff_task_assignees (user_id);
CREATE INDEX IF NOT EXISTS idx_staff_task_checklists_task_id_position ON staff_task_checklists (task_id, position);
CREATE INDEX IF NOT EXISTS idx_staff_task_comments_task_id_created_at ON staff_task_comments (task_id, created_at DESC);
CREATE INDEX IF NOT EXISTS idx_staff_task_labels_board_id ON staff_task_labels (board_id);
CREATE INDEX IF NOT EXISTS idx_staff_task_label_relations_label_id ON staff_task_label_relations (label_id);
CREATE INDEX IF NOT EXISTS idx_projects_created_at ON projects (created_at DESC) WHERE deleted_at IS NULL;
CREATE INDEX IF NOT EXISTS idx_projects_client_id_status ON projects (client_id, status) WHERE deleted_at IS NULL;
CREATE INDEX IF NOT EXISTS idx_maintenance_plans_client_id_status ON maintenance_plans (client_id, status) WHERE deleted_at IS NULL;
CREATE INDEX IF NOT EXISTS idx_maintenance_plans_next_billing_date ON maintenance_plans (next_billing_date) WHERE deleted_at IS NULL AND status = 'active';
CREATE INDEX IF NOT EXISTS idx_maintenance_plan_usage_plan_id_logged_at ON maintenance_plan_usage (plan_id, logged_at) WHERE deleted_at IS NULL;
CREATE INDEX IF NOT EXISTS idx_support_tickets_status ON support_tickets (status) WHERE deleted_at IS NULL;
CREATE INDEX IF NOT EXISTS idx_support_tickets_assigned_to_status ON support_tickets (assigned_to, status) WHERE deleted_at IS NULL;
CREATE INDEX IF NOT EXISTS idx_support_tickets_client_id_status_priority ON support_tickets (client_id, status, priority DESC) WHERE deleted_at IS NULL;
CREATE INDEX IF NOT EXISTS idx_support_tickets_sla_response_due_at ON support_tickets (sla_response_due_at) WHERE deleted_at IS NULL AND sla_response_breached = false;
CREATE INDEX IF NOT EXISTS idx_support_tickets_sla_resolution_due_at ON support_tickets (sla_resolution_due_at) WHERE deleted_at IS NULL AND sla_resolution_breached = false;
CREATE INDEX IF NOT EXISTS idx_support_ticket_comments_ticket_id_created_at ON support_ticket_comments (support_ticket_id, created_at DESC) WHERE deleted_at IS NULL;
CREATE INDEX IF NOT EXISTS idx_activity_logs_client_id_created_at ON activity_logs (client_id, created_at DESC);
CREATE INDEX IF NOT EXISTS idx_staff_assignments_client_id ON staff_assignments (client_id);
CREATE INDEX IF NOT EXISTS idx_staff_assignments_user_id ON staff_assignments (user_id);
