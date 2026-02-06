-- Feature Flags Seed Data
-- Insert all 55+ features available in the system

-- Core Features (already implemented)
INSERT INTO features (name, display_name, description, category, is_enabled_by_default) VALUES
('clients', 'Client Management', 'Manage client companies and profiles', 'core', true),
('users', 'User Management', 'Manage user accounts and permissions', 'core', true),
('invoices', 'Invoicing', 'Create and manage invoices with payment tracking', 'finance', true),
('recurring_invoices', 'Recurring Invoices', 'Automated recurring invoice generation', 'finance', true),
('requests', 'Service Requests', 'Track service requests and tickets', 'operations', true),
('documents', 'Document Library', 'Upload and manage documents with versioning', 'files', true),
('contracts', 'Contract Management', 'Manage service contracts and agreements', 'legal', true),
('activity_logs', 'Activity Logs', 'Track user activity and system events', 'core', true),
('settings', 'System Settings', 'Configure system preferences', 'core', true);

-- Support & Ticketing
INSERT INTO features (name, display_name, description, category, is_enabled_by_default) VALUES
('support_tickets', 'Support Tickets', 'Advanced support ticketing system with SLA tracking', 'support', true);

-- Proposals
INSERT INTO features (name, display_name, description, category, is_enabled_by_default) VALUES
('proposals', 'Proposals', 'Create proposals with e-signature and tracking', 'sales', true);

-- Time Tracking
INSERT INTO features (name, display_name, description, category, is_enabled_by_default) VALUES
('time_tracking', 'Time Tracking', 'Track billable hours with live timer', 'operations', true);

-- Project Management
INSERT INTO features (name, display_name, description, category, is_enabled_by_default) VALUES
('projects', 'Projects', 'Full project management with timeline and budgets', 'projects', true),
('project_budgets', 'Project Budgets', 'Track project costs and budget allocation', 'projects', true),
('project_timeline', 'Project Timeline', 'Gantt chart timeline view for projects', 'projects', true),
('project_milestones', 'Project Milestones', 'Define and track project milestones', 'projects', true);

-- Task Management
INSERT INTO features (name, display_name, description, category, is_enabled_by_default) VALUES
('staff_tasks', 'Staff Tasks', 'Internal task management with Kanban board', 'operations', true),
('task_board', 'Task Board', 'Visual Kanban board for team tasks', 'operations', true),
('client_tasks', 'Client Tasks', 'Client-facing task management', 'operations', false);

-- Communication
INSERT INTO features (name, display_name, description, category, is_enabled_by_default) VALUES
('meetings', 'Meetings', 'Schedule and manage meetings with notes', 'communication', true),
('meeting_notes', 'Meeting Notes', 'Rich text meeting notes and action items', 'communication', true),
('messages', 'Internal Messaging', 'Real-time team messaging system', 'communication', true);

-- Maintenance
INSERT INTO features (name, display_name, description, category, is_enabled_by_default) VALUES
('maintenance_plans', 'Maintenance Plans', 'Recurring maintenance plans with hour tracking', 'services', true);

-- Marketing
INSERT INTO features (name, display_name, description, category, is_enabled_by_default) VALUES
('marketing_tools', 'Marketing Tools', 'Campaign management and content calendar', 'marketing', false),
('social_media', 'Social Media Management', 'Schedule and publish social media posts', 'marketing', false),
('ad_management', 'Ad Management', 'Manage advertising campaigns and metrics', 'marketing', false),
('content_calendar', 'Content Calendar', 'Plan and schedule content', 'marketing', false),
('lead_management', 'Lead Management', 'Track and nurture leads', 'marketing', false);

-- Brand
INSERT INTO features (name, display_name, description, category, is_enabled_by_default) VALUES
('brand_monitoring', 'Brand Monitoring', 'Monitor brand mentions and sentiment', 'brand', false),
('brand_guide', 'Brand Guide', 'Centralized brand guidelines and assets', 'brand', true),
('competitor_tracking', 'Competitor Tracking', 'Monitor competitor activity', 'brand', false);

-- AI Features
INSERT INTO features (name, display_name, description, category, is_enabled_by_default, requires_setup) VALUES
('ai_assistant', 'AI Assistant', 'AI-powered chat assistant', 'ai', false, true),
('ai_management', 'AI Management', 'Configure AI providers and models', 'ai', false, true),
('ai_workflows', 'AI Workflows', 'Automated AI-powered workflows', 'ai', false, true),
('ai_analytics', 'AI Analytics', 'AI usage tracking and analytics', 'ai', false, false);

-- Automation
INSERT INTO features (name, display_name, description, category, is_enabled_by_default) VALUES
('automation', 'Automation Workflows', 'Create automated business workflows', 'automation', true);

-- Reports
INSERT INTO features (name, display_name, description, category, is_enabled_by_default) VALUES
('reports_dashboard', 'Reports Dashboard', 'Customizable analytics dashboard', 'reporting', true),
('team_workload', 'Team Workload', 'Track team capacity and utilization', 'reporting', true),
('client_reports', 'Client Reports', 'Generate client-facing reports', 'reporting', true),
('custom_dashboards', 'Custom Dashboards', 'Build custom dashboard layouts', 'reporting', false);

-- Partners & Referrals
INSERT INTO features (name, display_name, description, category, is_enabled_by_default) VALUES
('partner_management', 'Partner Management', 'Manage partner relationships', 'partnerships', false),
('referrals', 'Referral Program', 'Track referrals and commissions', 'partnerships', false);

-- Knowledge Base
INSERT INTO features (name, display_name, description, category, is_enabled_by_default) VALUES
('staff_guides', 'Staff Guides', 'Internal knowledge base for staff', 'knowledge', true),
('knowledge_base', 'Knowledge Base', 'Public knowledge base articles', 'knowledge', false);

-- Surveys
INSERT INTO features (name, display_name, description, category, is_enabled_by_default) VALUES
('feedback_surveys', 'Feedback & Surveys', 'Collect client feedback via surveys', 'feedback', true);

-- Account Health
INSERT INTO features (name, display_name, description, category, is_enabled_by_default) VALUES
('account_health', 'Account Health', 'Client health scoring and monitoring', 'analytics', true);

-- Storage
INSERT INTO features (name, display_name, description, category, is_enabled_by_default, requires_setup) VALUES
('storage_management', 'Storage Management', 'Connect and sync with cloud storage providers', 'files', false, true);

-- Webhooks
INSERT INTO features (name, display_name, description, category, is_enabled_by_default) VALUES
('webhooks', 'Webhooks', 'Configure webhook endpoints for integrations', 'integrations', false);

-- Security & Privacy
INSERT INTO features (name, display_name, description, category, is_enabled_by_default) VALUES
('security_overview', 'Security Overview', 'Security audit and monitoring', 'security', true),
('privacy_requests', 'Privacy Requests', 'GDPR data export and deletion requests', 'security', true);

-- System Features
INSERT INTO features (name, display_name, description, category, is_enabled_by_default) VALUES
('form_templates', 'Form Templates', 'Create custom form templates', 'system', false),
('white_label', 'White Label', 'Custom branding and domain configuration', 'system', false),
('email_assistant', 'Email Assistant', 'AI-powered email composition', 'communication', false);
