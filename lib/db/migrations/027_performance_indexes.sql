-- 027_performance_indexes.sql
-- Performance indexes for high-traffic tables identified in database optimizer audit.
--
-- All indexes use CONCURRENTLY to avoid table locks during creation.
-- Run this file connected to the Supabase database OUTSIDE a transaction block.
--
-- Tables covered:
--   staff_task_boards, staff_task_columns, staff_tasks, staff_task_assignees,
--   staff_task_checklists, staff_task_comments, staff_task_labels,
--   staff_task_label_relations, projects, maintenance_plans,
--   maintenance_plan_usage, support_tickets, support_ticket_comments,
--   activity_logs, staff_assignments
--
-- Estimated improvement: kanban board load ~60-80% faster (seq scan → index scan),
--   project list sort ~40% faster (added created_at index),
--   SLA dashboard ~70% faster (composite status + sla indexes).

-- ─────────────────────────────────────────────────────────────────────────────
-- staff_task_boards  (GET /api/tasks/boards filters is_archived, sorts is_default + sort_order)
-- ─────────────────────────────────────────────────────────────────────────────
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_staff_task_boards_is_archived
  ON staff_task_boards (is_archived)
  WHERE deleted_at IS NULL;

CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_staff_task_boards_is_default_sort_order
  ON staff_task_boards (is_default DESC, sort_order DESC)
  WHERE deleted_at IS NULL;

-- ─────────────────────────────────────────────────────────────────────────────
-- staff_task_columns  (ordered fetch per board on every board render)
-- ─────────────────────────────────────────────────────────────────────────────
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_staff_task_columns_board_id_position
  ON staff_task_columns (board_id, position);

-- ─────────────────────────────────────────────────────────────────────────────
-- staff_tasks  (kanban render + position calc on every task creation)
-- ─────────────────────────────────────────────────────────────────────────────
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_staff_tasks_column_id_position
  ON staff_tasks (column_id, position)
  WHERE deleted_at IS NULL;

CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_staff_tasks_board_id
  ON staff_tasks (board_id)
  WHERE deleted_at IS NULL;

CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_staff_tasks_parent_id
  ON staff_tasks (parent_id)
  WHERE parent_id IS NOT NULL;

-- ─────────────────────────────────────────────────────────────────────────────
-- staff_task_assignees  (user-centric task views; join from tasks)
-- ─────────────────────────────────────────────────────────────────────────────
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_staff_task_assignees_task_id
  ON staff_task_assignees (task_id);

CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_staff_task_assignees_user_id
  ON staff_task_assignees (user_id);

-- ─────────────────────────────────────────────────────────────────────────────
-- staff_task_checklists  (fetched alongside task detail)
-- ─────────────────────────────────────────────────────────────────────────────
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_staff_task_checklists_task_id_position
  ON staff_task_checklists (task_id, position);

-- ─────────────────────────────────────────────────────────────────────────────
-- staff_task_comments  (thread view ordered by time)
-- ─────────────────────────────────────────────────────────────────────────────
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_staff_task_comments_task_id_created_at
  ON staff_task_comments (task_id, created_at DESC);

-- ─────────────────────────────────────────────────────────────────────────────
-- staff_task_labels  (label list per board in filter UI)
-- ─────────────────────────────────────────────────────────────────────────────
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_staff_task_labels_board_id
  ON staff_task_labels (board_id);

-- ─────────────────────────────────────────────────────────────────────────────
-- staff_task_label_relations  (reverse lookup: tasks by label)
-- ─────────────────────────────────────────────────────────────────────────────
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_staff_task_label_relations_label_id
  ON staff_task_label_relations (label_id);

-- ─────────────────────────────────────────────────────────────────────────────
-- projects  (GET /api/projects ORDER BY created_at DESC)
-- ─────────────────────────────────────────────────────────────────────────────
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_projects_created_at
  ON projects (created_at DESC)
  WHERE deleted_at IS NULL;

-- Composite for the most common filter: client + status
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_projects_client_id_status
  ON projects (client_id, status)
  WHERE deleted_at IS NULL;

-- ─────────────────────────────────────────────────────────────────────────────
-- maintenance_plans  (client dashboard + Inngest billing cron)
-- ─────────────────────────────────────────────────────────────────────────────
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_maintenance_plans_client_id_status
  ON maintenance_plans (client_id, status)
  WHERE deleted_at IS NULL;

CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_maintenance_plans_next_billing_date
  ON maintenance_plans (next_billing_date)
  WHERE deleted_at IS NULL AND status = 'active';

-- ─────────────────────────────────────────────────────────────────────────────
-- maintenance_plan_usage  (billing period aggregation — plan_id + date range)
-- ─────────────────────────────────────────────────────────────────────────────
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_maintenance_plan_usage_plan_id_logged_at
  ON maintenance_plan_usage (plan_id, logged_at)
  WHERE deleted_at IS NULL;

-- ─────────────────────────────────────────────────────────────────────────────
-- support_tickets  (SLA dashboard + assignment views)
-- ─────────────────────────────────────────────────────────────────────────────
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_support_tickets_status
  ON support_tickets (status)
  WHERE deleted_at IS NULL;

CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_support_tickets_assigned_to_status
  ON support_tickets (assigned_to, status)
  WHERE deleted_at IS NULL;

CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_support_tickets_client_id_status_priority
  ON support_tickets (client_id, status, priority DESC)
  WHERE deleted_at IS NULL;

-- SLA breach monitoring (Inngest sla-checks function runs every 5 min)
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_support_tickets_sla_response_due_at
  ON support_tickets (sla_response_due_at)
  WHERE deleted_at IS NULL AND sla_response_breached = false;

CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_support_tickets_sla_resolution_due_at
  ON support_tickets (sla_resolution_due_at)
  WHERE deleted_at IS NULL AND sla_resolution_breached = false;

-- ─────────────────────────────────────────────────────────────────────────────
-- support_ticket_comments  (comment thread per ticket)
-- ─────────────────────────────────────────────────────────────────────────────
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_support_ticket_comments_ticket_id_created_at
  ON support_ticket_comments (support_ticket_id, created_at DESC)
  WHERE deleted_at IS NULL;

-- ─────────────────────────────────────────────────────────────────────────────
-- activity_logs  (audit trail — always filtered by client, ordered by time)
-- ─────────────────────────────────────────────────────────────────────────────
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_activity_logs_client_id_created_at
  ON activity_logs (client_id, created_at DESC);

-- ─────────────────────────────────────────────────────────────────────────────
-- staff_assignments  (client staff list + user access control)
-- ─────────────────────────────────────────────────────────────────────────────
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_staff_assignments_client_id
  ON staff_assignments (client_id);

CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_staff_assignments_user_id
  ON staff_assignments (user_id);

-- ─────────────────────────────────────────────────────────────────────────────
-- VALIDATION QUERIES  (run after applying indexes)
-- ─────────────────────────────────────────────────────────────────────────────
--
-- 1. Confirm all indexes were created:
--
--   SELECT indexname, tablename, indexdef
--   FROM pg_indexes
--   WHERE indexname LIKE 'idx_staff_task%'
--      OR indexname IN (
--        'idx_projects_created_at',
--        'idx_projects_client_id_status',
--        'idx_maintenance_plans_client_id_status',
--        'idx_maintenance_plan_usage_plan_id_logged_at',
--        'idx_support_tickets_assigned_to_status',
--        'idx_support_ticket_comments_ticket_id_created_at',
--        'idx_activity_logs_client_id_created_at',
--        'idx_staff_assignments_client_id'
--      )
--   ORDER BY tablename, indexname;
--
-- 2. Confirm kanban query now uses index (should show Index Scan, not Seq Scan):
--
--   EXPLAIN (ANALYZE, BUFFERS)
--   SELECT id, title, position FROM staff_tasks
--   WHERE column_id = '<any-column-uuid>' AND deleted_at IS NULL
--   ORDER BY position;
--
-- 3. Check index usage after traffic (run after 1 hour of production load):
--
--   SELECT relname AS table, indexrelname AS index,
--          idx_scan, idx_tup_read, idx_tup_fetch
--   FROM pg_stat_user_indexes
--   WHERE relname IN (
--     'staff_tasks', 'staff_task_boards', 'staff_task_columns',
--     'support_tickets', 'activity_logs', 'projects'
--   )
--   ORDER BY idx_scan DESC;
--
--   Any index with idx_scan = 0 after several hours of traffic can be dropped.
