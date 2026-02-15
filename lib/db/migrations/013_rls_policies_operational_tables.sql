-- ============================================================================
-- Migration 013: RLS Policies for Operational Tables
-- Covers: Staff Tasks, Meetings, Messaging, Maintenance, Marketing, Social/Ads
-- ============================================================================
-- NOTE: Tables are created by Drizzle `db:push`. This migration adds RLS only.
-- Run AFTER `pnpm db:push` has created the table structures.
-- ============================================================================

-- Helper: reusable admin check
CREATE OR REPLACE FUNCTION public.is_admin_or_super_admin()
RETURNS BOOLEAN
LANGUAGE sql
SECURITY DEFINER
STABLE
AS $$
  SELECT EXISTS (
    SELECT 1 FROM public.user_roles ur
    JOIN public.roles r ON ur.role_id = r.id
    WHERE ur.user_id = auth.uid()
    AND r.name IN ('super_admin', 'admin')
  );
$$;

CREATE OR REPLACE FUNCTION public.is_staff_or_above()
RETURNS BOOLEAN
LANGUAGE sql
SECURITY DEFINER
STABLE
AS $$
  SELECT EXISTS (
    SELECT 1 FROM public.user_roles ur
    JOIN public.roles r ON ur.role_id = r.id
    WHERE ur.user_id = auth.uid()
    AND r.name IN ('super_admin', 'admin', 'account_manager', 'staff')
  );
$$;

-- ===========================================
-- STAFF TASKS (8 tables)
-- ===========================================

-- staff_task_boards: no client_id, scoped by team/org
ALTER TABLE IF EXISTS public.staff_task_boards ENABLE ROW LEVEL SECURITY;
DO $$ BEGIN
  IF EXISTS (SELECT 1 FROM pg_tables WHERE tablename = 'staff_task_boards') THEN
    EXECUTE 'CREATE POLICY "staff_view_boards" ON public.staff_task_boards FOR SELECT USING (public.is_staff_or_above())';
    EXECUTE 'CREATE POLICY "staff_manage_boards" ON public.staff_task_boards FOR ALL USING (public.is_staff_or_above())';
  END IF;
END $$;

-- staff_task_columns: child of boards
ALTER TABLE IF EXISTS public.staff_task_columns ENABLE ROW LEVEL SECURITY;
DO $$ BEGIN
  IF EXISTS (SELECT 1 FROM pg_tables WHERE tablename = 'staff_task_columns') THEN
    EXECUTE 'CREATE POLICY "staff_view_columns" ON public.staff_task_columns FOR SELECT USING (public.is_staff_or_above())';
    EXECUTE 'CREATE POLICY "staff_manage_columns" ON public.staff_task_columns FOR ALL USING (public.is_staff_or_above())';
  END IF;
END $$;

-- staff_tasks: has client_id
ALTER TABLE IF EXISTS public.staff_tasks ENABLE ROW LEVEL SECURITY;
DO $$ BEGIN
  IF EXISTS (SELECT 1 FROM pg_tables WHERE tablename = 'staff_tasks') THEN
    EXECUTE 'CREATE POLICY "view_client_tasks" ON public.staff_tasks FOR SELECT USING (
      client_id IS NULL AND public.is_staff_or_above()
      OR client_id = public.get_current_user_client_id()
      OR public.is_admin_or_super_admin()
    )';
    EXECUTE 'CREATE POLICY "manage_tasks" ON public.staff_tasks FOR ALL USING (public.is_staff_or_above())';
  END IF;
END $$;

-- staff_task_checklists: child of tasks
ALTER TABLE IF EXISTS public.staff_task_checklists ENABLE ROW LEVEL SECURITY;
DO $$ BEGIN
  IF EXISTS (SELECT 1 FROM pg_tables WHERE tablename = 'staff_task_checklists') THEN
    EXECUTE 'CREATE POLICY "staff_view_checklists" ON public.staff_task_checklists FOR SELECT USING (public.is_staff_or_above())';
    EXECUTE 'CREATE POLICY "staff_manage_checklists" ON public.staff_task_checklists FOR ALL USING (public.is_staff_or_above())';
  END IF;
END $$;

-- staff_task_comments: has user_id
ALTER TABLE IF EXISTS public.staff_task_comments ENABLE ROW LEVEL SECURITY;
DO $$ BEGIN
  IF EXISTS (SELECT 1 FROM pg_tables WHERE tablename = 'staff_task_comments') THEN
    EXECUTE 'CREATE POLICY "staff_view_task_comments" ON public.staff_task_comments FOR SELECT USING (public.is_staff_or_above())';
    EXECUTE 'CREATE POLICY "staff_create_task_comments" ON public.staff_task_comments FOR INSERT WITH CHECK (public.is_staff_or_above())';
    EXECUTE 'CREATE POLICY "own_task_comments" ON public.staff_task_comments FOR UPDATE USING (user_id = auth.uid())';
    EXECUTE 'CREATE POLICY "delete_own_task_comments" ON public.staff_task_comments FOR DELETE USING (user_id = auth.uid() OR public.is_admin_or_super_admin())';
  END IF;
END $$;

-- staff_task_labels: child of boards
ALTER TABLE IF EXISTS public.staff_task_labels ENABLE ROW LEVEL SECURITY;
DO $$ BEGIN
  IF EXISTS (SELECT 1 FROM pg_tables WHERE tablename = 'staff_task_labels') THEN
    EXECUTE 'CREATE POLICY "staff_view_labels" ON public.staff_task_labels FOR SELECT USING (public.is_staff_or_above())';
    EXECUTE 'CREATE POLICY "staff_manage_labels" ON public.staff_task_labels FOR ALL USING (public.is_staff_or_above())';
  END IF;
END $$;

-- staff_task_assignees: junction table
ALTER TABLE IF EXISTS public.staff_task_assignees ENABLE ROW LEVEL SECURITY;
DO $$ BEGIN
  IF EXISTS (SELECT 1 FROM pg_tables WHERE tablename = 'staff_task_assignees') THEN
    EXECUTE 'CREATE POLICY "staff_view_assignees" ON public.staff_task_assignees FOR SELECT USING (public.is_staff_or_above())';
    EXECUTE 'CREATE POLICY "staff_manage_assignees" ON public.staff_task_assignees FOR ALL USING (public.is_staff_or_above())';
  END IF;
END $$;

-- staff_task_label_relations: junction table
ALTER TABLE IF EXISTS public.staff_task_label_relations ENABLE ROW LEVEL SECURITY;
DO $$ BEGIN
  IF EXISTS (SELECT 1 FROM pg_tables WHERE tablename = 'staff_task_label_relations') THEN
    EXECUTE 'CREATE POLICY "staff_view_label_rels" ON public.staff_task_label_relations FOR SELECT USING (public.is_staff_or_above())';
    EXECUTE 'CREATE POLICY "staff_manage_label_rels" ON public.staff_task_label_relations FOR ALL USING (public.is_staff_or_above())';
  END IF;
END $$;

-- ===========================================
-- MEETINGS (3 tables)
-- ===========================================

-- meetings: has client_id
ALTER TABLE IF EXISTS public.meetings ENABLE ROW LEVEL SECURITY;
DO $$ BEGIN
  IF EXISTS (SELECT 1 FROM pg_tables WHERE tablename = 'meetings') THEN
    EXECUTE 'CREATE POLICY "view_client_meetings" ON public.meetings FOR SELECT USING (
      client_id = public.get_current_user_client_id()
      OR public.is_admin_or_super_admin()
    )';
    EXECUTE 'CREATE POLICY "staff_manage_meetings" ON public.meetings FOR ALL USING (public.is_staff_or_above())';
  END IF;
END $$;

-- meeting_notes: child of meetings
ALTER TABLE IF EXISTS public.meeting_notes ENABLE ROW LEVEL SECURITY;
DO $$ BEGIN
  IF EXISTS (SELECT 1 FROM pg_tables WHERE tablename = 'meeting_notes') THEN
    EXECUTE 'CREATE POLICY "view_meeting_notes" ON public.meeting_notes FOR SELECT USING (
      EXISTS (
        SELECT 1 FROM public.meetings m
        WHERE m.id = meeting_notes.meeting_id
        AND (m.client_id = public.get_current_user_client_id() OR public.is_admin_or_super_admin())
      )
    )';
    EXECUTE 'CREATE POLICY "staff_manage_meeting_notes" ON public.meeting_notes FOR ALL USING (public.is_staff_or_above())';
  END IF;
END $$;

-- meeting_attendees: child of meetings
ALTER TABLE IF EXISTS public.meeting_attendees ENABLE ROW LEVEL SECURITY;
DO $$ BEGIN
  IF EXISTS (SELECT 1 FROM pg_tables WHERE tablename = 'meeting_attendees') THEN
    EXECUTE 'CREATE POLICY "view_meeting_attendees" ON public.meeting_attendees FOR SELECT USING (
      EXISTS (
        SELECT 1 FROM public.meetings m
        WHERE m.id = meeting_attendees.meeting_id
        AND (m.client_id = public.get_current_user_client_id() OR public.is_admin_or_super_admin())
      )
    )';
    EXECUTE 'CREATE POLICY "staff_manage_attendees" ON public.meeting_attendees FOR ALL USING (public.is_staff_or_above())';
  END IF;
END $$;

-- ===========================================
-- MESSAGING (5 tables)
-- ===========================================

-- conversations: has client_id
ALTER TABLE IF EXISTS public.conversations ENABLE ROW LEVEL SECURITY;
DO $$ BEGIN
  IF EXISTS (SELECT 1 FROM pg_tables WHERE tablename = 'conversations') THEN
    EXECUTE 'CREATE POLICY "view_client_conversations" ON public.conversations FOR SELECT USING (
      client_id IS NULL
      OR client_id = public.get_current_user_client_id()
      OR public.is_admin_or_super_admin()
    )';
    EXECUTE 'CREATE POLICY "authenticated_create_conversations" ON public.conversations FOR INSERT WITH CHECK (auth.uid() IS NOT NULL)';
    EXECUTE 'CREATE POLICY "admin_manage_conversations" ON public.conversations FOR ALL USING (public.is_admin_or_super_admin())';
  END IF;
END $$;

-- conversation_participants: junction table
ALTER TABLE IF EXISTS public.conversation_participants ENABLE ROW LEVEL SECURITY;
DO $$ BEGIN
  IF EXISTS (SELECT 1 FROM pg_tables WHERE tablename = 'conversation_participants') THEN
    EXECUTE 'CREATE POLICY "view_own_participations" ON public.conversation_participants FOR SELECT USING (
      user_id = auth.uid()
      OR public.is_admin_or_super_admin()
    )';
    EXECUTE 'CREATE POLICY "manage_participations" ON public.conversation_participants FOR ALL USING (auth.uid() IS NOT NULL)';
  END IF;
END $$;

-- messages: child of conversations
ALTER TABLE IF EXISTS public.messages ENABLE ROW LEVEL SECURITY;
DO $$ BEGIN
  IF EXISTS (SELECT 1 FROM pg_tables WHERE tablename = 'messages') THEN
    EXECUTE 'CREATE POLICY "view_conversation_messages" ON public.messages FOR SELECT USING (
      EXISTS (
        SELECT 1 FROM public.conversation_participants cp
        WHERE cp.conversation_id = messages.conversation_id
        AND cp.user_id = auth.uid()
      )
      OR public.is_admin_or_super_admin()
    )';
    EXECUTE 'CREATE POLICY "send_messages" ON public.messages FOR INSERT WITH CHECK (sender_id = auth.uid())';
    EXECUTE 'CREATE POLICY "edit_own_messages" ON public.messages FOR UPDATE USING (sender_id = auth.uid())';
    EXECUTE 'CREATE POLICY "delete_own_messages" ON public.messages FOR DELETE USING (sender_id = auth.uid() OR public.is_admin_or_super_admin())';
  END IF;
END $$;

-- message_reads: tracks read receipts
ALTER TABLE IF EXISTS public.message_reads ENABLE ROW LEVEL SECURITY;
DO $$ BEGIN
  IF EXISTS (SELECT 1 FROM pg_tables WHERE tablename = 'message_reads') THEN
    EXECUTE 'CREATE POLICY "view_own_reads" ON public.message_reads FOR SELECT USING (user_id = auth.uid() OR public.is_admin_or_super_admin())';
    EXECUTE 'CREATE POLICY "mark_own_reads" ON public.message_reads FOR INSERT WITH CHECK (user_id = auth.uid())';
  END IF;
END $$;

-- message_attachments: child of messages
ALTER TABLE IF EXISTS public.message_attachments ENABLE ROW LEVEL SECURITY;
DO $$ BEGIN
  IF EXISTS (SELECT 1 FROM pg_tables WHERE tablename = 'message_attachments') THEN
    EXECUTE 'CREATE POLICY "view_message_attachments" ON public.message_attachments FOR SELECT USING (
      EXISTS (
        SELECT 1 FROM public.messages m
        JOIN public.conversation_participants cp ON cp.conversation_id = m.conversation_id
        WHERE m.id = message_attachments.message_id
        AND cp.user_id = auth.uid()
      )
      OR public.is_admin_or_super_admin()
    )';
    EXECUTE 'CREATE POLICY "add_attachments" ON public.message_attachments FOR INSERT WITH CHECK (auth.uid() IS NOT NULL)';
  END IF;
END $$;

-- ===========================================
-- MAINTENANCE PLANS (3 tables)
-- ===========================================

-- maintenance_plans: has client_id
ALTER TABLE IF EXISTS public.maintenance_plans ENABLE ROW LEVEL SECURITY;
DO $$ BEGIN
  IF EXISTS (SELECT 1 FROM pg_tables WHERE tablename = 'maintenance_plans') THEN
    EXECUTE 'CREATE POLICY "view_client_plans" ON public.maintenance_plans FOR SELECT USING (
      client_id = public.get_current_user_client_id()
      OR public.is_admin_or_super_admin()
    )';
    EXECUTE 'CREATE POLICY "staff_manage_plans" ON public.maintenance_plans FOR ALL USING (public.is_staff_or_above())';
  END IF;
END $$;

-- maintenance_plan_usage: child of plans
ALTER TABLE IF EXISTS public.maintenance_plan_usage ENABLE ROW LEVEL SECURITY;
DO $$ BEGIN
  IF EXISTS (SELECT 1 FROM pg_tables WHERE tablename = 'maintenance_plan_usage') THEN
    EXECUTE 'CREATE POLICY "view_plan_usage" ON public.maintenance_plan_usage FOR SELECT USING (
      EXISTS (
        SELECT 1 FROM public.maintenance_plans mp
        WHERE mp.id = maintenance_plan_usage.plan_id
        AND (mp.client_id = public.get_current_user_client_id() OR public.is_admin_or_super_admin())
      )
    )';
    EXECUTE 'CREATE POLICY "staff_manage_usage" ON public.maintenance_plan_usage FOR ALL USING (public.is_staff_or_above())';
  END IF;
END $$;

-- maintenance_plan_billing_history: child of plans
ALTER TABLE IF EXISTS public.maintenance_plan_billing_history ENABLE ROW LEVEL SECURITY;
DO $$ BEGIN
  IF EXISTS (SELECT 1 FROM pg_tables WHERE tablename = 'maintenance_plan_billing_history') THEN
    EXECUTE 'CREATE POLICY "view_billing_history" ON public.maintenance_plan_billing_history FOR SELECT USING (
      EXISTS (
        SELECT 1 FROM public.maintenance_plans mp
        WHERE mp.id = maintenance_plan_billing_history.plan_id
        AND (mp.client_id = public.get_current_user_client_id() OR public.is_admin_or_super_admin())
      )
    )';
    EXECUTE 'CREATE POLICY "admin_manage_billing" ON public.maintenance_plan_billing_history FOR ALL USING (public.is_admin_or_super_admin())';
  END IF;
END $$;

-- ===========================================
-- MARKETING (7 tables)
-- ===========================================

-- campaigns: has client_id
ALTER TABLE IF EXISTS public.campaigns ENABLE ROW LEVEL SECURITY;
DO $$ BEGIN
  IF EXISTS (SELECT 1 FROM pg_tables WHERE tablename = 'campaigns') THEN
    EXECUTE 'CREATE POLICY "view_client_campaigns" ON public.campaigns FOR SELECT USING (
      client_id = public.get_current_user_client_id()
      OR public.is_admin_or_super_admin()
    )';
    EXECUTE 'CREATE POLICY "staff_manage_campaigns" ON public.campaigns FOR ALL USING (public.is_staff_or_above())';
  END IF;
END $$;

-- campaign_assets: child of campaigns
ALTER TABLE IF EXISTS public.campaign_assets ENABLE ROW LEVEL SECURITY;
DO $$ BEGIN
  IF EXISTS (SELECT 1 FROM pg_tables WHERE tablename = 'campaign_assets') THEN
    EXECUTE 'CREATE POLICY "view_campaign_assets" ON public.campaign_assets FOR SELECT USING (
      EXISTS (
        SELECT 1 FROM public.campaigns c
        WHERE c.id = campaign_assets.campaign_id
        AND (c.client_id = public.get_current_user_client_id() OR public.is_admin_or_super_admin())
      )
    )';
    EXECUTE 'CREATE POLICY "staff_manage_assets" ON public.campaign_assets FOR ALL USING (public.is_staff_or_above())';
  END IF;
END $$;

-- campaign_metrics: child of campaigns
ALTER TABLE IF EXISTS public.campaign_metrics ENABLE ROW LEVEL SECURITY;
DO $$ BEGIN
  IF EXISTS (SELECT 1 FROM pg_tables WHERE tablename = 'campaign_metrics') THEN
    EXECUTE 'CREATE POLICY "view_campaign_metrics" ON public.campaign_metrics FOR SELECT USING (
      EXISTS (
        SELECT 1 FROM public.campaigns c
        WHERE c.id = campaign_metrics.campaign_id
        AND (c.client_id = public.get_current_user_client_id() OR public.is_admin_or_super_admin())
      )
    )';
    EXECUTE 'CREATE POLICY "staff_manage_metrics" ON public.campaign_metrics FOR ALL USING (public.is_staff_or_above())';
  END IF;
END $$;

-- content_calendar_items: has client_id
ALTER TABLE IF EXISTS public.content_calendar_items ENABLE ROW LEVEL SECURITY;
DO $$ BEGIN
  IF EXISTS (SELECT 1 FROM pg_tables WHERE tablename = 'content_calendar_items') THEN
    EXECUTE 'CREATE POLICY "view_client_calendar" ON public.content_calendar_items FOR SELECT USING (
      client_id = public.get_current_user_client_id()
      OR public.is_admin_or_super_admin()
    )';
    EXECUTE 'CREATE POLICY "staff_manage_calendar" ON public.content_calendar_items FOR ALL USING (public.is_staff_or_above())';
  END IF;
END $$;

-- content_templates: has client_id
ALTER TABLE IF EXISTS public.content_templates ENABLE ROW LEVEL SECURITY;
DO $$ BEGIN
  IF EXISTS (SELECT 1 FROM pg_tables WHERE tablename = 'content_templates') THEN
    EXECUTE 'CREATE POLICY "view_content_templates" ON public.content_templates FOR SELECT USING (
      client_id IS NULL
      OR client_id = public.get_current_user_client_id()
      OR public.is_admin_or_super_admin()
    )';
    EXECUTE 'CREATE POLICY "staff_manage_content_templates" ON public.content_templates FOR ALL USING (public.is_staff_or_above())';
  END IF;
END $$;

-- leads: has client_id
ALTER TABLE IF EXISTS public.leads ENABLE ROW LEVEL SECURITY;
DO $$ BEGIN
  IF EXISTS (SELECT 1 FROM pg_tables WHERE tablename = 'leads') THEN
    EXECUTE 'CREATE POLICY "view_client_leads" ON public.leads FOR SELECT USING (
      client_id = public.get_current_user_client_id()
      OR public.is_admin_or_super_admin()
    )';
    EXECUTE 'CREATE POLICY "staff_manage_leads" ON public.leads FOR ALL USING (public.is_staff_or_above())';
  END IF;
END $$;

-- lead_activities: child of leads
ALTER TABLE IF EXISTS public.lead_activities ENABLE ROW LEVEL SECURITY;
DO $$ BEGIN
  IF EXISTS (SELECT 1 FROM pg_tables WHERE tablename = 'lead_activities') THEN
    EXECUTE 'CREATE POLICY "view_lead_activities" ON public.lead_activities FOR SELECT USING (
      EXISTS (
        SELECT 1 FROM public.leads l
        WHERE l.id = lead_activities.lead_id
        AND (l.client_id = public.get_current_user_client_id() OR public.is_admin_or_super_admin())
      )
    )';
    EXECUTE 'CREATE POLICY "staff_manage_lead_activities" ON public.lead_activities FOR ALL USING (public.is_staff_or_above())';
  END IF;
END $$;

-- ===========================================
-- SOCIAL MEDIA & ADS (8 tables)
-- ===========================================

-- social_accounts: has client_id
ALTER TABLE IF EXISTS public.social_accounts ENABLE ROW LEVEL SECURITY;
DO $$ BEGIN
  IF EXISTS (SELECT 1 FROM pg_tables WHERE tablename = 'social_accounts') THEN
    EXECUTE 'CREATE POLICY "view_client_social" ON public.social_accounts FOR SELECT USING (
      client_id = public.get_current_user_client_id()
      OR public.is_admin_or_super_admin()
    )';
    EXECUTE 'CREATE POLICY "staff_manage_social" ON public.social_accounts FOR ALL USING (public.is_staff_or_above())';
  END IF;
END $$;

-- social_posts: child of social_accounts
ALTER TABLE IF EXISTS public.social_posts ENABLE ROW LEVEL SECURITY;
DO $$ BEGIN
  IF EXISTS (SELECT 1 FROM pg_tables WHERE tablename = 'social_posts') THEN
    EXECUTE 'CREATE POLICY "view_social_posts" ON public.social_posts FOR SELECT USING (
      EXISTS (
        SELECT 1 FROM public.social_accounts sa
        WHERE sa.id = social_posts.account_id
        AND (sa.client_id = public.get_current_user_client_id() OR public.is_admin_or_super_admin())
      )
    )';
    EXECUTE 'CREATE POLICY "staff_manage_posts" ON public.social_posts FOR ALL USING (public.is_staff_or_above())';
  END IF;
END $$;

-- ad_accounts: has client_id
ALTER TABLE IF EXISTS public.ad_accounts ENABLE ROW LEVEL SECURITY;
DO $$ BEGIN
  IF EXISTS (SELECT 1 FROM pg_tables WHERE tablename = 'ad_accounts') THEN
    EXECUTE 'CREATE POLICY "view_client_ad_accounts" ON public.ad_accounts FOR SELECT USING (
      client_id = public.get_current_user_client_id()
      OR public.is_admin_or_super_admin()
    )';
    EXECUTE 'CREATE POLICY "staff_manage_ad_accounts" ON public.ad_accounts FOR ALL USING (public.is_staff_or_above())';
  END IF;
END $$;

-- ad_campaigns: child of ad_accounts
ALTER TABLE IF EXISTS public.ad_campaigns ENABLE ROW LEVEL SECURITY;
DO $$ BEGIN
  IF EXISTS (SELECT 1 FROM pg_tables WHERE tablename = 'ad_campaigns') THEN
    EXECUTE 'CREATE POLICY "view_ad_campaigns" ON public.ad_campaigns FOR SELECT USING (
      EXISTS (
        SELECT 1 FROM public.ad_accounts aa
        WHERE aa.id = ad_campaigns.ad_account_id
        AND (aa.client_id = public.get_current_user_client_id() OR public.is_admin_or_super_admin())
      )
    )';
    EXECUTE 'CREATE POLICY "staff_manage_ad_campaigns" ON public.ad_campaigns FOR ALL USING (public.is_staff_or_above())';
  END IF;
END $$;

-- ad_sets: child of ad_campaigns (2 levels deep)
ALTER TABLE IF EXISTS public.ad_sets ENABLE ROW LEVEL SECURITY;
DO $$ BEGIN
  IF EXISTS (SELECT 1 FROM pg_tables WHERE tablename = 'ad_sets') THEN
    EXECUTE 'CREATE POLICY "view_ad_sets" ON public.ad_sets FOR SELECT USING (
      EXISTS (
        SELECT 1 FROM public.ad_campaigns ac
        JOIN public.ad_accounts aa ON aa.id = ac.ad_account_id
        WHERE ac.id = ad_sets.campaign_id
        AND (aa.client_id = public.get_current_user_client_id() OR public.is_admin_or_super_admin())
      )
    )';
    EXECUTE 'CREATE POLICY "staff_manage_ad_sets" ON public.ad_sets FOR ALL USING (public.is_staff_or_above())';
  END IF;
END $$;

-- ads: child of ad_sets (3 levels deep)
ALTER TABLE IF EXISTS public.ads ENABLE ROW LEVEL SECURITY;
DO $$ BEGIN
  IF EXISTS (SELECT 1 FROM pg_tables WHERE tablename = 'ads') THEN
    EXECUTE 'CREATE POLICY "staff_view_ads" ON public.ads FOR SELECT USING (public.is_staff_or_above())';
    EXECUTE 'CREATE POLICY "staff_manage_ads" ON public.ads FOR ALL USING (public.is_staff_or_above())';
  END IF;
END $$;

-- ad_creatives: has client_id (via ad_account_id reference)
ALTER TABLE IF EXISTS public.ad_creatives ENABLE ROW LEVEL SECURITY;
DO $$ BEGIN
  IF EXISTS (SELECT 1 FROM pg_tables WHERE tablename = 'ad_creatives') THEN
    EXECUTE 'CREATE POLICY "staff_view_ad_creatives" ON public.ad_creatives FOR SELECT USING (public.is_staff_or_above())';
    EXECUTE 'CREATE POLICY "staff_manage_ad_creatives" ON public.ad_creatives FOR ALL USING (public.is_staff_or_above())';
  END IF;
END $$;

-- ad_metrics: child of ads (4 levels deep)
ALTER TABLE IF EXISTS public.ad_metrics ENABLE ROW LEVEL SECURITY;
DO $$ BEGIN
  IF EXISTS (SELECT 1 FROM pg_tables WHERE tablename = 'ad_metrics') THEN
    EXECUTE 'CREATE POLICY "staff_view_ad_metrics" ON public.ad_metrics FOR SELECT USING (public.is_staff_or_above())';
    EXECUTE 'CREATE POLICY "staff_manage_ad_metrics" ON public.ad_metrics FOR ALL USING (public.is_staff_or_above())';
  END IF;
END $$;
