-- ============================================================================
-- Migration 014: RLS Policies for Extended Feature Tables
-- Covers: Brand, AI, Automation, Partners/KB/Surveys, Additional Features
-- ============================================================================
-- NOTE: Tables are created by Drizzle `db:push`. This migration adds RLS only.
-- Run AFTER `pnpm db:push` has created the table structures.
-- ============================================================================

-- ===========================================
-- BRAND MANAGEMENT (10 tables)
-- ===========================================

-- brand_guides: has client_id (nullable)
ALTER TABLE IF EXISTS public.brand_guides ENABLE ROW LEVEL SECURITY;
DO $$ BEGIN
  IF EXISTS (SELECT 1 FROM pg_tables WHERE tablename = 'brand_guides') THEN
    EXECUTE 'CREATE POLICY "view_client_brand_guides" ON public.brand_guides FOR SELECT USING (
      client_id IS NULL
      OR client_id = public.get_current_user_client_id()
      OR public.is_admin_or_super_admin()
    )';
    EXECUTE 'CREATE POLICY "staff_manage_brand_guides" ON public.brand_guides FOR ALL USING (public.is_staff_or_above())';
  END IF;
END $$;

-- brand_guide_sections: child of brand_guides
ALTER TABLE IF EXISTS public.brand_guide_sections ENABLE ROW LEVEL SECURITY;
DO $$ BEGIN
  IF EXISTS (SELECT 1 FROM pg_tables WHERE tablename = 'brand_guide_sections') THEN
    EXECUTE 'CREATE POLICY "view_brand_sections" ON public.brand_guide_sections FOR SELECT USING (
      EXISTS (
        SELECT 1 FROM public.brand_guides bg
        WHERE bg.id = brand_guide_sections.brand_guide_id
        AND (bg.client_id IS NULL OR bg.client_id = public.get_current_user_client_id() OR public.is_admin_or_super_admin())
      )
    )';
    EXECUTE 'CREATE POLICY "staff_manage_brand_sections" ON public.brand_guide_sections FOR ALL USING (public.is_staff_or_above())';
  END IF;
END $$;

-- brand_colors: child of brand_guides
ALTER TABLE IF EXISTS public.brand_colors ENABLE ROW LEVEL SECURITY;
DO $$ BEGIN
  IF EXISTS (SELECT 1 FROM pg_tables WHERE tablename = 'brand_colors') THEN
    EXECUTE 'CREATE POLICY "view_brand_colors" ON public.brand_colors FOR SELECT USING (
      EXISTS (
        SELECT 1 FROM public.brand_guides bg
        WHERE bg.id = brand_colors.brand_guide_id
        AND (bg.client_id IS NULL OR bg.client_id = public.get_current_user_client_id() OR public.is_admin_or_super_admin())
      )
    )';
    EXECUTE 'CREATE POLICY "staff_manage_brand_colors" ON public.brand_colors FOR ALL USING (public.is_staff_or_above())';
  END IF;
END $$;

-- brand_fonts: child of brand_guides
ALTER TABLE IF EXISTS public.brand_fonts ENABLE ROW LEVEL SECURITY;
DO $$ BEGIN
  IF EXISTS (SELECT 1 FROM pg_tables WHERE tablename = 'brand_fonts') THEN
    EXECUTE 'CREATE POLICY "view_brand_fonts" ON public.brand_fonts FOR SELECT USING (
      EXISTS (
        SELECT 1 FROM public.brand_guides bg
        WHERE bg.id = brand_fonts.brand_guide_id
        AND (bg.client_id IS NULL OR bg.client_id = public.get_current_user_client_id() OR public.is_admin_or_super_admin())
      )
    )';
    EXECUTE 'CREATE POLICY "staff_manage_brand_fonts" ON public.brand_fonts FOR ALL USING (public.is_staff_or_above())';
  END IF;
END $$;

-- brand_templates: child of brand_guides
ALTER TABLE IF EXISTS public.brand_templates ENABLE ROW LEVEL SECURITY;
DO $$ BEGIN
  IF EXISTS (SELECT 1 FROM pg_tables WHERE tablename = 'brand_templates') THEN
    EXECUTE 'CREATE POLICY "view_brand_templates" ON public.brand_templates FOR SELECT USING (
      EXISTS (
        SELECT 1 FROM public.brand_guides bg
        WHERE bg.id = brand_templates.brand_guide_id
        AND (bg.client_id IS NULL OR bg.client_id = public.get_current_user_client_id() OR public.is_admin_or_super_admin())
      )
    )';
    EXECUTE 'CREATE POLICY "staff_manage_brand_templates" ON public.brand_templates FOR ALL USING (public.is_staff_or_above())';
  END IF;
END $$;

-- brand_assets: has client_id (nullable)
ALTER TABLE IF EXISTS public.brand_assets ENABLE ROW LEVEL SECURITY;
DO $$ BEGIN
  IF EXISTS (SELECT 1 FROM pg_tables WHERE tablename = 'brand_assets') THEN
    EXECUTE 'CREATE POLICY "view_client_brand_assets" ON public.brand_assets FOR SELECT USING (
      client_id IS NULL
      OR client_id = public.get_current_user_client_id()
      OR public.is_admin_or_super_admin()
    )';
    EXECUTE 'CREATE POLICY "staff_manage_brand_assets" ON public.brand_assets FOR ALL USING (public.is_staff_or_above())';
  END IF;
END $$;

-- brand_mentions: has client_id (nullable)
ALTER TABLE IF EXISTS public.brand_mentions ENABLE ROW LEVEL SECURITY;
DO $$ BEGIN
  IF EXISTS (SELECT 1 FROM pg_tables WHERE tablename = 'brand_mentions') THEN
    EXECUTE 'CREATE POLICY "view_client_mentions" ON public.brand_mentions FOR SELECT USING (
      client_id IS NULL
      OR client_id = public.get_current_user_client_id()
      OR public.is_admin_or_super_admin()
    )';
    EXECUTE 'CREATE POLICY "staff_manage_mentions" ON public.brand_mentions FOR ALL USING (public.is_staff_or_above())';
  END IF;
END $$;

-- brand_competitors: has client_id (nullable)
ALTER TABLE IF EXISTS public.brand_competitors ENABLE ROW LEVEL SECURITY;
DO $$ BEGIN
  IF EXISTS (SELECT 1 FROM pg_tables WHERE tablename = 'brand_competitors') THEN
    EXECUTE 'CREATE POLICY "view_client_competitors" ON public.brand_competitors FOR SELECT USING (
      client_id IS NULL
      OR client_id = public.get_current_user_client_id()
      OR public.is_admin_or_super_admin()
    )';
    EXECUTE 'CREATE POLICY "staff_manage_competitors" ON public.brand_competitors FOR ALL USING (public.is_staff_or_above())';
  END IF;
END $$;

-- brand_audits: has client_id (nullable)
ALTER TABLE IF EXISTS public.brand_audits ENABLE ROW LEVEL SECURITY;
DO $$ BEGIN
  IF EXISTS (SELECT 1 FROM pg_tables WHERE tablename = 'brand_audits') THEN
    EXECUTE 'CREATE POLICY "view_client_audits" ON public.brand_audits FOR SELECT USING (
      client_id IS NULL
      OR client_id = public.get_current_user_client_id()
      OR public.is_admin_or_super_admin()
    )';
    EXECUTE 'CREATE POLICY "staff_manage_audits" ON public.brand_audits FOR ALL USING (public.is_staff_or_above())';
  END IF;
END $$;

-- brand_inconsistencies: child of brand_audits
ALTER TABLE IF EXISTS public.brand_inconsistencies ENABLE ROW LEVEL SECURITY;
DO $$ BEGIN
  IF EXISTS (SELECT 1 FROM pg_tables WHERE tablename = 'brand_inconsistencies') THEN
    EXECUTE 'CREATE POLICY "view_inconsistencies" ON public.brand_inconsistencies FOR SELECT USING (
      EXISTS (
        SELECT 1 FROM public.brand_audits ba
        WHERE ba.id = brand_inconsistencies.brand_audit_id
        AND (ba.client_id IS NULL OR ba.client_id = public.get_current_user_client_id() OR public.is_admin_or_super_admin())
      )
    )';
    EXECUTE 'CREATE POLICY "staff_manage_inconsistencies" ON public.brand_inconsistencies FOR ALL USING (public.is_staff_or_above())';
  END IF;
END $$;

-- ===========================================
-- AI FEATURES (9 tables)
-- ===========================================

-- ai_conversations: has client_id + user_id
ALTER TABLE IF EXISTS public.ai_conversations ENABLE ROW LEVEL SECURITY;
DO $$ BEGIN
  IF EXISTS (SELECT 1 FROM pg_tables WHERE tablename = 'ai_conversations') THEN
    EXECUTE 'CREATE POLICY "view_own_ai_conversations" ON public.ai_conversations FOR SELECT USING (
      user_id = auth.uid()
      OR public.is_admin_or_super_admin()
    )';
    EXECUTE 'CREATE POLICY "create_ai_conversations" ON public.ai_conversations FOR INSERT WITH CHECK (user_id = auth.uid())';
    EXECUTE 'CREATE POLICY "manage_own_ai_conversations" ON public.ai_conversations FOR UPDATE USING (user_id = auth.uid())';
    EXECUTE 'CREATE POLICY "delete_own_ai_conversations" ON public.ai_conversations FOR DELETE USING (user_id = auth.uid() OR public.is_admin_or_super_admin())';
  END IF;
END $$;

-- ai_messages: child of ai_conversations
ALTER TABLE IF EXISTS public.ai_messages ENABLE ROW LEVEL SECURITY;
DO $$ BEGIN
  IF EXISTS (SELECT 1 FROM pg_tables WHERE tablename = 'ai_messages') THEN
    EXECUTE 'CREATE POLICY "view_own_ai_messages" ON public.ai_messages FOR SELECT USING (
      EXISTS (
        SELECT 1 FROM public.ai_conversations ac
        WHERE ac.id = ai_messages.conversation_id
        AND (ac.user_id = auth.uid() OR public.is_admin_or_super_admin())
      )
    )';
    EXECUTE 'CREATE POLICY "create_ai_messages" ON public.ai_messages FOR INSERT WITH CHECK (auth.uid() IS NOT NULL)';
  END IF;
END $$;

-- ai_message_feedback: child of ai_messages
ALTER TABLE IF EXISTS public.ai_message_feedback ENABLE ROW LEVEL SECURITY;
DO $$ BEGIN
  IF EXISTS (SELECT 1 FROM pg_tables WHERE tablename = 'ai_message_feedback') THEN
    EXECUTE 'CREATE POLICY "view_own_ai_feedback" ON public.ai_message_feedback FOR SELECT USING (user_id = auth.uid() OR public.is_admin_or_super_admin())';
    EXECUTE 'CREATE POLICY "create_ai_feedback" ON public.ai_message_feedback FOR INSERT WITH CHECK (user_id = auth.uid())';
  END IF;
END $$;

-- ai_tasks: has client_id + user_id
ALTER TABLE IF EXISTS public.ai_tasks ENABLE ROW LEVEL SECURITY;
DO $$ BEGIN
  IF EXISTS (SELECT 1 FROM pg_tables WHERE tablename = 'ai_tasks') THEN
    EXECUTE 'CREATE POLICY "view_own_ai_tasks" ON public.ai_tasks FOR SELECT USING (
      user_id = auth.uid()
      OR client_id = public.get_current_user_client_id()
      OR public.is_admin_or_super_admin()
    )';
    EXECUTE 'CREATE POLICY "create_ai_tasks" ON public.ai_tasks FOR INSERT WITH CHECK (user_id = auth.uid())';
    EXECUTE 'CREATE POLICY "manage_own_ai_tasks" ON public.ai_tasks FOR UPDATE USING (user_id = auth.uid() OR public.is_admin_or_super_admin())';
  END IF;
END $$;

-- ai_workflows: has client_id
ALTER TABLE IF EXISTS public.ai_workflows ENABLE ROW LEVEL SECURITY;
DO $$ BEGIN
  IF EXISTS (SELECT 1 FROM pg_tables WHERE tablename = 'ai_workflows') THEN
    EXECUTE 'CREATE POLICY "view_client_ai_workflows" ON public.ai_workflows FOR SELECT USING (
      client_id = public.get_current_user_client_id()
      OR public.is_admin_or_super_admin()
    )';
    EXECUTE 'CREATE POLICY "staff_manage_ai_workflows" ON public.ai_workflows FOR ALL USING (public.is_staff_or_above())';
  END IF;
END $$;

-- ai_providers: system-wide config (no client isolation)
ALTER TABLE IF EXISTS public.ai_providers ENABLE ROW LEVEL SECURITY;
DO $$ BEGIN
  IF EXISTS (SELECT 1 FROM pg_tables WHERE tablename = 'ai_providers') THEN
    EXECUTE 'CREATE POLICY "view_ai_providers" ON public.ai_providers FOR SELECT USING (auth.uid() IS NOT NULL)';
    EXECUTE 'CREATE POLICY "admin_manage_ai_providers" ON public.ai_providers FOR ALL USING (public.is_admin_or_super_admin())';
  END IF;
END $$;

-- ai_usage_tracking: has client_id + user_id
ALTER TABLE IF EXISTS public.ai_usage_tracking ENABLE ROW LEVEL SECURITY;
DO $$ BEGIN
  IF EXISTS (SELECT 1 FROM pg_tables WHERE tablename = 'ai_usage_tracking') THEN
    EXECUTE 'CREATE POLICY "view_own_ai_usage" ON public.ai_usage_tracking FOR SELECT USING (
      user_id = auth.uid()
      OR client_id = public.get_current_user_client_id()
      OR public.is_admin_or_super_admin()
    )';
    EXECUTE 'CREATE POLICY "insert_ai_usage" ON public.ai_usage_tracking FOR INSERT WITH CHECK (auth.uid() IS NOT NULL)';
  END IF;
END $$;

-- ai_insight_reports: has client_id
ALTER TABLE IF EXISTS public.ai_insight_reports ENABLE ROW LEVEL SECURITY;
DO $$ BEGIN
  IF EXISTS (SELECT 1 FROM pg_tables WHERE tablename = 'ai_insight_reports') THEN
    EXECUTE 'CREATE POLICY "view_client_ai_reports" ON public.ai_insight_reports FOR SELECT USING (
      client_id = public.get_current_user_client_id()
      OR public.is_admin_or_super_admin()
    )';
    EXECUTE 'CREATE POLICY "admin_manage_ai_reports" ON public.ai_insight_reports FOR ALL USING (public.is_admin_or_super_admin())';
  END IF;
END $$;

-- prompt_templates: system-wide (no client_id)
ALTER TABLE IF EXISTS public.prompt_templates ENABLE ROW LEVEL SECURITY;
DO $$ BEGIN
  IF EXISTS (SELECT 1 FROM pg_tables WHERE tablename = 'prompt_templates') THEN
    EXECUTE 'CREATE POLICY "view_prompt_templates" ON public.prompt_templates FOR SELECT USING (auth.uid() IS NOT NULL)';
    EXECUTE 'CREATE POLICY "staff_manage_prompts" ON public.prompt_templates FOR ALL USING (public.is_staff_or_above())';
  END IF;
END $$;

-- ===========================================
-- AUTOMATION & REPORTING (7 tables)
-- ===========================================

-- automation_rules: system-wide (no client_id)
ALTER TABLE IF EXISTS public.automation_rules ENABLE ROW LEVEL SECURITY;
DO $$ BEGIN
  IF EXISTS (SELECT 1 FROM pg_tables WHERE tablename = 'automation_rules') THEN
    EXECUTE 'CREATE POLICY "staff_view_rules" ON public.automation_rules FOR SELECT USING (public.is_staff_or_above())';
    EXECUTE 'CREATE POLICY "admin_manage_rules" ON public.automation_rules FOR ALL USING (public.is_admin_or_super_admin())';
  END IF;
END $$;

-- automation_runs: has client_id
ALTER TABLE IF EXISTS public.automation_runs ENABLE ROW LEVEL SECURITY;
DO $$ BEGIN
  IF EXISTS (SELECT 1 FROM pg_tables WHERE tablename = 'automation_runs') THEN
    EXECUTE 'CREATE POLICY "view_client_runs" ON public.automation_runs FOR SELECT USING (
      client_id = public.get_current_user_client_id()
      OR public.is_admin_or_super_admin()
    )';
    EXECUTE 'CREATE POLICY "system_manage_runs" ON public.automation_runs FOR ALL USING (public.is_admin_or_super_admin())';
  END IF;
END $$;

-- automation_logs: child of automation_rules
ALTER TABLE IF EXISTS public.automation_logs ENABLE ROW LEVEL SECURITY;
DO $$ BEGIN
  IF EXISTS (SELECT 1 FROM pg_tables WHERE tablename = 'automation_logs') THEN
    EXECUTE 'CREATE POLICY "staff_view_logs" ON public.automation_logs FOR SELECT USING (public.is_staff_or_above())';
    EXECUTE 'CREATE POLICY "admin_manage_logs" ON public.automation_logs FOR ALL USING (public.is_admin_or_super_admin())';
  END IF;
END $$;

-- report_templates: system-wide
ALTER TABLE IF EXISTS public.report_templates ENABLE ROW LEVEL SECURITY;
DO $$ BEGIN
  IF EXISTS (SELECT 1 FROM pg_tables WHERE tablename = 'report_templates') THEN
    EXECUTE 'CREATE POLICY "staff_view_report_templates" ON public.report_templates FOR SELECT USING (public.is_staff_or_above())';
    EXECUTE 'CREATE POLICY "admin_manage_report_templates" ON public.report_templates FOR ALL USING (public.is_admin_or_super_admin())';
  END IF;
END $$;

-- report_schedules: has client_id
ALTER TABLE IF EXISTS public.report_schedules ENABLE ROW LEVEL SECURITY;
DO $$ BEGIN
  IF EXISTS (SELECT 1 FROM pg_tables WHERE tablename = 'report_schedules') THEN
    EXECUTE 'CREATE POLICY "view_client_schedules" ON public.report_schedules FOR SELECT USING (
      client_id = public.get_current_user_client_id()
      OR public.is_admin_or_super_admin()
    )';
    EXECUTE 'CREATE POLICY "staff_manage_schedules" ON public.report_schedules FOR ALL USING (public.is_staff_or_above())';
  END IF;
END $$;

-- report_deliveries: has client_id
ALTER TABLE IF EXISTS public.report_deliveries ENABLE ROW LEVEL SECURITY;
DO $$ BEGIN
  IF EXISTS (SELECT 1 FROM pg_tables WHERE tablename = 'report_deliveries') THEN
    EXECUTE 'CREATE POLICY "view_client_deliveries" ON public.report_deliveries FOR SELECT USING (
      client_id = public.get_current_user_client_id()
      OR public.is_admin_or_super_admin()
    )';
    EXECUTE 'CREATE POLICY "admin_manage_deliveries" ON public.report_deliveries FOR ALL USING (public.is_admin_or_super_admin())';
  END IF;
END $$;

-- custom_dashboards: has client_id + user_id
ALTER TABLE IF EXISTS public.custom_dashboards ENABLE ROW LEVEL SECURITY;
DO $$ BEGIN
  IF EXISTS (SELECT 1 FROM pg_tables WHERE tablename = 'custom_dashboards') THEN
    EXECUTE 'CREATE POLICY "view_own_dashboards" ON public.custom_dashboards FOR SELECT USING (
      user_id = auth.uid()
      OR client_id = public.get_current_user_client_id()
      OR public.is_admin_or_super_admin()
    )';
    EXECUTE 'CREATE POLICY "manage_own_dashboards" ON public.custom_dashboards FOR INSERT WITH CHECK (user_id = auth.uid())';
    EXECUTE 'CREATE POLICY "update_own_dashboards" ON public.custom_dashboards FOR UPDATE USING (user_id = auth.uid() OR public.is_admin_or_super_admin())';
    EXECUTE 'CREATE POLICY "delete_own_dashboards" ON public.custom_dashboards FOR DELETE USING (user_id = auth.uid() OR public.is_admin_or_super_admin())';
  END IF;
END $$;

-- ===========================================
-- PARTNERS & REFERRALS (2 tables)
-- ===========================================

-- partners: system-wide (no client isolation)
ALTER TABLE IF EXISTS public.partners ENABLE ROW LEVEL SECURITY;
DO $$ BEGIN
  IF EXISTS (SELECT 1 FROM pg_tables WHERE tablename = 'partners') THEN
    EXECUTE 'CREATE POLICY "staff_view_partners" ON public.partners FOR SELECT USING (public.is_staff_or_above())';
    EXECUTE 'CREATE POLICY "admin_manage_partners" ON public.partners FOR ALL USING (public.is_admin_or_super_admin())';
  END IF;
END $$;

-- referrals: has client_id
ALTER TABLE IF EXISTS public.referrals ENABLE ROW LEVEL SECURITY;
DO $$ BEGIN
  IF EXISTS (SELECT 1 FROM pg_tables WHERE tablename = 'referrals') THEN
    EXECUTE 'CREATE POLICY "view_client_referrals" ON public.referrals FOR SELECT USING (
      client_id = public.get_current_user_client_id()
      OR public.is_admin_or_super_admin()
    )';
    EXECUTE 'CREATE POLICY "admin_manage_referrals" ON public.referrals FOR ALL USING (public.is_admin_or_super_admin())';
  END IF;
END $$;

-- ===========================================
-- KNOWLEDGE BASE (6 tables)
-- ===========================================

-- knowledge_base_categories: system-wide (hierarchical)
ALTER TABLE IF EXISTS public.knowledge_base_categories ENABLE ROW LEVEL SECURITY;
DO $$ BEGIN
  IF EXISTS (SELECT 1 FROM pg_tables WHERE tablename = 'knowledge_base_categories') THEN
    EXECUTE 'CREATE POLICY "public_view_kb_categories" ON public.knowledge_base_categories FOR SELECT USING (true)';
    EXECUTE 'CREATE POLICY "admin_manage_kb_categories" ON public.knowledge_base_categories FOR ALL USING (public.is_admin_or_super_admin())';
  END IF;
END $$;

-- knowledge_base_articles: system-wide
ALTER TABLE IF EXISTS public.knowledge_base_articles ENABLE ROW LEVEL SECURITY;
DO $$ BEGIN
  IF EXISTS (SELECT 1 FROM pg_tables WHERE tablename = 'knowledge_base_articles') THEN
    EXECUTE 'CREATE POLICY "public_view_kb_articles" ON public.knowledge_base_articles FOR SELECT USING (true)';
    EXECUTE 'CREATE POLICY "staff_manage_kb_articles" ON public.knowledge_base_articles FOR ALL USING (public.is_staff_or_above())';
  END IF;
END $$;

-- knowledge_base_feedback: user-scoped
ALTER TABLE IF EXISTS public.knowledge_base_feedback ENABLE ROW LEVEL SECURITY;
DO $$ BEGIN
  IF EXISTS (SELECT 1 FROM pg_tables WHERE tablename = 'knowledge_base_feedback') THEN
    EXECUTE 'CREATE POLICY "view_own_kb_feedback" ON public.knowledge_base_feedback FOR SELECT USING (user_id = auth.uid() OR public.is_admin_or_super_admin())';
    EXECUTE 'CREATE POLICY "create_kb_feedback" ON public.knowledge_base_feedback FOR INSERT WITH CHECK (auth.uid() IS NOT NULL)';
  END IF;
END $$;

-- staff_guide_categories: system-wide
ALTER TABLE IF EXISTS public.staff_guide_categories ENABLE ROW LEVEL SECURITY;
DO $$ BEGIN
  IF EXISTS (SELECT 1 FROM pg_tables WHERE tablename = 'staff_guide_categories') THEN
    EXECUTE 'CREATE POLICY "staff_view_guide_categories" ON public.staff_guide_categories FOR SELECT USING (public.is_staff_or_above())';
    EXECUTE 'CREATE POLICY "admin_manage_guide_categories" ON public.staff_guide_categories FOR ALL USING (public.is_admin_or_super_admin())';
  END IF;
END $$;

-- staff_guides: system-wide
ALTER TABLE IF EXISTS public.staff_guides ENABLE ROW LEVEL SECURITY;
DO $$ BEGIN
  IF EXISTS (SELECT 1 FROM pg_tables WHERE tablename = 'staff_guides') THEN
    EXECUTE 'CREATE POLICY "staff_view_guides" ON public.staff_guides FOR SELECT USING (public.is_staff_or_above())';
    EXECUTE 'CREATE POLICY "admin_manage_guides" ON public.staff_guides FOR ALL USING (public.is_admin_or_super_admin())';
  END IF;
END $$;

-- staff_guide_views: user-scoped tracking
ALTER TABLE IF EXISTS public.staff_guide_views ENABLE ROW LEVEL SECURITY;
DO $$ BEGIN
  IF EXISTS (SELECT 1 FROM pg_tables WHERE tablename = 'staff_guide_views') THEN
    EXECUTE 'CREATE POLICY "view_own_guide_views" ON public.staff_guide_views FOR SELECT USING (user_id = auth.uid() OR public.is_admin_or_super_admin())';
    EXECUTE 'CREATE POLICY "track_guide_views" ON public.staff_guide_views FOR INSERT WITH CHECK (user_id = auth.uid())';
  END IF;
END $$;

-- ===========================================
-- SURVEYS (4 tables)
-- ===========================================

-- surveys: has client_id
ALTER TABLE IF EXISTS public.surveys ENABLE ROW LEVEL SECURITY;
DO $$ BEGIN
  IF EXISTS (SELECT 1 FROM pg_tables WHERE tablename = 'surveys') THEN
    EXECUTE 'CREATE POLICY "view_client_surveys" ON public.surveys FOR SELECT USING (
      client_id = public.get_current_user_client_id()
      OR public.is_admin_or_super_admin()
    )';
    EXECUTE 'CREATE POLICY "staff_manage_surveys" ON public.surveys FOR ALL USING (public.is_staff_or_above())';
  END IF;
END $$;

-- survey_questions: child of surveys
ALTER TABLE IF EXISTS public.survey_questions ENABLE ROW LEVEL SECURITY;
DO $$ BEGIN
  IF EXISTS (SELECT 1 FROM pg_tables WHERE tablename = 'survey_questions') THEN
    EXECUTE 'CREATE POLICY "view_survey_questions" ON public.survey_questions FOR SELECT USING (
      EXISTS (
        SELECT 1 FROM public.surveys s
        WHERE s.id = survey_questions.survey_id
        AND (s.client_id = public.get_current_user_client_id() OR public.is_admin_or_super_admin())
      )
    )';
    EXECUTE 'CREATE POLICY "staff_manage_questions" ON public.survey_questions FOR ALL USING (public.is_staff_or_above())';
  END IF;
END $$;

-- survey_responses: has client_id
ALTER TABLE IF EXISTS public.survey_responses ENABLE ROW LEVEL SECURITY;
DO $$ BEGIN
  IF EXISTS (SELECT 1 FROM pg_tables WHERE tablename = 'survey_responses') THEN
    EXECUTE 'CREATE POLICY "view_own_responses" ON public.survey_responses FOR SELECT USING (
      respondent_id = auth.uid()
      OR client_id = public.get_current_user_client_id()
      OR public.is_admin_or_super_admin()
    )';
    EXECUTE 'CREATE POLICY "submit_responses" ON public.survey_responses FOR INSERT WITH CHECK (auth.uid() IS NOT NULL)';
  END IF;
END $$;

-- survey_answers: child of survey_responses
ALTER TABLE IF EXISTS public.survey_answers ENABLE ROW LEVEL SECURITY;
DO $$ BEGIN
  IF EXISTS (SELECT 1 FROM pg_tables WHERE tablename = 'survey_answers') THEN
    EXECUTE 'CREATE POLICY "view_own_answers" ON public.survey_answers FOR SELECT USING (
      EXISTS (
        SELECT 1 FROM public.survey_responses sr
        WHERE sr.id = survey_answers.response_id
        AND (sr.respondent_id = auth.uid() OR public.is_admin_or_super_admin())
      )
    )';
    EXECUTE 'CREATE POLICY "submit_answers" ON public.survey_answers FOR INSERT WITH CHECK (auth.uid() IS NOT NULL)';
  END IF;
END $$;

-- ===========================================
-- ADDITIONAL FEATURES (9 tables)
-- ===========================================

-- account_health: has client_id
ALTER TABLE IF EXISTS public.account_health ENABLE ROW LEVEL SECURITY;
DO $$ BEGIN
  IF EXISTS (SELECT 1 FROM pg_tables WHERE tablename = 'account_health') THEN
    EXECUTE 'CREATE POLICY "view_client_health" ON public.account_health FOR SELECT USING (
      client_id = public.get_current_user_client_id()
      OR public.is_admin_or_super_admin()
    )';
    EXECUTE 'CREATE POLICY "admin_manage_health" ON public.account_health FOR ALL USING (public.is_admin_or_super_admin())';
  END IF;
END $$;

-- client_health_snapshots: has client_id
ALTER TABLE IF EXISTS public.client_health_snapshots ENABLE ROW LEVEL SECURITY;
DO $$ BEGIN
  IF EXISTS (SELECT 1 FROM pg_tables WHERE tablename = 'client_health_snapshots') THEN
    EXECUTE 'CREATE POLICY "view_client_snapshots" ON public.client_health_snapshots FOR SELECT USING (
      client_id = public.get_current_user_client_id()
      OR public.is_admin_or_super_admin()
    )';
    EXECUTE 'CREATE POLICY "admin_manage_snapshots" ON public.client_health_snapshots FOR ALL USING (public.is_admin_or_super_admin())';
  END IF;
END $$;

-- storage_connections: has client_id
ALTER TABLE IF EXISTS public.storage_connections ENABLE ROW LEVEL SECURITY;
DO $$ BEGIN
  IF EXISTS (SELECT 1 FROM pg_tables WHERE tablename = 'storage_connections') THEN
    EXECUTE 'CREATE POLICY "view_client_storage" ON public.storage_connections FOR SELECT USING (
      client_id = public.get_current_user_client_id()
      OR public.is_admin_or_super_admin()
    )';
    EXECUTE 'CREATE POLICY "admin_manage_storage" ON public.storage_connections FOR ALL USING (public.is_admin_or_super_admin())';
  END IF;
END $$;

-- storage_files: child of storage_connections
ALTER TABLE IF EXISTS public.storage_files ENABLE ROW LEVEL SECURITY;
DO $$ BEGIN
  IF EXISTS (SELECT 1 FROM pg_tables WHERE tablename = 'storage_files') THEN
    EXECUTE 'CREATE POLICY "view_storage_files" ON public.storage_files FOR SELECT USING (
      EXISTS (
        SELECT 1 FROM public.storage_connections sc
        WHERE sc.id = storage_files.connection_id
        AND (sc.client_id = public.get_current_user_client_id() OR public.is_admin_or_super_admin())
      )
    )';
    EXECUTE 'CREATE POLICY "staff_manage_storage_files" ON public.storage_files FOR ALL USING (public.is_staff_or_above())';
  END IF;
END $$;

-- data_privacy_requests: has client_id + user_id
ALTER TABLE IF EXISTS public.data_privacy_requests ENABLE ROW LEVEL SECURITY;
DO $$ BEGIN
  IF EXISTS (SELECT 1 FROM pg_tables WHERE tablename = 'data_privacy_requests') THEN
    EXECUTE 'CREATE POLICY "view_own_privacy_requests" ON public.data_privacy_requests FOR SELECT USING (
      user_id = auth.uid()
      OR client_id = public.get_current_user_client_id()
      OR public.is_admin_or_super_admin()
    )';
    EXECUTE 'CREATE POLICY "create_privacy_requests" ON public.data_privacy_requests FOR INSERT WITH CHECK (user_id = auth.uid())';
    EXECUTE 'CREATE POLICY "admin_manage_privacy" ON public.data_privacy_requests FOR UPDATE USING (public.is_admin_or_super_admin())';
  END IF;
END $$;

-- white_label_configs: has client_id (unique per client)
ALTER TABLE IF EXISTS public.white_label_configs ENABLE ROW LEVEL SECURITY;
DO $$ BEGIN
  IF EXISTS (SELECT 1 FROM pg_tables WHERE tablename = 'white_label_configs') THEN
    EXECUTE 'CREATE POLICY "view_own_white_label" ON public.white_label_configs FOR SELECT USING (
      client_id = public.get_current_user_client_id()
      OR public.is_admin_or_super_admin()
    )';
    EXECUTE 'CREATE POLICY "admin_manage_white_label" ON public.white_label_configs FOR ALL USING (public.is_admin_or_super_admin())';
  END IF;
END $$;

-- form_templates: has client_id (nullable for global templates)
ALTER TABLE IF EXISTS public.form_templates ENABLE ROW LEVEL SECURITY;
DO $$ BEGIN
  IF EXISTS (SELECT 1 FROM pg_tables WHERE tablename = 'form_templates') THEN
    EXECUTE 'CREATE POLICY "view_form_templates" ON public.form_templates FOR SELECT USING (
      client_id IS NULL
      OR client_id = public.get_current_user_client_id()
      OR public.is_admin_or_super_admin()
    )';
    EXECUTE 'CREATE POLICY "staff_manage_form_templates" ON public.form_templates FOR ALL USING (public.is_staff_or_above())';
  END IF;
END $$;

-- webhook_endpoints: has client_id
ALTER TABLE IF EXISTS public.webhook_endpoints ENABLE ROW LEVEL SECURITY;
DO $$ BEGIN
  IF EXISTS (SELECT 1 FROM pg_tables WHERE tablename = 'webhook_endpoints') THEN
    EXECUTE 'CREATE POLICY "view_client_webhooks" ON public.webhook_endpoints FOR SELECT USING (
      client_id = public.get_current_user_client_id()
      OR public.is_admin_or_super_admin()
    )';
    EXECUTE 'CREATE POLICY "admin_manage_webhooks" ON public.webhook_endpoints FOR ALL USING (public.is_admin_or_super_admin())';
  END IF;
END $$;

-- webhook_deliveries: child of webhook_endpoints
ALTER TABLE IF EXISTS public.webhook_deliveries ENABLE ROW LEVEL SECURITY;
DO $$ BEGIN
  IF EXISTS (SELECT 1 FROM pg_tables WHERE tablename = 'webhook_deliveries') THEN
    EXECUTE 'CREATE POLICY "view_webhook_deliveries" ON public.webhook_deliveries FOR SELECT USING (
      EXISTS (
        SELECT 1 FROM public.webhook_endpoints we
        WHERE we.id = webhook_deliveries.endpoint_id
        AND (we.client_id = public.get_current_user_client_id() OR public.is_admin_or_super_admin())
      )
    )';
    EXECUTE 'CREATE POLICY "admin_manage_deliveries" ON public.webhook_deliveries FOR ALL USING (public.is_admin_or_super_admin())';
  END IF;
END $$;
