--
-- PostgreSQL database dump
--


-- Dumped from database version 17.6
-- Dumped by pg_dump version 17.6

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET transaction_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

--
-- Name: public; Type: SCHEMA; Schema: -; Owner: -
--



--
-- Name: SCHEMA public; Type: COMMENT; Schema: -; Owner: -
--

COMMENT ON SCHEMA public IS 'standard public schema';


--
-- Name: ai_message_role; Type: TYPE; Schema: public; Owner: -
--

CREATE TYPE public.ai_message_role AS ENUM (
    'user',
    'assistant',
    'system',
    'tool'
);


--
-- Name: ai_prompt_category; Type: TYPE; Schema: public; Owner: -
--

CREATE TYPE public.ai_prompt_category AS ENUM (
    'general',
    'customer_service',
    'content_creation',
    'code_generation',
    'data_analysis',
    'custom'
);


--
-- Name: ai_provider_type; Type: TYPE; Schema: public; Owner: -
--

CREATE TYPE public.ai_provider_type AS ENUM (
    'openai',
    'anthropic',
    'google',
    'azure',
    'local',
    'custom'
);


--
-- Name: ai_task_status; Type: TYPE; Schema: public; Owner: -
--

CREATE TYPE public.ai_task_status AS ENUM (
    'pending',
    'processing',
    'completed',
    'failed',
    'cancelled'
);


--
-- Name: ai_task_type; Type: TYPE; Schema: public; Owner: -
--

CREATE TYPE public.ai_task_type AS ENUM (
    'text_generation',
    'code_generation',
    'analysis',
    'summarization',
    'translation',
    'qa',
    'custom'
);


--
-- Name: ai_trigger_type; Type: TYPE; Schema: public; Owner: -
--

CREATE TYPE public.ai_trigger_type AS ENUM (
    'manual',
    'scheduled',
    'event',
    'webhook',
    'api'
);


--
-- Name: generate_contract_number(); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.generate_contract_number() RETURNS text
    LANGUAGE plpgsql
    AS $$
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
$$;


--
-- Name: get_current_user_client_id(); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.get_current_user_client_id() RETURNS uuid
    LANGUAGE sql STABLE SECURITY DEFINER
    AS $$
  SELECT client_id FROM public.users WHERE id = auth.uid() LIMIT 1;
$$;


--
-- Name: FUNCTION get_current_user_client_id(); Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON FUNCTION public.get_current_user_client_id() IS 'Helper function to get current user client_id without RLS recursion';


--
-- Name: get_user_permissions(uuid); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.get_user_permissions(p_user_id uuid) RETURNS TABLE(permission_name text, resource text, action text)
    LANGUAGE plpgsql SECURITY DEFINER
    AS $$
BEGIN
  RETURN QUERY
  SELECT DISTINCT p.name, p.resource, p.action
  FROM user_roles ur
  JOIN role_permissions rp ON ur.role_id = rp.role_id
  JOIN permissions p ON rp.permission_id = p.id
  WHERE ur.user_id = p_user_id;
END;
$$;


--
-- Name: get_user_roles(uuid); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.get_user_roles(p_user_id uuid) RETURNS TABLE(role_id uuid, role_name text, role_description text)
    LANGUAGE plpgsql SECURITY DEFINER
    AS $$
BEGIN
  RETURN QUERY
  SELECT r.id, r.name, r.description
  FROM user_roles ur
  JOIN roles r ON ur.role_id = r.id
  WHERE ur.user_id = p_user_id;
END;
$$;


--
-- Name: handle_document_version(); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.handle_document_version() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
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
$$;


--
-- Name: handle_new_user(); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.handle_new_user() RETURNS trigger
    LANGUAGE plpgsql SECURITY DEFINER
    AS $$
BEGIN
  INSERT INTO public.users (id, name, email, client_id, is_super_admin)
  VALUES (
    NEW.id,
    COALESCE(NEW.raw_user_meta_data->>'name', NEW.email),
    NEW.email,
    (NEW.raw_user_meta_data->>'client_id')::uuid,
    COALESCE((NEW.raw_user_meta_data->>'is_super_admin')::boolean, false)
  )
  ON CONFLICT (id) DO UPDATE
  SET
    email = EXCLUDED.email,
    updated_at = NOW();
  
  RETURN NEW;
END;
$$;


--
-- Name: FUNCTION handle_new_user(); Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON FUNCTION public.handle_new_user() IS 'Trigger function to auto-create public.users profile when auth.users record is created';


--
-- Name: is_admin_or_super_admin(); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.is_admin_or_super_admin() RETURNS boolean
    LANGUAGE sql STABLE SECURITY DEFINER
    AS $$
  SELECT EXISTS (
    SELECT 1 FROM public.user_roles ur
    JOIN public.roles r ON ur.role_id = r.id
    WHERE ur.user_id = auth.uid()
    AND r.name IN ('super_admin', 'admin')
  );
$$;


--
-- Name: is_staff_for_client(uuid, uuid); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.is_staff_for_client(p_user_id uuid, p_client_id uuid) RETURNS boolean
    LANGUAGE sql STABLE SECURITY DEFINER
    AS $$
  SELECT EXISTS (
    SELECT 1 FROM public.staff_assignments
    WHERE user_id = p_user_id AND client_id = p_client_id
  );
$$;


--
-- Name: is_staff_or_above(); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.is_staff_or_above() RETURNS boolean
    LANGUAGE sql STABLE SECURITY DEFINER
    AS $$
  SELECT EXISTS (
    SELECT 1 FROM public.user_roles ur
    JOIN public.roles r ON ur.role_id = r.id
    WHERE ur.user_id = auth.uid()
    AND r.name IN ('super_admin', 'admin', 'account_manager', 'staff')
  );
$$;


--
-- Name: update_announcements_updated_at(); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.update_announcements_updated_at() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
  NEW.updated_at = NOW();
  RETURN NEW;
END;
$$;


--
-- Name: update_application_tables_updated_at(); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.update_application_tables_updated_at() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
  NEW.updated_at = NOW();
  RETURN NEW;
END;
$$;


--
-- Name: update_core_tables_updated_at(); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.update_core_tables_updated_at() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
  NEW.updated_at = NOW();
  RETURN NEW;
END;
$$;


--
-- Name: update_support_tickets_updated_at(); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.update_support_tickets_updated_at() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
  NEW.updated_at = NOW();
  RETURN NEW;
END;
$$;


--
-- Name: update_updated_at_column(); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.update_updated_at_column() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
  NEW.updated_at = now();
  RETURN NEW;
END;
$$;


--
-- Name: user_has_permission(uuid, text); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.user_has_permission(p_user_id uuid, p_permission_name text) RETURNS boolean
    LANGUAGE plpgsql SECURITY DEFINER
    AS $$
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
$$;


--
-- Name: user_has_role(uuid, text); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.user_has_role(p_user_id uuid, p_role_name text) RETURNS boolean
    LANGUAGE plpgsql SECURITY DEFINER
    AS $$
BEGIN
  RETURN EXISTS (
    SELECT 1
    FROM user_roles ur
    JOIN roles r ON ur.role_id = r.id
    WHERE ur.user_id = p_user_id
      AND r.name = p_role_name
  );
END;
$$;


SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- Name: account_health; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.account_health (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    client_id uuid NOT NULL,
    score real NOT NULL,
    factors jsonb NOT NULL,
    last_interaction timestamp with time zone,
    revenue_trend text,
    satisfaction_score real,
    risk_level text NOT NULL,
    recommendations jsonb,
    calculated_at timestamp with time zone NOT NULL,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL
);


--
-- Name: activity_logs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.activity_logs (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    client_id uuid,
    causer_id uuid,
    subject_type text NOT NULL,
    subject_id uuid,
    description text NOT NULL,
    properties jsonb DEFAULT '{}'::jsonb,
    created_at timestamp with time zone DEFAULT now() NOT NULL
);


--
-- Name: ad_accounts; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.ad_accounts (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    client_id uuid NOT NULL,
    platform text NOT NULL,
    account_id text NOT NULL,
    account_name text NOT NULL,
    currency text DEFAULT 'USD'::text NOT NULL,
    timezone text DEFAULT 'America/New_York'::text NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    metadata jsonb,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL,
    deleted_at timestamp with time zone
);


--
-- Name: ad_campaigns; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.ad_campaigns (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    ad_account_id uuid NOT NULL,
    campaign_id text NOT NULL,
    name text NOT NULL,
    objective text NOT NULL,
    status text DEFAULT 'active'::text NOT NULL,
    daily_budget numeric(10,2),
    lifetime_budget numeric(10,2),
    start_date date,
    end_date date,
    metadata jsonb,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL,
    deleted_at timestamp with time zone
);


--
-- Name: ad_creatives; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.ad_creatives (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    ad_account_id uuid NOT NULL,
    creative_id text,
    name text NOT NULL,
    asset_urls jsonb,
    headline text,
    description text,
    call_to_action text,
    metadata jsonb,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL,
    deleted_at timestamp with time zone
);


--
-- Name: ad_metrics; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.ad_metrics (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    ad_id uuid NOT NULL,
    metric_date date NOT NULL,
    impressions numeric(12,0) DEFAULT 0 NOT NULL,
    clicks numeric(12,0) DEFAULT 0 NOT NULL,
    spend numeric(10,2) DEFAULT 0 NOT NULL,
    conversions numeric(10,2) DEFAULT 0 NOT NULL,
    ctr numeric(6,4) DEFAULT 0 NOT NULL,
    cpc numeric(10,4) DEFAULT 0 NOT NULL,
    cpm numeric(10,4) DEFAULT 0 NOT NULL,
    roas numeric(10,4) DEFAULT 0 NOT NULL,
    metadata jsonb,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL
);


--
-- Name: ad_sets; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.ad_sets (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    campaign_id uuid NOT NULL,
    ad_set_id text NOT NULL,
    name text NOT NULL,
    targeting jsonb,
    bid_amount numeric(10,4),
    optimization_goal text,
    status text DEFAULT 'active'::text NOT NULL,
    metadata jsonb,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL,
    deleted_at timestamp with time zone
);


--
-- Name: ads; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.ads (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    ad_set_id uuid NOT NULL,
    ad_id text NOT NULL,
    name text NOT NULL,
    creative_id uuid,
    status text DEFAULT 'active'::text NOT NULL,
    metadata jsonb,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL,
    deleted_at timestamp with time zone
);


--
-- Name: ai_conversations; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.ai_conversations (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    user_id uuid NOT NULL,
    client_id uuid,
    title text NOT NULL,
    context jsonb,
    metadata jsonb,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL
);


--
-- Name: ai_insight_reports; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.ai_insight_reports (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    client_id uuid,
    report_type text NOT NULL,
    generated_at timestamp with time zone DEFAULT now() NOT NULL,
    data jsonb NOT NULL,
    metadata jsonb
);


--
-- Name: ai_message_feedback; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.ai_message_feedback (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    message_id uuid NOT NULL,
    user_id uuid NOT NULL,
    rating integer,
    feedback_type text,
    comment text,
    metadata jsonb,
    created_at timestamp with time zone DEFAULT now() NOT NULL
);


--
-- Name: ai_messages; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.ai_messages (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    conversation_id uuid NOT NULL,
    role public.ai_message_role NOT NULL,
    content text NOT NULL,
    tokens_used integer DEFAULT 0,
    model text,
    cost numeric(10,6),
    metadata jsonb,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL
);


--
-- Name: ai_providers; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.ai_providers (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    name text NOT NULL,
    provider_type public.ai_provider_type NOT NULL,
    api_key_encrypted text,
    config jsonb,
    is_active boolean DEFAULT true NOT NULL,
    metadata jsonb,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL
);


--
-- Name: ai_tasks; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.ai_tasks (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    client_id uuid,
    user_id uuid,
    task_type public.ai_task_type NOT NULL,
    input jsonb NOT NULL,
    output jsonb,
    status public.ai_task_status DEFAULT 'pending'::public.ai_task_status NOT NULL,
    started_at timestamp with time zone,
    completed_at timestamp with time zone,
    error text,
    metadata jsonb,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL
);


--
-- Name: ai_usage_tracking; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.ai_usage_tracking (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    user_id uuid,
    client_id uuid,
    provider text NOT NULL,
    model text NOT NULL,
    tokens_used integer NOT NULL,
    cost numeric(10,6) NOT NULL,
    request_type text,
    metadata jsonb,
    created_at timestamp with time zone DEFAULT now() NOT NULL
);


--
-- Name: ai_workflows; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.ai_workflows (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    client_id uuid,
    name text NOT NULL,
    description text,
    trigger_type public.ai_trigger_type NOT NULL,
    steps jsonb NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    run_count integer DEFAULT 0,
    created_by uuid,
    metadata jsonb,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL
);


--
-- Name: announcements; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.announcements (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    title text NOT NULL,
    content text NOT NULL,
    link_url text,
    link_text text,
    client_id uuid,
    is_active boolean DEFAULT true NOT NULL,
    priority integer DEFAULT 0,
    starts_at timestamp with time zone,
    expires_at timestamp with time zone,
    metadata jsonb,
    created_by uuid,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL,
    deleted_at timestamp with time zone
);


--
-- Name: TABLE announcements; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE public.announcements IS 'Announcements and news items shown in client dashboard news ticker';


--
-- Name: COLUMN announcements.client_id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.announcements.client_id IS 'NULL for global announcements, specific client_id for targeted announcements';


--
-- Name: COLUMN announcements.priority; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.announcements.priority IS 'Higher numbers show first (0 = normal, 10 = high priority)';


--
-- Name: automation_logs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.automation_logs (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    automation_rule_id uuid NOT NULL,
    trigger text NOT NULL,
    status text NOT NULL,
    message text,
    context jsonb,
    started_at timestamp with time zone NOT NULL,
    finished_at timestamp with time zone,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL
);


--
-- Name: automation_rules; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.automation_rules (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    name text NOT NULL,
    description text,
    trigger text NOT NULL,
    conditions jsonb,
    actions jsonb NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    sort_order integer DEFAULT 0 NOT NULL,
    run_count integer DEFAULT 0 NOT NULL,
    last_run_at timestamp with time zone,
    created_by uuid,
    updated_by uuid,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL
);


--
-- Name: automation_runs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.automation_runs (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    automation_rule_id uuid NOT NULL,
    trigger text NOT NULL,
    client_id uuid,
    context jsonb,
    matched boolean DEFAULT false NOT NULL,
    succeeded boolean DEFAULT false NOT NULL,
    actions_total integer DEFAULT 0 NOT NULL,
    actions_succeeded integer DEFAULT 0 NOT NULL,
    actions_failed integer DEFAULT 0 NOT NULL,
    error text,
    ran_at timestamp with time zone DEFAULT now() NOT NULL,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL
);


--
-- Name: brand_assets; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.brand_assets (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    client_id uuid,
    asset_type text NOT NULL,
    asset_name text,
    asset_value text,
    usage_context text,
    is_approved boolean DEFAULT false NOT NULL,
    meta jsonb,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL
);


--
-- Name: brand_audits; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.brand_audits (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    client_id uuid,
    audit_date date NOT NULL,
    status text DEFAULT 'pending'::text NOT NULL,
    overall_score integer,
    visual_score integer,
    messaging_score integer,
    consistency_score integer,
    perception_score integer,
    report jsonb,
    failure_reason text,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL
);


--
-- Name: brand_colors; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.brand_colors (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    brand_guide_id uuid NOT NULL,
    color_name text,
    color_type text DEFAULT 'primary'::text NOT NULL,
    hex_value text,
    rgb_value text,
    cmyk_value text,
    pantone_value text,
    usage_context text,
    accessibility_notes text,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL
);


--
-- Name: brand_competitors; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.brand_competitors (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    client_id uuid,
    competitor_name text NOT NULL,
    website_url text,
    positioning text,
    target_audience text,
    key_differentiators jsonb,
    is_active boolean DEFAULT true NOT NULL,
    meta jsonb,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL
);


--
-- Name: brand_fonts; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.brand_fonts (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    brand_guide_id uuid NOT NULL,
    font_name text NOT NULL,
    font_category text DEFAULT 'primary'::text NOT NULL,
    font_weights jsonb,
    font_file_path text,
    web_font_url text,
    usage_context text,
    licensing_info text,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL
);


--
-- Name: brand_guide_sections; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.brand_guide_sections (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    brand_guide_id uuid NOT NULL,
    section_type text NOT NULL,
    section_order integer DEFAULT 0 NOT NULL,
    title text,
    content jsonb,
    is_visible boolean DEFAULT true NOT NULL,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL
);


--
-- Name: brand_guides; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.brand_guides (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    client_id uuid,
    version integer DEFAULT 1 NOT NULL,
    status text DEFAULT 'draft'::text NOT NULL,
    cover_image text,
    slug text NOT NULL,
    is_public boolean DEFAULT false NOT NULL,
    password_protected boolean DEFAULT false NOT NULL,
    password text,
    created_by uuid,
    published_at timestamp with time zone,
    meta jsonb,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL
);


--
-- Name: brand_inconsistencies; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.brand_inconsistencies (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    brand_audit_id uuid NOT NULL,
    category text DEFAULT 'visual'::text NOT NULL,
    severity text DEFAULT 'warning'::text NOT NULL,
    location text,
    description text NOT NULL,
    recommendation text,
    status text DEFAULT 'open'::text NOT NULL,
    meta jsonb,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL
);


--
-- Name: brand_mentions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.brand_mentions (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    client_id uuid,
    platform text NOT NULL,
    mention_text text NOT NULL,
    sentiment text,
    author text,
    url text,
    posted_at timestamp with time zone,
    responded_at timestamp with time zone,
    responded_by uuid,
    response_notes text,
    meta jsonb,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL
);


--
-- Name: brand_templates; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.brand_templates (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    brand_guide_id uuid NOT NULL,
    template_name text NOT NULL,
    template_type text NOT NULL,
    file_path text NOT NULL,
    thumbnail text,
    download_count integer DEFAULT 0 NOT NULL,
    is_public boolean DEFAULT false NOT NULL,
    meta jsonb,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL
);


--
-- Name: calendar_connections; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.calendar_connections (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    user_id uuid NOT NULL,
    provider text NOT NULL,
    calendar_id text DEFAULT 'primary'::text NOT NULL,
    calendar_name text,
    encrypted_access_token text NOT NULL,
    encrypted_refresh_token text,
    token_expiry timestamp with time zone,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL,
    CONSTRAINT calendar_connections_provider_check CHECK ((provider = ANY (ARRAY['google'::text, 'microsoft'::text])))
);


--
-- Name: campaign_assets; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.campaign_assets (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    campaign_id uuid NOT NULL,
    asset_type text NOT NULL,
    name text NOT NULL,
    file_url text NOT NULL,
    thumbnail_url text,
    is_primary integer DEFAULT 0,
    metadata jsonb,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL
);


--
-- Name: campaign_metrics; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.campaign_metrics (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    campaign_id uuid NOT NULL,
    metric_date date NOT NULL,
    channel text,
    impressions integer DEFAULT 0,
    clicks integer DEFAULT 0,
    conversions integer DEFAULT 0,
    cost numeric(12,2) DEFAULT 0,
    revenue numeric(12,2) DEFAULT 0,
    metadata jsonb,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL
);


--
-- Name: campaigns; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.campaigns (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    client_id uuid NOT NULL,
    name text NOT NULL,
    description text,
    campaign_type text NOT NULL,
    status text DEFAULT 'draft'::text NOT NULL,
    start_date date,
    end_date date,
    budget numeric(12,2),
    spent numeric(12,2) DEFAULT 0,
    goals jsonb,
    channels jsonb,
    created_by uuid,
    metadata jsonb,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL,
    deleted_at timestamp with time zone
);


--
-- Name: client_features; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.client_features (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    client_id uuid NOT NULL,
    feature_id uuid NOT NULL,
    is_enabled boolean DEFAULT true NOT NULL,
    config jsonb,
    enabled_at timestamp with time zone,
    enabled_by uuid,
    disabled_at timestamp with time zone,
    disabled_by uuid,
    notes text,
    created_at timestamp with time zone DEFAULT now(),
    updated_at timestamp with time zone DEFAULT now()
);


--
-- Name: client_health_snapshots; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.client_health_snapshots (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    client_id uuid NOT NULL,
    snapshot_date timestamp with time zone NOT NULL,
    metrics jsonb NOT NULL,
    alerts jsonb,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL
);


--
-- Name: clients; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.clients (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    company_name text NOT NULL,
    email text NOT NULL,
    phone text,
    website text,
    address text,
    city text,
    state text,
    zip_code text,
    country text DEFAULT 'US'::text,
    status text DEFAULT 'active'::text NOT NULL,
    enabled_features jsonb,
    google_search_console_data jsonb,
    marketing_strategy jsonb,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL,
    deleted_at timestamp with time zone,
    domain text,
    industry text,
    primary_contact_id uuid,
    logo_url text,
    CONSTRAINT clients_status_check CHECK ((status = ANY (ARRAY['active'::text, 'inactive'::text, 'pending'::text, 'suspended'::text])))
);


--
-- Name: TABLE clients; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE public.clients IS 'Client companies in the multi-tenant system';


--
-- Name: COLUMN clients.domain; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.clients.domain IS 'Primary website domain (e.g., example.com)';


--
-- Name: COLUMN clients.industry; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.clients.industry IS 'Client industry (free-form)';


--
-- Name: COLUMN clients.primary_contact_id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.clients.primary_contact_id IS 'Primary contact user id (public.users.id)';


--
-- Name: COLUMN clients.logo_url; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.clients.logo_url IS 'Public logo image URL used in client pages and listings';


--
-- Name: content_calendar_items; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.content_calendar_items (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    client_id uuid NOT NULL,
    title text NOT NULL,
    content_type text NOT NULL,
    platform text NOT NULL,
    scheduled_for timestamp with time zone,
    published_at timestamp with time zone,
    status text DEFAULT 'draft'::text NOT NULL,
    content text,
    campaign_tag text,
    approved_by uuid,
    created_by uuid,
    metadata jsonb,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL,
    deleted_at timestamp with time zone
);


--
-- Name: content_templates; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.content_templates (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    client_id uuid NOT NULL,
    name text NOT NULL,
    template_type text NOT NULL,
    content text NOT NULL,
    variables jsonb,
    usage_count integer DEFAULT 0,
    created_by uuid,
    metadata jsonb,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL,
    deleted_at timestamp with time zone
);


--
-- Name: contracts; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.contracts (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    title text NOT NULL,
    description text,
    contract_number text NOT NULL,
    client_id uuid NOT NULL,
    type text NOT NULL,
    status text DEFAULT 'draft'::text NOT NULL,
    start_date timestamp with time zone,
    end_date timestamp with time zone,
    signed_date timestamp with time zone,
    value integer,
    currency text DEFAULT 'USD'::text,
    billing_cycle text,
    document_id uuid,
    client_signed_by text,
    client_signed_at timestamp with time zone,
    company_signed_by uuid,
    company_signed_at timestamp with time zone,
    terms jsonb,
    auto_renew boolean DEFAULT false,
    notice_required integer,
    tags jsonb,
    custom_fields jsonb,
    created_at timestamp with time zone DEFAULT now(),
    updated_at timestamp with time zone DEFAULT now(),
    deleted_at timestamp with time zone
);


--
-- Name: conversation_participants; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.conversation_participants (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    conversation_id uuid NOT NULL,
    user_id uuid NOT NULL,
    role text,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL
);


--
-- Name: conversations; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.conversations (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    client_id uuid NOT NULL,
    title text,
    context_type text,
    context_id uuid,
    is_closed boolean DEFAULT false NOT NULL,
    last_message_at timestamp with time zone,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL
);


--
-- Name: custom_dashboards; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.custom_dashboards (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    user_id uuid,
    client_id uuid,
    dashboard_name text NOT NULL,
    layout jsonb,
    widgets jsonb,
    is_default boolean DEFAULT false NOT NULL,
    meta jsonb,
    created_by uuid,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL
);


--
-- Name: data_privacy_requests; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.data_privacy_requests (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    client_id uuid NOT NULL,
    user_id uuid,
    request_type text NOT NULL,
    status text DEFAULT 'pending'::text NOT NULL,
    requested_at timestamp with time zone DEFAULT now() NOT NULL,
    completed_at timestamp with time zone,
    data_export_url text,
    notes text,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL
);


--
-- Name: document_shares; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.document_shares (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    document_id uuid NOT NULL,
    shared_with_user_id uuid,
    shared_with_email text,
    can_view boolean DEFAULT true NOT NULL,
    can_download boolean DEFAULT true NOT NULL,
    can_edit boolean DEFAULT false NOT NULL,
    expires_at timestamp with time zone,
    shared_by uuid NOT NULL,
    last_accessed_at timestamp with time zone,
    access_count integer DEFAULT 0,
    created_at timestamp with time zone DEFAULT now(),
    updated_at timestamp with time zone DEFAULT now()
);


--
-- Name: documents; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.documents (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    name text NOT NULL,
    description text,
    file_name text NOT NULL,
    file_size integer NOT NULL,
    mime_type text NOT NULL,
    storage_path text NOT NULL,
    storage_url text,
    client_id uuid NOT NULL,
    request_id uuid,
    uploaded_by uuid NOT NULL,
    version integer DEFAULT 1 NOT NULL,
    parent_document_id uuid,
    is_latest_version boolean DEFAULT true NOT NULL,
    is_public boolean DEFAULT false NOT NULL,
    shared_with jsonb,
    tags jsonb,
    metadata jsonb,
    created_at timestamp with time zone DEFAULT now(),
    updated_at timestamp with time zone DEFAULT now(),
    deleted_at timestamp with time zone
);


--
-- Name: email_templates; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.email_templates (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    name text NOT NULL,
    description text,
    type text NOT NULL,
    subject text NOT NULL,
    html_content text NOT NULL,
    text_content text,
    available_variables jsonb,
    is_default boolean DEFAULT false,
    is_active boolean DEFAULT true,
    preview_data jsonb,
    created_at timestamp with time zone DEFAULT now(),
    updated_at timestamp with time zone DEFAULT now(),
    deleted_at timestamp with time zone
);


--
-- Name: encrypted_settings; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.encrypted_settings (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    client_id uuid NOT NULL,
    provider text NOT NULL,
    category text NOT NULL,
    setting_key text NOT NULL,
    encrypted_value text NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    label text,
    last_rotated_at timestamp with time zone,
    last_verified_at timestamp with time zone,
    updated_by uuid,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL
);


--
-- Name: TABLE encrypted_settings; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE public.encrypted_settings IS 'Stores encrypted API keys and OAuth credentials for third-party integrations (AES-256-GCM encrypted)';


--
-- Name: COLUMN encrypted_settings.provider; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.encrypted_settings.provider IS 'Integration provider (e.g., anthropic, stripe, zapier, google_analytics)';


--
-- Name: COLUMN encrypted_settings.category; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.encrypted_settings.category IS 'Provider category (ai, payments, email, social, analytics, automation, storage)';


--
-- Name: COLUMN encrypted_settings.encrypted_value; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.encrypted_settings.encrypted_value IS 'AES-256-GCM encrypted value with salt+iv+authTag prepended';


--
-- Name: features; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.features (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    name text NOT NULL,
    display_name text NOT NULL,
    description text,
    category text NOT NULL,
    is_enabled_by_default boolean DEFAULT true,
    requires_setup boolean DEFAULT false,
    setup_instructions text,
    dependencies jsonb,
    metadata jsonb,
    created_at timestamp with time zone DEFAULT now(),
    updated_at timestamp with time zone DEFAULT now()
);


--
-- Name: form_templates; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.form_templates (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    client_id uuid,
    name text NOT NULL,
    description text,
    fields jsonb NOT NULL,
    validation_rules jsonb,
    created_by uuid,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL
);


--
-- Name: invoice_items; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.invoice_items (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    invoice_id uuid NOT NULL,
    description text NOT NULL,
    quantity numeric(10,2) DEFAULT 1 NOT NULL,
    unit_price numeric(10,2) NOT NULL,
    amount numeric(10,2) NOT NULL,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL,
    details text
);


--
-- Name: TABLE invoice_items; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE public.invoice_items IS 'Line items for invoices';


--
-- Name: invoice_templates; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.invoice_templates (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    name text NOT NULL,
    description text,
    html_content text NOT NULL,
    css_content text,
    available_variables jsonb,
    is_default boolean DEFAULT false,
    is_active boolean DEFAULT true,
    preview_data jsonb,
    created_at timestamp with time zone DEFAULT now(),
    updated_at timestamp with time zone DEFAULT now(),
    deleted_at timestamp with time zone
);


--
-- Name: invoices; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.invoices (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    invoice_number text NOT NULL,
    client_id uuid NOT NULL,
    amount numeric(10,2) NOT NULL,
    status text DEFAULT 'draft'::text NOT NULL,
    due_date timestamp with time zone,
    paid_at timestamp with time zone,
    notes text,
    items jsonb,
    is_recurring boolean DEFAULT false,
    recurring_interval text,
    next_recurring_date timestamp with time zone,
    created_by uuid,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL,
    deleted_at timestamp with time zone,
    subtotal numeric(10,2),
    tax_rate numeric(5,3) DEFAULT 0,
    tax_amount numeric(10,2) DEFAULT 0,
    discount_type text DEFAULT 'none'::text,
    discount_value numeric(10,2) DEFAULT 0,
    discount_amount numeric(10,2) DEFAULT 0,
    CONSTRAINT invoices_discount_type_check CHECK ((discount_type = ANY (ARRAY['none'::text, 'percentage'::text, 'fixed'::text]))),
    CONSTRAINT invoices_recurring_interval_check CHECK ((recurring_interval = ANY (ARRAY['weekly'::text, 'monthly'::text, 'quarterly'::text, 'yearly'::text]))),
    CONSTRAINT invoices_status_check CHECK ((status = ANY (ARRAY['draft'::text, 'sent'::text, 'paid'::text, 'overdue'::text, 'cancelled'::text])))
);


--
-- Name: TABLE invoices; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE public.invoices IS 'Client invoices with recurring billing support';


--
-- Name: knowledge_base_articles; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.knowledge_base_articles (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    category_id uuid NOT NULL,
    title text NOT NULL,
    slug text NOT NULL,
    excerpt text,
    content text NOT NULL,
    video_url text,
    is_published boolean DEFAULT true NOT NULL,
    published_at timestamp with time zone,
    view_count integer DEFAULT 0 NOT NULL,
    helpful_count integer DEFAULT 0 NOT NULL,
    created_by uuid,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL
);


--
-- Name: knowledge_base_categories; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.knowledge_base_categories (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    name text NOT NULL,
    slug text NOT NULL,
    description text,
    parent_id uuid,
    "position" integer DEFAULT 0 NOT NULL,
    icon text DEFAULT 'book'::text,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL
);


--
-- Name: knowledge_base_feedback; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.knowledge_base_feedback (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    article_id uuid NOT NULL,
    user_id uuid,
    was_helpful boolean NOT NULL,
    comment text,
    created_at timestamp with time zone DEFAULT now() NOT NULL
);


--
-- Name: lead_activities; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.lead_activities (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    lead_id uuid NOT NULL,
    activity_type text NOT NULL,
    description text NOT NULL,
    occurred_at timestamp with time zone DEFAULT now() NOT NULL,
    user_id uuid,
    metadata jsonb,
    created_at timestamp with time zone DEFAULT now() NOT NULL
);


--
-- Name: leads; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.leads (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    client_id uuid NOT NULL,
    name text NOT NULL,
    email text NOT NULL,
    phone text,
    company text,
    source text NOT NULL,
    status text DEFAULT 'new'::text NOT NULL,
    score integer DEFAULT 0,
    assigned_to uuid,
    converted_at timestamp with time zone,
    metadata jsonb,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL,
    deleted_at timestamp with time zone
);


--
-- Name: maintenance_plan_billing_history; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.maintenance_plan_billing_history (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    plan_id uuid NOT NULL,
    invoice_id uuid,
    billing_period_start date NOT NULL,
    billing_period_end date NOT NULL,
    base_amount numeric(10,2) NOT NULL,
    overage_hours numeric(10,2) DEFAULT 0 NOT NULL,
    overage_amount numeric(10,2) DEFAULT 0 NOT NULL,
    total_amount numeric(10,2) NOT NULL,
    included_hours numeric(10,2) NOT NULL,
    used_hours numeric(10,2) NOT NULL,
    rollover_hours_used numeric(10,2) DEFAULT 0 NOT NULL,
    billing_status text DEFAULT 'pending'::text NOT NULL,
    billed_at timestamp with time zone,
    paid_at timestamp with time zone,
    metadata jsonb,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL
);


--
-- Name: maintenance_plan_templates; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.maintenance_plan_templates (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    name text NOT NULL,
    description text,
    plan_type text DEFAULT 'standard'::text NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    billing_cycle text DEFAULT 'monthly'::text NOT NULL,
    monthly_rate numeric(10,2) NOT NULL,
    currency text DEFAULT 'USD'::text NOT NULL,
    included_hours numeric(10,2) NOT NULL,
    hourly_rate_overage numeric(10,2) NOT NULL,
    auto_renew boolean DEFAULT true NOT NULL,
    rollover_enabled boolean DEFAULT false NOT NULL,
    max_rollover_hours numeric(10,2),
    overage_billing_enabled boolean DEFAULT true NOT NULL,
    overage_approval_required boolean DEFAULT false NOT NULL,
    overage_notification_threshold numeric(5,2) DEFAULT 90 NOT NULL,
    renewal_term_months integer DEFAULT 12 NOT NULL,
    services_included jsonb,
    metadata jsonb,
    created_by uuid NOT NULL,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL
);


--
-- Name: maintenance_plan_usage; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.maintenance_plan_usage (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    plan_id uuid NOT NULL,
    support_ticket_id uuid,
    logged_by uuid NOT NULL,
    hours_used numeric(10,2) NOT NULL,
    description text NOT NULL,
    task_category text,
    is_overage boolean DEFAULT false NOT NULL,
    overage_amount numeric(10,2),
    is_billable boolean DEFAULT true NOT NULL,
    billed_amount numeric(10,2),
    invoice_id uuid,
    logged_at timestamp with time zone DEFAULT now() NOT NULL,
    work_performed_at timestamp with time zone,
    requires_approval boolean DEFAULT false NOT NULL,
    approved_by uuid,
    approved_at timestamp with time zone,
    approval_status text DEFAULT 'approved'::text,
    metadata jsonb,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL,
    deleted_at timestamp with time zone
);


--
-- Name: maintenance_plans; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.maintenance_plans (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    client_id uuid NOT NULL,
    created_by uuid NOT NULL,
    name text NOT NULL,
    description text,
    plan_type text DEFAULT 'standard'::text NOT NULL,
    status text DEFAULT 'active'::text NOT NULL,
    start_date date NOT NULL,
    end_date date,
    next_billing_date date,
    billing_cycle text DEFAULT 'monthly'::text NOT NULL,
    monthly_rate numeric(10,2) NOT NULL,
    currency text DEFAULT 'USD'::text NOT NULL,
    auto_renew boolean DEFAULT true NOT NULL,
    included_hours numeric(10,2) NOT NULL,
    used_hours numeric(10,2) DEFAULT 0 NOT NULL,
    hourly_rate_overage numeric(10,2) NOT NULL,
    rollover_enabled boolean DEFAULT false NOT NULL,
    max_rollover_hours numeric(10,2),
    rollover_hours_available numeric(10,2) DEFAULT 0 NOT NULL,
    services_included jsonb,
    covered_services jsonb,
    overage_billing_enabled boolean DEFAULT true NOT NULL,
    overage_approval_required boolean DEFAULT false NOT NULL,
    overage_notification_threshold numeric(5,2) DEFAULT 90 NOT NULL,
    auto_renew_notification_days integer DEFAULT 30 NOT NULL,
    last_renewal_notification_sent timestamp with time zone,
    renewal_term_months integer DEFAULT 12 NOT NULL,
    metadata jsonb,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL,
    deleted_at timestamp with time zone,
    template_id uuid
);


--
-- Name: meeting_attendees; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.meeting_attendees (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    meeting_id uuid NOT NULL,
    user_id uuid NOT NULL,
    status text DEFAULT 'invited'::text NOT NULL,
    attended jsonb,
    response_note text,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL
);


--
-- Name: meeting_notes; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.meeting_notes (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    meeting_id uuid NOT NULL,
    section text NOT NULL,
    content text NOT NULL,
    order_index integer DEFAULT 0,
    created_by uuid NOT NULL,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL
);


--
-- Name: meetings; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.meetings (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    client_id uuid NOT NULL,
    request_id uuid,
    title text NOT NULL,
    description text,
    meeting_type text NOT NULL,
    status text DEFAULT 'scheduled'::text NOT NULL,
    scheduled_at timestamp with time zone NOT NULL,
    duration_minutes integer DEFAULT 60 NOT NULL,
    location text,
    meeting_url text,
    attendees jsonb,
    agenda text,
    notes text,
    recording_url text,
    action_items jsonb,
    metadata jsonb,
    created_by uuid NOT NULL,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL,
    deleted_at timestamp with time zone
);


--
-- Name: message_attachments; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.message_attachments (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    message_id uuid NOT NULL,
    disk text DEFAULT 'attachments'::text NOT NULL,
    path text NOT NULL,
    filename text NOT NULL,
    mime_type text,
    size_bytes bigint DEFAULT 0 NOT NULL,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL
);


--
-- Name: message_reads; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.message_reads (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    message_id uuid NOT NULL,
    user_id uuid NOT NULL,
    read_at timestamp with time zone,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL
);


--
-- Name: messages; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.messages (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    conversation_id uuid NOT NULL,
    sender_id uuid,
    body text,
    type text DEFAULT 'text'::text NOT NULL,
    meta jsonb,
    mentions jsonb,
    is_pinned boolean DEFAULT false NOT NULL,
    pinned_at timestamp with time zone,
    pinned_by uuid,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL
);


--
-- Name: partners; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.partners (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    company_name text NOT NULL,
    contact_name text NOT NULL,
    email text NOT NULL,
    phone text,
    website text,
    partner_type text DEFAULT 'affiliate'::text NOT NULL,
    status text DEFAULT 'active'::text NOT NULL,
    commission_rate numeric(5,2) DEFAULT 0 NOT NULL,
    total_referrals integer DEFAULT 0 NOT NULL,
    total_revenue numeric(12,2) DEFAULT 0 NOT NULL,
    code text NOT NULL,
    metadata jsonb,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL
);


--
-- Name: permissions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.permissions (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    name text NOT NULL,
    description text,
    resource text NOT NULL,
    action text NOT NULL,
    created_at timestamp with time zone DEFAULT now() NOT NULL
);


--
-- Name: project_budgets; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.project_budgets (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    project_id uuid NOT NULL,
    category text NOT NULL,
    allocated_amount numeric(12,2) NOT NULL,
    spent_amount numeric(12,2) DEFAULT 0,
    currency text DEFAULT 'USD'::text NOT NULL,
    notes text,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL,
    CONSTRAINT project_budgets_category_check CHECK ((category = ANY (ARRAY['development'::text, 'design'::text, 'marketing'::text, 'infrastructure'::text, 'other'::text]))),
    CONSTRAINT project_budgets_currency_check CHECK ((currency = ANY (ARRAY['USD'::text, 'EUR'::text, 'GBP'::text, 'CAD'::text, 'AUD'::text])))
);


--
-- Name: TABLE project_budgets; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE public.project_budgets IS 'Project budget breakdown by category for detailed expense tracking';


--
-- Name: project_cost_entries; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.project_cost_entries (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    project_id uuid NOT NULL,
    budget_id uuid,
    user_id uuid,
    description text NOT NULL,
    amount numeric(12,2) NOT NULL,
    entry_date timestamp with time zone NOT NULL,
    approved_by uuid,
    approved_at timestamp with time zone,
    metadata jsonb,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL
);


--
-- Name: TABLE project_cost_entries; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE public.project_cost_entries IS 'Individual cost/expense entries for project spending tracking';


--
-- Name: project_deliverables; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.project_deliverables (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    project_id uuid NOT NULL,
    milestone_id uuid,
    title text NOT NULL,
    description text,
    status text DEFAULT 'pending'::text NOT NULL,
    due_date timestamp with time zone,
    delivered_at timestamp with time zone,
    document_id uuid,
    sort_order integer DEFAULT 0,
    metadata jsonb,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL,
    CONSTRAINT project_deliverables_status_check CHECK ((status = ANY (ARRAY['pending'::text, 'in_progress'::text, 'review'::text, 'completed'::text, 'rejected'::text])))
);


--
-- Name: TABLE project_deliverables; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE public.project_deliverables IS 'Specific deliverables within projects or milestones';


--
-- Name: project_milestones; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.project_milestones (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    project_id uuid NOT NULL,
    title text NOT NULL,
    description text,
    due_date timestamp with time zone,
    completed_at timestamp with time zone,
    completion_percentage integer DEFAULT 0,
    sort_order integer DEFAULT 0,
    metadata jsonb,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL
);


--
-- Name: TABLE project_milestones; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE public.project_milestones IS 'Major project milestones with progress tracking';


--
-- Name: project_review_comments; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.project_review_comments (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    review_item_id uuid NOT NULL,
    project_id uuid NOT NULL,
    author_id uuid,
    body text NOT NULL,
    x_percent numeric(6,3),
    y_percent numeric(6,3),
    status text DEFAULT 'open'::text NOT NULL,
    metadata jsonb,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL,
    CONSTRAINT project_review_comments_coordinates_check CHECK ((((x_percent IS NULL) AND (y_percent IS NULL)) OR ((x_percent IS NOT NULL) AND (y_percent IS NOT NULL) AND (x_percent >= (0)::numeric) AND (x_percent <= (100)::numeric) AND (y_percent >= (0)::numeric) AND (y_percent <= (100)::numeric)))),
    CONSTRAINT project_review_comments_status_check CHECK ((status = ANY (ARRAY['open'::text, 'in_review'::text, 'resolved'::text, 'archived'::text])))
);


--
-- Name: project_review_items; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.project_review_items (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    project_id uuid NOT NULL,
    type text NOT NULL,
    title text NOT NULL,
    website_url text,
    image_storage_path text,
    image_file_name text,
    image_mime_type text,
    image_size integer,
    status text DEFAULT 'open'::text NOT NULL,
    created_by uuid,
    metadata jsonb,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL,
    CONSTRAINT project_review_items_source_check CHECK ((((type = 'website'::text) AND (website_url IS NOT NULL)) OR ((type = 'image'::text) AND (image_storage_path IS NOT NULL)))),
    CONSTRAINT project_review_items_status_check CHECK ((status = ANY (ARRAY['open'::text, 'in_review'::text, 'resolved'::text, 'archived'::text]))),
    CONSTRAINT project_review_items_type_check CHECK ((type = ANY (ARRAY['website'::text, 'image'::text])))
);


--
-- Name: projects; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.projects (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    client_id uuid NOT NULL,
    name text NOT NULL,
    description text,
    status text DEFAULT 'planning'::text NOT NULL,
    start_date timestamp with time zone,
    end_date timestamp with time zone,
    estimated_hours numeric(10,2),
    actual_hours numeric(10,2) DEFAULT 0,
    budget_amount numeric(12,2),
    spent_amount numeric(12,2) DEFAULT 0,
    currency text DEFAULT 'USD'::text NOT NULL,
    project_manager_id uuid,
    progress_percent integer DEFAULT 0,
    team_members jsonb,
    metadata jsonb,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL,
    deleted_at timestamp with time zone,
    CONSTRAINT projects_currency_check CHECK ((currency = ANY (ARRAY['USD'::text, 'EUR'::text, 'GBP'::text, 'CAD'::text, 'AUD'::text]))),
    CONSTRAINT projects_status_check CHECK ((status = ANY (ARRAY['planning'::text, 'active'::text, 'on_hold'::text, 'completed'::text, 'cancelled'::text])))
);


--
-- Name: TABLE projects; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE public.projects IS 'Client projects with budget tracking, milestones, and team management';


--
-- Name: COLUMN projects.team_members; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.projects.team_members IS 'Array of team members with userId, name, role, hourlyRate (JSONB)';


--
-- Name: COLUMN projects.metadata; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.projects.metadata IS 'Additional metadata including tags, priority, repository, slackChannel (JSONB)';


--
-- Name: prompt_templates; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.prompt_templates (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    name text NOT NULL,
    description text,
    template_content text NOT NULL,
    variables jsonb,
    category public.ai_prompt_category DEFAULT 'general'::public.ai_prompt_category NOT NULL,
    created_by uuid,
    metadata jsonb,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL
);


--
-- Name: proposal_selections; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.proposal_selections (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    proposal_id uuid NOT NULL,
    section_name text NOT NULL,
    selected_option text NOT NULL,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL
);


--
-- Name: TABLE proposal_selections; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE public.proposal_selections IS 'Client selections for proposal sections with multiple options';


--
-- Name: proposal_views; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.proposal_views (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    proposal_id uuid NOT NULL,
    viewed_by_ip text,
    viewed_by_user_id uuid,
    viewed_at timestamp with time zone DEFAULT now() NOT NULL
);


--
-- Name: TABLE proposal_views; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE public.proposal_views IS 'Tracks when and by whom proposals are viewed for analytics';


--
-- Name: proposals; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.proposals (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    client_id uuid NOT NULL,
    title text NOT NULL,
    description text,
    status text DEFAULT 'draft'::text NOT NULL,
    total_amount numeric(10,2) NOT NULL,
    currency text DEFAULT 'USD'::text NOT NULL,
    valid_until timestamp with time zone,
    created_by uuid NOT NULL,
    sent_at timestamp with time zone,
    viewed_at timestamp with time zone,
    accepted_at timestamp with time zone,
    rejected_at timestamp with time zone,
    signature_data jsonb,
    terms text,
    line_items jsonb NOT NULL,
    metadata jsonb,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL,
    deleted_at timestamp with time zone,
    service_template_id uuid,
    CONSTRAINT proposals_currency_check CHECK ((currency = ANY (ARRAY['USD'::text, 'EUR'::text, 'GBP'::text, 'CAD'::text]))),
    CONSTRAINT proposals_status_check CHECK ((status = ANY (ARRAY['draft'::text, 'sent'::text, 'viewed'::text, 'accepted'::text, 'rejected'::text, 'expired'::text])))
);


--
-- Name: TABLE proposals; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE public.proposals IS 'Client proposals with e-signature support, line items, and tracking';


--
-- Name: COLUMN proposals.signature_data; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.proposals.signature_data IS 'E-signature data including image, signer info, timestamp (JSONB)';


--
-- Name: COLUMN proposals.line_items; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.proposals.line_items IS 'Array of line items with description, quantity, price (JSONB)';


--
-- Name: COLUMN proposals.metadata; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.proposals.metadata IS 'Additional metadata including notes, tags, attachments (JSONB)';


--
-- Name: referrals; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.referrals (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    partner_id uuid NOT NULL,
    client_id uuid,
    referral_code text NOT NULL,
    referred_name text,
    referred_email text,
    referred_phone text,
    status text DEFAULT 'pending'::text NOT NULL,
    referred_at timestamp with time zone DEFAULT now() NOT NULL,
    converted_at timestamp with time zone,
    converted_client_id uuid,
    commission_amount numeric(10,2),
    paid_at timestamp with time zone,
    metadata jsonb,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL
);


--
-- Name: report_deliveries; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.report_deliveries (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    report_schedule_id uuid,
    report_template_id uuid NOT NULL,
    client_id uuid,
    category text,
    meta jsonb,
    disk text DEFAULT 'public'::text,
    path text NOT NULL,
    recipients jsonb,
    generated_at timestamp with time zone NOT NULL,
    sent_at timestamp with time zone,
    status text NOT NULL,
    error text,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL
);


--
-- Name: report_schedules; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.report_schedules (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    report_template_id uuid NOT NULL,
    client_id uuid,
    name text NOT NULL,
    frequency text NOT NULL,
    recipients jsonb NOT NULL,
    config jsonb,
    is_active boolean DEFAULT true NOT NULL,
    next_run_at timestamp with time zone,
    last_run_at timestamp with time zone,
    last_error text,
    created_by uuid,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL
);


--
-- Name: report_templates; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.report_templates (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    name text NOT NULL,
    description text,
    report_type text NOT NULL,
    config jsonb,
    sections jsonb,
    created_by uuid,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL
);


--
-- Name: request_comments; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.request_comments (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    request_id uuid NOT NULL,
    user_id uuid NOT NULL,
    content text NOT NULL,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL
);


--
-- Name: TABLE request_comments; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE public.request_comments IS 'Comments on service requests';


--
-- Name: request_time_entries; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.request_time_entries (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    request_id uuid NOT NULL,
    user_id uuid NOT NULL,
    hours numeric(5,2) NOT NULL,
    note text,
    logged_at timestamp with time zone NOT NULL,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL
);


--
-- Name: TABLE request_time_entries; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE public.request_time_entries IS 'Simplified time tracking for service requests';


--
-- Name: requests; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.requests (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    client_id uuid NOT NULL,
    title text NOT NULL,
    description text,
    status text DEFAULT 'pending'::text NOT NULL,
    priority text DEFAULT 'medium'::text,
    created_by uuid NOT NULL,
    assigned_to uuid,
    due_date timestamp with time zone,
    custom_fields jsonb,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL,
    deleted_at timestamp with time zone,
    CONSTRAINT requests_priority_check CHECK ((priority = ANY (ARRAY['low'::text, 'medium'::text, 'high'::text]))),
    CONSTRAINT requests_status_check CHECK ((status = ANY (ARRAY['pending'::text, 'in_progress'::text, 'completed'::text, 'cancelled'::text, 'on_hold'::text, 'awaiting_approval'::text, 'approved'::text, 'rejected'::text])))
);


--
-- Name: TABLE requests; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE public.requests IS 'Service requests from clients';


--
-- Name: role_features; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.role_features (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    role_id uuid NOT NULL,
    feature_id uuid NOT NULL,
    is_enabled boolean DEFAULT true NOT NULL,
    created_at timestamp with time zone DEFAULT now(),
    updated_at timestamp with time zone DEFAULT now()
);


--
-- Name: role_permissions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.role_permissions (
    role_id uuid NOT NULL,
    permission_id uuid NOT NULL,
    created_at timestamp with time zone DEFAULT now() NOT NULL
);


--
-- Name: roles; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.roles (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    name text NOT NULL,
    description text,
    is_system boolean DEFAULT false NOT NULL,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL
);


--
-- Name: service_templates; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.service_templates (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    name text NOT NULL,
    description text,
    category text,
    is_active boolean DEFAULT true NOT NULL,
    currency text DEFAULT 'USD'::text NOT NULL,
    line_items jsonb NOT NULL,
    total_amount numeric(10,2) NOT NULL,
    terms text,
    metadata jsonb,
    created_by uuid NOT NULL,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL,
    CONSTRAINT service_templates_currency_check CHECK ((currency = ANY (ARRAY['USD'::text, 'EUR'::text, 'GBP'::text, 'CAD'::text])))
);


--
-- Name: TABLE service_templates; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE public.service_templates IS 'Admin-managed service offerings clients can request to generate proposals';


--
-- Name: COLUMN service_templates.line_items; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.service_templates.line_items IS 'Array of service line items with description, quantity, price, and amount (JSONB)';


--
-- Name: social_accounts; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.social_accounts (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    client_id uuid NOT NULL,
    platform text NOT NULL,
    account_name text NOT NULL,
    account_id text NOT NULL,
    access_token_encrypted text NOT NULL,
    refresh_token_encrypted text,
    expires_at timestamp with time zone,
    is_active boolean DEFAULT true NOT NULL,
    metadata jsonb,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL,
    deleted_at timestamp with time zone
);


--
-- Name: social_posts; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.social_posts (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    account_id uuid NOT NULL,
    content text NOT NULL,
    scheduled_for timestamp with time zone,
    published_at timestamp with time zone,
    post_url text,
    engagement_metrics jsonb,
    status text DEFAULT 'draft'::text NOT NULL,
    created_by uuid NOT NULL,
    metadata jsonb,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL,
    deleted_at timestamp with time zone
);


--
-- Name: staff_assignments; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.staff_assignments (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    client_id uuid NOT NULL,
    user_id uuid NOT NULL,
    role text DEFAULT 'member'::text,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL
);


--
-- Name: staff_guide_categories; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.staff_guide_categories (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    name text NOT NULL,
    slug text NOT NULL,
    description text,
    icon text DEFAULT 'book'::text,
    "position" integer DEFAULT 0 NOT NULL,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL
);


--
-- Name: staff_guide_views; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.staff_guide_views (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    guide_id uuid NOT NULL,
    user_id uuid,
    created_at timestamp with time zone DEFAULT now() NOT NULL
);


--
-- Name: staff_guides; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.staff_guides (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    category_id uuid NOT NULL,
    title text NOT NULL,
    slug text NOT NULL,
    summary text,
    content text NOT NULL,
    checklist jsonb,
    service_tier text,
    price numeric(10,2),
    commitment text,
    tags jsonb DEFAULT '[]'::jsonb,
    is_internal boolean DEFAULT true NOT NULL,
    is_published boolean DEFAULT true NOT NULL,
    view_count integer DEFAULT 0 NOT NULL,
    author_id uuid,
    published_at timestamp with time zone,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL
);


--
-- Name: staff_task_assignees; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.staff_task_assignees (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    task_id uuid NOT NULL,
    user_id uuid NOT NULL,
    role text,
    assigned_at timestamp with time zone DEFAULT now() NOT NULL,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL
);


--
-- Name: staff_task_boards; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.staff_task_boards (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    name text NOT NULL,
    description text,
    team_id uuid,
    created_by uuid,
    is_default boolean DEFAULT false NOT NULL,
    is_archived boolean DEFAULT false NOT NULL,
    color text DEFAULT '#3b82f6'::text,
    column_order jsonb DEFAULT '[]'::jsonb,
    settings jsonb,
    sort_order integer DEFAULT 0 NOT NULL,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL
);


--
-- Name: staff_task_checklists; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.staff_task_checklists (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    task_id uuid NOT NULL,
    title text NOT NULL,
    is_completed boolean DEFAULT false NOT NULL,
    "position" integer DEFAULT 0 NOT NULL,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL
);


--
-- Name: staff_task_columns; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.staff_task_columns (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    board_id uuid NOT NULL,
    name text NOT NULL,
    "position" integer DEFAULT 0 NOT NULL,
    wip_limit integer,
    color text DEFAULT '#94a3b8'::text,
    icon text,
    is_done_column boolean DEFAULT false NOT NULL,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL
);


--
-- Name: staff_task_comments; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.staff_task_comments (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    task_id uuid NOT NULL,
    user_id uuid NOT NULL,
    content text NOT NULL,
    is_system boolean DEFAULT false NOT NULL,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL
);


--
-- Name: staff_task_label_relations; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.staff_task_label_relations (
    task_id uuid NOT NULL,
    label_id uuid NOT NULL
);


--
-- Name: staff_task_labels; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.staff_task_labels (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    board_id uuid,
    name text NOT NULL,
    color text NOT NULL,
    description text,
    is_global boolean DEFAULT false NOT NULL,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL
);


--
-- Name: staff_tasks; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.staff_tasks (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    board_id uuid NOT NULL,
    column_id uuid NOT NULL,
    parent_id uuid,
    title text NOT NULL,
    description text,
    priority text DEFAULT 'normal'::text NOT NULL,
    start_date timestamp with time zone,
    due_date timestamp with time zone,
    estimated_hours numeric(10,2),
    actual_hours numeric(10,2),
    progress integer DEFAULT 0 NOT NULL,
    "position" integer DEFAULT 0 NOT NULL,
    created_by uuid NOT NULL,
    reporter_id uuid,
    client_id uuid,
    request_id uuid,
    completed_at timestamp with time zone,
    metadata jsonb,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL
);


--
-- Name: storage_connections; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.storage_connections (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    client_id uuid NOT NULL,
    provider text NOT NULL,
    connection_name text NOT NULL,
    credentials_encrypted text NOT NULL,
    sync_enabled boolean DEFAULT true NOT NULL,
    last_sync_at timestamp with time zone,
    config jsonb,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL,
    owner_type text DEFAULT 'client'::text NOT NULL,
    CONSTRAINT storage_connections_owner_type_check CHECK ((owner_type = ANY (ARRAY['company'::text, 'client'::text])))
);


--
-- Name: storage_files; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.storage_files (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    connection_id uuid NOT NULL,
    file_path text NOT NULL,
    file_name text NOT NULL,
    file_size bigint NOT NULL,
    mime_type text,
    last_modified timestamp with time zone NOT NULL,
    sync_status text DEFAULT 'pending'::text NOT NULL,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL
);


--
-- Name: support_ticket_comments; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.support_ticket_comments (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    support_ticket_id uuid NOT NULL,
    user_id uuid NOT NULL,
    comment text NOT NULL,
    is_internal boolean DEFAULT false NOT NULL,
    attachments jsonb,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL,
    deleted_at timestamp with time zone
);


--
-- Name: TABLE support_ticket_comments; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE public.support_ticket_comments IS 'Comments and internal notes on support tickets';


--
-- Name: support_tickets; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.support_tickets (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    client_id uuid NOT NULL,
    maintenance_plan_id uuid,
    created_by uuid NOT NULL,
    assigned_to uuid,
    invoice_id uuid,
    ticket_number text NOT NULL,
    subject text NOT NULL,
    description text NOT NULL,
    category text NOT NULL,
    status text DEFAULT 'open'::text NOT NULL,
    priority text DEFAULT 'medium'::text NOT NULL,
    is_billable boolean DEFAULT true NOT NULL,
    estimated_hours numeric(10,2),
    actual_hours numeric(10,2),
    hourly_rate numeric(10,2),
    first_response_at timestamp with time zone,
    resolved_at timestamp with time zone,
    closed_at timestamp with time zone,
    sla_response_due_at timestamp with time zone,
    sla_resolution_due_at timestamp with time zone,
    sla_response_breached boolean DEFAULT false NOT NULL,
    sla_resolution_breached boolean DEFAULT false NOT NULL,
    sla_response_breached_at timestamp with time zone,
    sla_resolution_breached_at timestamp with time zone,
    sla_paused boolean DEFAULT false NOT NULL,
    sla_paused_duration_minutes integer DEFAULT 0 NOT NULL,
    escalation_level integer DEFAULT 0 NOT NULL,
    last_escalated_at timestamp with time zone,
    metadata jsonb,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL,
    deleted_at timestamp with time zone,
    CONSTRAINT support_tickets_category_check CHECK ((category = ANY (ARRAY['technical'::text, 'billing'::text, 'general'::text, 'feature_request'::text, 'bug_report'::text, 'security'::text, 'performance'::text]))),
    CONSTRAINT support_tickets_priority_check CHECK ((priority = ANY (ARRAY['low'::text, 'medium'::text, 'high'::text, 'urgent'::text]))),
    CONSTRAINT support_tickets_status_check CHECK ((status = ANY (ARRAY['open'::text, 'in_progress'::text, 'waiting_on_client'::text, 'waiting_on_vendor'::text, 'resolved'::text, 'closed'::text])))
);


--
-- Name: TABLE support_tickets; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE public.support_tickets IS 'Support tickets with SLA tracking, escalation, and billing integration';


--
-- Name: COLUMN support_tickets.ticket_number; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.support_tickets.ticket_number IS 'Unique ticket number (e.g., TKT-2024-001)';


--
-- Name: COLUMN support_tickets.sla_response_due_at; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.support_tickets.sla_response_due_at IS 'When the first response is due according to SLA';


--
-- Name: COLUMN support_tickets.sla_resolution_due_at; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.support_tickets.sla_resolution_due_at IS 'When the ticket resolution is due according to SLA';


--
-- Name: COLUMN support_tickets.sla_paused; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.support_tickets.sla_paused IS 'Whether SLA timer is currently paused (e.g., waiting on client)';


--
-- Name: COLUMN support_tickets.escalation_level; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.support_tickets.escalation_level IS 'Number of times ticket has been escalated (0 = not escalated)';


--
-- Name: survey_answers; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.survey_answers (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    response_id uuid NOT NULL,
    question_id uuid NOT NULL,
    value text,
    created_at timestamp with time zone DEFAULT now() NOT NULL
);


--
-- Name: survey_questions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.survey_questions (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    survey_id uuid NOT NULL,
    type text DEFAULT 'text'::text NOT NULL,
    prompt text NOT NULL,
    options jsonb,
    is_required boolean DEFAULT true NOT NULL,
    sort_order integer DEFAULT 0 NOT NULL,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL
);


--
-- Name: survey_responses; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.survey_responses (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    survey_id uuid NOT NULL,
    respondent_id uuid,
    client_id uuid,
    anonymous_token text,
    metadata jsonb,
    submitted_at timestamp with time zone,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL
);


--
-- Name: surveys; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.surveys (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    client_id uuid,
    title text NOT NULL,
    description text,
    is_active boolean DEFAULT true NOT NULL,
    anonymous_allowed boolean DEFAULT true NOT NULL,
    response_count integer DEFAULT 0 NOT NULL,
    created_by uuid,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL
);


--
-- Name: system_settings; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.system_settings (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    category text NOT NULL,
    key text NOT NULL,
    value text NOT NULL,
    is_encrypted boolean DEFAULT false NOT NULL,
    updated_by uuid,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL
);


--
-- Name: time_entries; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.time_entries (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    user_id uuid NOT NULL,
    client_id uuid,
    request_id uuid,
    task_id uuid,
    project_id uuid,
    description text,
    started_at timestamp with time zone,
    ended_at timestamp with time zone,
    duration_minutes integer,
    is_billable boolean DEFAULT true NOT NULL,
    hourly_rate numeric(10,2),
    total_amount numeric(10,2),
    status text DEFAULT 'pending'::text NOT NULL,
    locked_at timestamp with time zone,
    locked_by uuid,
    approved_by uuid,
    approved_at timestamp with time zone,
    billed_at timestamp with time zone,
    metadata jsonb,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL,
    CONSTRAINT time_entries_status_check CHECK ((status = ANY (ARRAY['pending'::text, 'approved'::text, 'billed'::text, 'rejected'::text])))
);


--
-- Name: TABLE time_entries; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE public.time_entries IS 'Time tracking entries for billable work';


--
-- Name: time_entry_locks; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.time_entry_locks (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    user_id uuid NOT NULL,
    period_start date NOT NULL,
    period_end date NOT NULL,
    locked_at timestamp with time zone NOT NULL,
    locked_by uuid NOT NULL,
    reason text,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL
);


--
-- Name: TABLE time_entry_locks; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE public.time_entry_locks IS 'Period locks for time entries (prevents editing locked periods for payroll/billing)';


--
-- Name: user_features; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.user_features (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    user_id uuid NOT NULL,
    feature_id uuid NOT NULL,
    is_enabled boolean DEFAULT true NOT NULL,
    created_at timestamp with time zone DEFAULT now(),
    updated_at timestamp with time zone DEFAULT now()
);


--
-- Name: user_roles; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.user_roles (
    user_id uuid NOT NULL,
    role_id uuid NOT NULL,
    assigned_by uuid,
    created_at timestamp with time zone DEFAULT now() NOT NULL
);


--
-- Name: users; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.users (
    id uuid NOT NULL,
    name text NOT NULL,
    email text NOT NULL,
    phone text,
    avatar text,
    client_id uuid,
    is_active boolean DEFAULT true NOT NULL,
    is_super_admin boolean DEFAULT false NOT NULL,
    status text DEFAULT 'active'::text NOT NULL,
    last_login_at timestamp with time zone,
    manual_permissions jsonb,
    security_settings jsonb,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL,
    deleted_at timestamp with time zone,
    CONSTRAINT users_status_check CHECK ((status = ANY (ARRAY['active'::text, 'inactive'::text, 'suspended'::text])))
);


--
-- Name: TABLE users; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE public.users IS 'User accounts linked to clients - synced with auth.users';


--
-- Name: COLUMN users.id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.users.id IS 'References auth.users(id) - kept in sync via trigger';


--
-- Name: COLUMN users.client_id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.users.client_id IS 'Links user to their client company (NULL for super admins)';


--
-- Name: COLUMN users.is_super_admin; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.users.is_super_admin IS 'Super admins can access all clients';


--
-- Name: webhook_deliveries; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.webhook_deliveries (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    endpoint_id uuid NOT NULL,
    event_type text NOT NULL,
    payload jsonb NOT NULL,
    status text DEFAULT 'pending'::text NOT NULL,
    attempts integer DEFAULT 0 NOT NULL,
    last_attempt_at timestamp with time zone,
    response jsonb,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL
);


--
-- Name: webhook_endpoints; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.webhook_endpoints (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    client_id uuid NOT NULL,
    url text NOT NULL,
    secret text NOT NULL,
    events jsonb NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    retry_config jsonb,
    created_by uuid,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL
);


--
-- Name: white_label_configs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.white_label_configs (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    client_id uuid NOT NULL,
    domain text,
    logo_url text,
    favicon_url text,
    primary_color text DEFAULT '#000000'::text,
    secondary_color text DEFAULT '#ffffff'::text,
    custom_css text,
    email_from_name text,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL
);


--
-- Name: account_health account_health_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.account_health
    ADD CONSTRAINT account_health_pkey PRIMARY KEY (id);


--
-- Name: activity_logs activity_logs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.activity_logs
    ADD CONSTRAINT activity_logs_pkey PRIMARY KEY (id);


--
-- Name: ad_accounts ad_accounts_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ad_accounts
    ADD CONSTRAINT ad_accounts_pkey PRIMARY KEY (id);


--
-- Name: ad_campaigns ad_campaigns_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ad_campaigns
    ADD CONSTRAINT ad_campaigns_pkey PRIMARY KEY (id);


--
-- Name: ad_creatives ad_creatives_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ad_creatives
    ADD CONSTRAINT ad_creatives_pkey PRIMARY KEY (id);


--
-- Name: ad_metrics ad_metrics_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ad_metrics
    ADD CONSTRAINT ad_metrics_pkey PRIMARY KEY (id);


--
-- Name: ad_sets ad_sets_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ad_sets
    ADD CONSTRAINT ad_sets_pkey PRIMARY KEY (id);


--
-- Name: ads ads_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ads
    ADD CONSTRAINT ads_pkey PRIMARY KEY (id);


--
-- Name: ai_conversations ai_conversations_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ai_conversations
    ADD CONSTRAINT ai_conversations_pkey PRIMARY KEY (id);


--
-- Name: ai_insight_reports ai_insight_reports_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ai_insight_reports
    ADD CONSTRAINT ai_insight_reports_pkey PRIMARY KEY (id);


--
-- Name: ai_message_feedback ai_message_feedback_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ai_message_feedback
    ADD CONSTRAINT ai_message_feedback_pkey PRIMARY KEY (id);


--
-- Name: ai_messages ai_messages_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ai_messages
    ADD CONSTRAINT ai_messages_pkey PRIMARY KEY (id);


--
-- Name: ai_providers ai_providers_name_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ai_providers
    ADD CONSTRAINT ai_providers_name_key UNIQUE (name);


--
-- Name: ai_providers ai_providers_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ai_providers
    ADD CONSTRAINT ai_providers_pkey PRIMARY KEY (id);


--
-- Name: ai_tasks ai_tasks_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ai_tasks
    ADD CONSTRAINT ai_tasks_pkey PRIMARY KEY (id);


--
-- Name: ai_usage_tracking ai_usage_tracking_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ai_usage_tracking
    ADD CONSTRAINT ai_usage_tracking_pkey PRIMARY KEY (id);


--
-- Name: ai_workflows ai_workflows_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ai_workflows
    ADD CONSTRAINT ai_workflows_pkey PRIMARY KEY (id);


--
-- Name: announcements announcements_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.announcements
    ADD CONSTRAINT announcements_pkey PRIMARY KEY (id);


--
-- Name: automation_logs automation_logs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.automation_logs
    ADD CONSTRAINT automation_logs_pkey PRIMARY KEY (id);


--
-- Name: automation_rules automation_rules_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.automation_rules
    ADD CONSTRAINT automation_rules_pkey PRIMARY KEY (id);


--
-- Name: automation_runs automation_runs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.automation_runs
    ADD CONSTRAINT automation_runs_pkey PRIMARY KEY (id);


--
-- Name: brand_assets brand_assets_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.brand_assets
    ADD CONSTRAINT brand_assets_pkey PRIMARY KEY (id);


--
-- Name: brand_audits brand_audits_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.brand_audits
    ADD CONSTRAINT brand_audits_pkey PRIMARY KEY (id);


--
-- Name: brand_colors brand_colors_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.brand_colors
    ADD CONSTRAINT brand_colors_pkey PRIMARY KEY (id);


--
-- Name: brand_competitors brand_competitors_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.brand_competitors
    ADD CONSTRAINT brand_competitors_pkey PRIMARY KEY (id);


--
-- Name: brand_fonts brand_fonts_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.brand_fonts
    ADD CONSTRAINT brand_fonts_pkey PRIMARY KEY (id);


--
-- Name: brand_guide_sections brand_guide_sections_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.brand_guide_sections
    ADD CONSTRAINT brand_guide_sections_pkey PRIMARY KEY (id);


--
-- Name: brand_guides brand_guides_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.brand_guides
    ADD CONSTRAINT brand_guides_pkey PRIMARY KEY (id);


--
-- Name: brand_guides brand_guides_slug_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.brand_guides
    ADD CONSTRAINT brand_guides_slug_key UNIQUE (slug);


--
-- Name: brand_inconsistencies brand_inconsistencies_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.brand_inconsistencies
    ADD CONSTRAINT brand_inconsistencies_pkey PRIMARY KEY (id);


--
-- Name: brand_mentions brand_mentions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.brand_mentions
    ADD CONSTRAINT brand_mentions_pkey PRIMARY KEY (id);


--
-- Name: brand_templates brand_templates_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.brand_templates
    ADD CONSTRAINT brand_templates_pkey PRIMARY KEY (id);


--
-- Name: calendar_connections calendar_connections_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.calendar_connections
    ADD CONSTRAINT calendar_connections_pkey PRIMARY KEY (id);


--
-- Name: campaign_assets campaign_assets_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.campaign_assets
    ADD CONSTRAINT campaign_assets_pkey PRIMARY KEY (id);


--
-- Name: campaign_metrics campaign_metrics_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.campaign_metrics
    ADD CONSTRAINT campaign_metrics_pkey PRIMARY KEY (id);


--
-- Name: campaigns campaigns_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.campaigns
    ADD CONSTRAINT campaigns_pkey PRIMARY KEY (id);


--
-- Name: client_features client_features_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_features
    ADD CONSTRAINT client_features_pkey PRIMARY KEY (id);


--
-- Name: client_health_snapshots client_health_snapshots_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_health_snapshots
    ADD CONSTRAINT client_health_snapshots_pkey PRIMARY KEY (id);


--
-- Name: clients clients_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.clients
    ADD CONSTRAINT clients_pkey PRIMARY KEY (id);


--
-- Name: content_calendar_items content_calendar_items_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.content_calendar_items
    ADD CONSTRAINT content_calendar_items_pkey PRIMARY KEY (id);


--
-- Name: content_templates content_templates_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.content_templates
    ADD CONSTRAINT content_templates_pkey PRIMARY KEY (id);


--
-- Name: contracts contracts_contract_number_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contracts
    ADD CONSTRAINT contracts_contract_number_key UNIQUE (contract_number);


--
-- Name: contracts contracts_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contracts
    ADD CONSTRAINT contracts_pkey PRIMARY KEY (id);


--
-- Name: conversation_participants conversation_participants_conversation_id_user_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.conversation_participants
    ADD CONSTRAINT conversation_participants_conversation_id_user_id_unique UNIQUE (conversation_id, user_id);


--
-- Name: conversation_participants conversation_participants_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.conversation_participants
    ADD CONSTRAINT conversation_participants_pkey PRIMARY KEY (id);


--
-- Name: conversations conversations_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.conversations
    ADD CONSTRAINT conversations_pkey PRIMARY KEY (id);


--
-- Name: custom_dashboards custom_dashboards_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.custom_dashboards
    ADD CONSTRAINT custom_dashboards_pkey PRIMARY KEY (id);


--
-- Name: data_privacy_requests data_privacy_requests_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.data_privacy_requests
    ADD CONSTRAINT data_privacy_requests_pkey PRIMARY KEY (id);


--
-- Name: document_shares document_shares_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.document_shares
    ADD CONSTRAINT document_shares_pkey PRIMARY KEY (id);


--
-- Name: documents documents_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.documents
    ADD CONSTRAINT documents_pkey PRIMARY KEY (id);


--
-- Name: email_templates email_templates_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.email_templates
    ADD CONSTRAINT email_templates_pkey PRIMARY KEY (id);


--
-- Name: encrypted_settings encrypted_settings_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.encrypted_settings
    ADD CONSTRAINT encrypted_settings_pkey PRIMARY KEY (id);


--
-- Name: features features_name_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.features
    ADD CONSTRAINT features_name_key UNIQUE (name);


--
-- Name: features features_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.features
    ADD CONSTRAINT features_pkey PRIMARY KEY (id);


--
-- Name: form_templates form_templates_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.form_templates
    ADD CONSTRAINT form_templates_pkey PRIMARY KEY (id);


--
-- Name: invoice_items invoice_items_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.invoice_items
    ADD CONSTRAINT invoice_items_pkey PRIMARY KEY (id);


--
-- Name: invoice_templates invoice_templates_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.invoice_templates
    ADD CONSTRAINT invoice_templates_pkey PRIMARY KEY (id);


--
-- Name: invoices invoices_invoice_number_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.invoices
    ADD CONSTRAINT invoices_invoice_number_key UNIQUE (invoice_number);


--
-- Name: invoices invoices_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.invoices
    ADD CONSTRAINT invoices_pkey PRIMARY KEY (id);


--
-- Name: knowledge_base_articles knowledge_base_articles_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.knowledge_base_articles
    ADD CONSTRAINT knowledge_base_articles_pkey PRIMARY KEY (id);


--
-- Name: knowledge_base_articles knowledge_base_articles_slug_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.knowledge_base_articles
    ADD CONSTRAINT knowledge_base_articles_slug_key UNIQUE (slug);


--
-- Name: knowledge_base_categories knowledge_base_categories_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.knowledge_base_categories
    ADD CONSTRAINT knowledge_base_categories_pkey PRIMARY KEY (id);


--
-- Name: knowledge_base_categories knowledge_base_categories_slug_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.knowledge_base_categories
    ADD CONSTRAINT knowledge_base_categories_slug_key UNIQUE (slug);


--
-- Name: knowledge_base_feedback knowledge_base_feedback_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.knowledge_base_feedback
    ADD CONSTRAINT knowledge_base_feedback_pkey PRIMARY KEY (id);


--
-- Name: lead_activities lead_activities_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.lead_activities
    ADD CONSTRAINT lead_activities_pkey PRIMARY KEY (id);


--
-- Name: leads leads_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.leads
    ADD CONSTRAINT leads_pkey PRIMARY KEY (id);


--
-- Name: maintenance_plan_billing_history maintenance_plan_billing_history_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.maintenance_plan_billing_history
    ADD CONSTRAINT maintenance_plan_billing_history_pkey PRIMARY KEY (id);


--
-- Name: maintenance_plan_templates maintenance_plan_templates_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.maintenance_plan_templates
    ADD CONSTRAINT maintenance_plan_templates_pkey PRIMARY KEY (id);


--
-- Name: maintenance_plan_usage maintenance_plan_usage_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.maintenance_plan_usage
    ADD CONSTRAINT maintenance_plan_usage_pkey PRIMARY KEY (id);


--
-- Name: maintenance_plans maintenance_plans_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.maintenance_plans
    ADD CONSTRAINT maintenance_plans_pkey PRIMARY KEY (id);


--
-- Name: meeting_attendees meeting_attendees_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.meeting_attendees
    ADD CONSTRAINT meeting_attendees_pkey PRIMARY KEY (id);


--
-- Name: meeting_notes meeting_notes_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.meeting_notes
    ADD CONSTRAINT meeting_notes_pkey PRIMARY KEY (id);


--
-- Name: meetings meetings_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.meetings
    ADD CONSTRAINT meetings_pkey PRIMARY KEY (id);


--
-- Name: message_attachments message_attachments_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.message_attachments
    ADD CONSTRAINT message_attachments_pkey PRIMARY KEY (id);


--
-- Name: message_reads message_reads_message_id_user_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.message_reads
    ADD CONSTRAINT message_reads_message_id_user_id_unique UNIQUE (message_id, user_id);


--
-- Name: message_reads message_reads_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.message_reads
    ADD CONSTRAINT message_reads_pkey PRIMARY KEY (id);


--
-- Name: messages messages_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.messages
    ADD CONSTRAINT messages_pkey PRIMARY KEY (id);


--
-- Name: partners partners_code_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.partners
    ADD CONSTRAINT partners_code_key UNIQUE (code);


--
-- Name: partners partners_email_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.partners
    ADD CONSTRAINT partners_email_key UNIQUE (email);


--
-- Name: partners partners_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.partners
    ADD CONSTRAINT partners_pkey PRIMARY KEY (id);


--
-- Name: permissions permissions_name_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.permissions
    ADD CONSTRAINT permissions_name_key UNIQUE (name);


--
-- Name: permissions permissions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.permissions
    ADD CONSTRAINT permissions_pkey PRIMARY KEY (id);


--
-- Name: project_budgets project_budgets_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.project_budgets
    ADD CONSTRAINT project_budgets_pkey PRIMARY KEY (id);


--
-- Name: project_cost_entries project_cost_entries_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.project_cost_entries
    ADD CONSTRAINT project_cost_entries_pkey PRIMARY KEY (id);


--
-- Name: project_deliverables project_deliverables_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.project_deliverables
    ADD CONSTRAINT project_deliverables_pkey PRIMARY KEY (id);


--
-- Name: project_milestones project_milestones_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.project_milestones
    ADD CONSTRAINT project_milestones_pkey PRIMARY KEY (id);


--
-- Name: project_review_comments project_review_comments_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.project_review_comments
    ADD CONSTRAINT project_review_comments_pkey PRIMARY KEY (id);


--
-- Name: project_review_items project_review_items_id_project_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.project_review_items
    ADD CONSTRAINT project_review_items_id_project_id_unique UNIQUE (id, project_id);


--
-- Name: project_review_items project_review_items_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.project_review_items
    ADD CONSTRAINT project_review_items_pkey PRIMARY KEY (id);


--
-- Name: projects projects_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.projects
    ADD CONSTRAINT projects_pkey PRIMARY KEY (id);


--
-- Name: prompt_templates prompt_templates_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.prompt_templates
    ADD CONSTRAINT prompt_templates_pkey PRIMARY KEY (id);


--
-- Name: proposal_selections proposal_selections_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.proposal_selections
    ADD CONSTRAINT proposal_selections_pkey PRIMARY KEY (id);


--
-- Name: proposal_views proposal_views_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.proposal_views
    ADD CONSTRAINT proposal_views_pkey PRIMARY KEY (id);


--
-- Name: proposals proposals_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.proposals
    ADD CONSTRAINT proposals_pkey PRIMARY KEY (id);


--
-- Name: referrals referrals_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.referrals
    ADD CONSTRAINT referrals_pkey PRIMARY KEY (id);


--
-- Name: report_deliveries report_deliveries_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.report_deliveries
    ADD CONSTRAINT report_deliveries_pkey PRIMARY KEY (id);


--
-- Name: report_schedules report_schedules_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.report_schedules
    ADD CONSTRAINT report_schedules_pkey PRIMARY KEY (id);


--
-- Name: report_templates report_templates_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.report_templates
    ADD CONSTRAINT report_templates_pkey PRIMARY KEY (id);


--
-- Name: request_comments request_comments_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.request_comments
    ADD CONSTRAINT request_comments_pkey PRIMARY KEY (id);


--
-- Name: request_time_entries request_time_entries_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.request_time_entries
    ADD CONSTRAINT request_time_entries_pkey PRIMARY KEY (id);


--
-- Name: requests requests_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.requests
    ADD CONSTRAINT requests_pkey PRIMARY KEY (id);


--
-- Name: role_features role_features_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.role_features
    ADD CONSTRAINT role_features_pkey PRIMARY KEY (id);


--
-- Name: role_permissions role_permissions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.role_permissions
    ADD CONSTRAINT role_permissions_pkey PRIMARY KEY (role_id, permission_id);


--
-- Name: roles roles_name_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.roles
    ADD CONSTRAINT roles_name_key UNIQUE (name);


--
-- Name: roles roles_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.roles
    ADD CONSTRAINT roles_pkey PRIMARY KEY (id);


--
-- Name: service_templates service_templates_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.service_templates
    ADD CONSTRAINT service_templates_pkey PRIMARY KEY (id);


--
-- Name: social_accounts social_accounts_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.social_accounts
    ADD CONSTRAINT social_accounts_pkey PRIMARY KEY (id);


--
-- Name: social_posts social_posts_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.social_posts
    ADD CONSTRAINT social_posts_pkey PRIMARY KEY (id);


--
-- Name: staff_assignments staff_assignments_client_id_user_id_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.staff_assignments
    ADD CONSTRAINT staff_assignments_client_id_user_id_key UNIQUE (client_id, user_id);


--
-- Name: staff_assignments staff_assignments_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.staff_assignments
    ADD CONSTRAINT staff_assignments_pkey PRIMARY KEY (id);


--
-- Name: staff_guide_categories staff_guide_categories_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.staff_guide_categories
    ADD CONSTRAINT staff_guide_categories_pkey PRIMARY KEY (id);


--
-- Name: staff_guide_categories staff_guide_categories_slug_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.staff_guide_categories
    ADD CONSTRAINT staff_guide_categories_slug_key UNIQUE (slug);


--
-- Name: staff_guide_views staff_guide_views_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.staff_guide_views
    ADD CONSTRAINT staff_guide_views_pkey PRIMARY KEY (id);


--
-- Name: staff_guides staff_guides_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.staff_guides
    ADD CONSTRAINT staff_guides_pkey PRIMARY KEY (id);


--
-- Name: staff_guides staff_guides_slug_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.staff_guides
    ADD CONSTRAINT staff_guides_slug_key UNIQUE (slug);


--
-- Name: staff_task_assignees staff_task_assignees_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.staff_task_assignees
    ADD CONSTRAINT staff_task_assignees_pkey PRIMARY KEY (id);


--
-- Name: staff_task_boards staff_task_boards_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.staff_task_boards
    ADD CONSTRAINT staff_task_boards_pkey PRIMARY KEY (id);


--
-- Name: staff_task_checklists staff_task_checklists_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.staff_task_checklists
    ADD CONSTRAINT staff_task_checklists_pkey PRIMARY KEY (id);


--
-- Name: staff_task_columns staff_task_columns_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.staff_task_columns
    ADD CONSTRAINT staff_task_columns_pkey PRIMARY KEY (id);


--
-- Name: staff_task_comments staff_task_comments_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.staff_task_comments
    ADD CONSTRAINT staff_task_comments_pkey PRIMARY KEY (id);


--
-- Name: staff_task_label_relations staff_task_label_relations_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.staff_task_label_relations
    ADD CONSTRAINT staff_task_label_relations_pkey PRIMARY KEY (task_id, label_id);


--
-- Name: staff_task_labels staff_task_labels_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.staff_task_labels
    ADD CONSTRAINT staff_task_labels_pkey PRIMARY KEY (id);


--
-- Name: staff_tasks staff_tasks_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.staff_tasks
    ADD CONSTRAINT staff_tasks_pkey PRIMARY KEY (id);


--
-- Name: storage_connections storage_connections_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.storage_connections
    ADD CONSTRAINT storage_connections_pkey PRIMARY KEY (id);


--
-- Name: storage_files storage_files_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.storage_files
    ADD CONSTRAINT storage_files_pkey PRIMARY KEY (id);


--
-- Name: support_ticket_comments support_ticket_comments_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.support_ticket_comments
    ADD CONSTRAINT support_ticket_comments_pkey PRIMARY KEY (id);


--
-- Name: support_tickets support_tickets_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.support_tickets
    ADD CONSTRAINT support_tickets_pkey PRIMARY KEY (id);


--
-- Name: support_tickets support_tickets_ticket_number_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.support_tickets
    ADD CONSTRAINT support_tickets_ticket_number_key UNIQUE (ticket_number);


--
-- Name: survey_answers survey_answers_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.survey_answers
    ADD CONSTRAINT survey_answers_pkey PRIMARY KEY (id);


--
-- Name: survey_questions survey_questions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.survey_questions
    ADD CONSTRAINT survey_questions_pkey PRIMARY KEY (id);


--
-- Name: survey_responses survey_responses_anonymous_token_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.survey_responses
    ADD CONSTRAINT survey_responses_anonymous_token_key UNIQUE (anonymous_token);


--
-- Name: survey_responses survey_responses_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.survey_responses
    ADD CONSTRAINT survey_responses_pkey PRIMARY KEY (id);


--
-- Name: surveys surveys_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.surveys
    ADD CONSTRAINT surveys_pkey PRIMARY KEY (id);


--
-- Name: system_settings system_settings_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.system_settings
    ADD CONSTRAINT system_settings_pkey PRIMARY KEY (id);


--
-- Name: time_entries time_entries_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.time_entries
    ADD CONSTRAINT time_entries_pkey PRIMARY KEY (id);


--
-- Name: time_entry_locks time_entry_locks_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.time_entry_locks
    ADD CONSTRAINT time_entry_locks_pkey PRIMARY KEY (id);


--
-- Name: time_entry_locks time_entry_locks_user_id_period_start_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.time_entry_locks
    ADD CONSTRAINT time_entry_locks_user_id_period_start_key UNIQUE (user_id, period_start);


--
-- Name: client_features unique_client_feature; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_features
    ADD CONSTRAINT unique_client_feature UNIQUE (client_id, feature_id);


--
-- Name: role_features unique_role_feature; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.role_features
    ADD CONSTRAINT unique_role_feature UNIQUE (role_id, feature_id);


--
-- Name: user_features unique_user_feature; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_features
    ADD CONSTRAINT unique_user_feature UNIQUE (user_id, feature_id);


--
-- Name: user_features user_features_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_features
    ADD CONSTRAINT user_features_pkey PRIMARY KEY (id);


--
-- Name: user_roles user_roles_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_roles
    ADD CONSTRAINT user_roles_pkey PRIMARY KEY (user_id, role_id);


--
-- Name: users users_email_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_email_key UNIQUE (email);


--
-- Name: users users_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_pkey PRIMARY KEY (id);


--
-- Name: webhook_deliveries webhook_deliveries_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.webhook_deliveries
    ADD CONSTRAINT webhook_deliveries_pkey PRIMARY KEY (id);


--
-- Name: webhook_endpoints webhook_endpoints_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.webhook_endpoints
    ADD CONSTRAINT webhook_endpoints_pkey PRIMARY KEY (id);


--
-- Name: white_label_configs white_label_configs_client_id_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.white_label_configs
    ADD CONSTRAINT white_label_configs_client_id_key UNIQUE (client_id);


--
-- Name: white_label_configs white_label_configs_domain_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.white_label_configs
    ADD CONSTRAINT white_label_configs_domain_key UNIQUE (domain);


--
-- Name: white_label_configs white_label_configs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.white_label_configs
    ADD CONSTRAINT white_label_configs_pkey PRIMARY KEY (id);


--
-- Name: calendar_connections_user_provider_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE UNIQUE INDEX calendar_connections_user_provider_idx ON public.calendar_connections USING btree (user_id, provider);


--
-- Name: conversations_client_id_is_closed_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX conversations_client_id_is_closed_idx ON public.conversations USING btree (client_id, is_closed);


--
-- Name: conversations_client_id_last_message_at_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX conversations_client_id_last_message_at_idx ON public.conversations USING btree (client_id, last_message_at);


--
-- Name: conversations_context_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX conversations_context_idx ON public.conversations USING btree (client_id, context_type, context_id);


--
-- Name: encrypted_settings_client_provider_key; Type: INDEX; Schema: public; Owner: -
--

CREATE UNIQUE INDEX encrypted_settings_client_provider_key ON public.encrypted_settings USING btree (client_id, provider, setting_key);


--
-- Name: idx_activity_logs_causer_id; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_activity_logs_causer_id ON public.activity_logs USING btree (causer_id);


--
-- Name: idx_activity_logs_client_id; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_activity_logs_client_id ON public.activity_logs USING btree (client_id);


--
-- Name: idx_activity_logs_client_id_created_at; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_activity_logs_client_id_created_at ON public.activity_logs USING btree (client_id, created_at DESC);


--
-- Name: idx_activity_logs_created_at; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_activity_logs_created_at ON public.activity_logs USING btree (created_at DESC);


--
-- Name: idx_activity_logs_subject_id; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_activity_logs_subject_id ON public.activity_logs USING btree (subject_id);


--
-- Name: idx_activity_logs_subject_type; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_activity_logs_subject_type ON public.activity_logs USING btree (subject_type);


--
-- Name: idx_announcements_client_id; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_announcements_client_id ON public.announcements USING btree (client_id) WHERE (deleted_at IS NULL);


--
-- Name: idx_announcements_created_at; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_announcements_created_at ON public.announcements USING btree (created_at DESC) WHERE (deleted_at IS NULL);


--
-- Name: idx_announcements_expires_at; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_announcements_expires_at ON public.announcements USING btree (expires_at) WHERE ((expires_at IS NOT NULL) AND (deleted_at IS NULL));


--
-- Name: idx_announcements_is_active; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_announcements_is_active ON public.announcements USING btree (is_active) WHERE ((is_active = true) AND (deleted_at IS NULL));


--
-- Name: idx_announcements_priority; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_announcements_priority ON public.announcements USING btree (priority DESC) WHERE ((is_active = true) AND (deleted_at IS NULL));


--
-- Name: idx_client_features_client_id; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_client_features_client_id ON public.client_features USING btree (client_id);


--
-- Name: idx_client_features_feature_id; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_client_features_feature_id ON public.client_features USING btree (feature_id);


--
-- Name: idx_clients_company_name; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_clients_company_name ON public.clients USING btree (company_name) WHERE (deleted_at IS NULL);


--
-- Name: idx_clients_domain; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_clients_domain ON public.clients USING btree (domain) WHERE (deleted_at IS NULL);


--
-- Name: idx_clients_email; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_clients_email ON public.clients USING btree (email) WHERE (deleted_at IS NULL);


--
-- Name: idx_clients_industry; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_clients_industry ON public.clients USING btree (industry) WHERE (deleted_at IS NULL);


--
-- Name: idx_clients_logo_url; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_clients_logo_url ON public.clients USING btree (logo_url) WHERE (deleted_at IS NULL);


--
-- Name: idx_clients_primary_contact_id; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_clients_primary_contact_id ON public.clients USING btree (primary_contact_id) WHERE (deleted_at IS NULL);


--
-- Name: idx_clients_status; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_clients_status ON public.clients USING btree (status) WHERE (deleted_at IS NULL);


--
-- Name: idx_contracts_client_id; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_contracts_client_id ON public.contracts USING btree (client_id) WHERE (deleted_at IS NULL);


--
-- Name: idx_contracts_end_date; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_contracts_end_date ON public.contracts USING btree (end_date) WHERE (deleted_at IS NULL);


--
-- Name: idx_contracts_status; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_contracts_status ON public.contracts USING btree (status) WHERE (deleted_at IS NULL);


--
-- Name: idx_contracts_type; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_contracts_type ON public.contracts USING btree (type) WHERE (deleted_at IS NULL);


--
-- Name: idx_document_shares_document_id; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_document_shares_document_id ON public.document_shares USING btree (document_id);


--
-- Name: idx_document_shares_email; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_document_shares_email ON public.document_shares USING btree (shared_with_email);


--
-- Name: idx_document_shares_expires; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_document_shares_expires ON public.document_shares USING btree (expires_at);


--
-- Name: idx_document_shares_user_id; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_document_shares_user_id ON public.document_shares USING btree (shared_with_user_id);


--
-- Name: idx_documents_client_id; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_documents_client_id ON public.documents USING btree (client_id) WHERE (deleted_at IS NULL);


--
-- Name: idx_documents_created_at; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_documents_created_at ON public.documents USING btree (created_at DESC) WHERE (deleted_at IS NULL);


--
-- Name: idx_documents_is_latest; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_documents_is_latest ON public.documents USING btree (is_latest_version) WHERE ((is_latest_version = true) AND (deleted_at IS NULL));


--
-- Name: idx_documents_parent_id; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_documents_parent_id ON public.documents USING btree (parent_document_id) WHERE (deleted_at IS NULL);


--
-- Name: idx_documents_request_id; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_documents_request_id ON public.documents USING btree (request_id) WHERE (deleted_at IS NULL);


--
-- Name: idx_documents_uploaded_by; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_documents_uploaded_by ON public.documents USING btree (uploaded_by) WHERE (deleted_at IS NULL);


--
-- Name: idx_email_templates_is_default; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_email_templates_is_default ON public.email_templates USING btree (is_default, type) WHERE ((is_default = true) AND (deleted_at IS NULL));


--
-- Name: idx_email_templates_type; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_email_templates_type ON public.email_templates USING btree (type) WHERE (deleted_at IS NULL);


--
-- Name: idx_encrypted_settings_category; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_encrypted_settings_category ON public.encrypted_settings USING btree (client_id, category);


--
-- Name: idx_encrypted_settings_client_id; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_encrypted_settings_client_id ON public.encrypted_settings USING btree (client_id);


--
-- Name: idx_encrypted_settings_provider; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_encrypted_settings_provider ON public.encrypted_settings USING btree (client_id, provider);


--
-- Name: idx_features_category; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_features_category ON public.features USING btree (category);


--
-- Name: idx_features_name; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_features_name ON public.features USING btree (name);


--
-- Name: idx_invoice_items_invoice_id; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_invoice_items_invoice_id ON public.invoice_items USING btree (invoice_id);


--
-- Name: idx_invoice_templates_is_active; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_invoice_templates_is_active ON public.invoice_templates USING btree (is_active) WHERE ((is_active = true) AND (deleted_at IS NULL));


--
-- Name: idx_invoice_templates_is_default; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_invoice_templates_is_default ON public.invoice_templates USING btree (is_default) WHERE ((is_default = true) AND (deleted_at IS NULL));


--
-- Name: idx_invoices_client_id; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_invoices_client_id ON public.invoices USING btree (client_id) WHERE (deleted_at IS NULL);


--
-- Name: idx_invoices_created_by; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_invoices_created_by ON public.invoices USING btree (created_by);


--
-- Name: idx_invoices_due_date; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_invoices_due_date ON public.invoices USING btree (due_date) WHERE (deleted_at IS NULL);


--
-- Name: idx_invoices_invoice_number; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_invoices_invoice_number ON public.invoices USING btree (invoice_number);


--
-- Name: idx_invoices_status; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_invoices_status ON public.invoices USING btree (status) WHERE (deleted_at IS NULL);


--
-- Name: idx_maintenance_plan_templates_created_at; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_maintenance_plan_templates_created_at ON public.maintenance_plan_templates USING btree (created_at DESC);


--
-- Name: idx_maintenance_plan_templates_created_by; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_maintenance_plan_templates_created_by ON public.maintenance_plan_templates USING btree (created_by);


--
-- Name: idx_maintenance_plan_templates_is_active; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_maintenance_plan_templates_is_active ON public.maintenance_plan_templates USING btree (is_active);


--
-- Name: idx_maintenance_plan_templates_plan_type; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_maintenance_plan_templates_plan_type ON public.maintenance_plan_templates USING btree (plan_type);


--
-- Name: idx_maintenance_plan_usage_plan_id_logged_at; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_maintenance_plan_usage_plan_id_logged_at ON public.maintenance_plan_usage USING btree (plan_id, logged_at) WHERE (deleted_at IS NULL);


--
-- Name: idx_maintenance_plans_client_id_status; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_maintenance_plans_client_id_status ON public.maintenance_plans USING btree (client_id, status) WHERE (deleted_at IS NULL);


--
-- Name: idx_maintenance_plans_next_billing_date; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_maintenance_plans_next_billing_date ON public.maintenance_plans USING btree (next_billing_date) WHERE ((deleted_at IS NULL) AND (status = 'active'::text));


--
-- Name: idx_maintenance_plans_template_id; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_maintenance_plans_template_id ON public.maintenance_plans USING btree (template_id) WHERE (template_id IS NOT NULL);


--
-- Name: idx_project_budgets_category; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_project_budgets_category ON public.project_budgets USING btree (category);


--
-- Name: idx_project_budgets_project_id; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_project_budgets_project_id ON public.project_budgets USING btree (project_id);


--
-- Name: idx_project_cost_entries_budget_id; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_project_cost_entries_budget_id ON public.project_cost_entries USING btree (budget_id);


--
-- Name: idx_project_cost_entries_entry_date; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_project_cost_entries_entry_date ON public.project_cost_entries USING btree (entry_date);


--
-- Name: idx_project_cost_entries_project_id; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_project_cost_entries_project_id ON public.project_cost_entries USING btree (project_id);


--
-- Name: idx_project_cost_entries_user_id; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_project_cost_entries_user_id ON public.project_cost_entries USING btree (user_id);


--
-- Name: idx_project_deliverables_due_date; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_project_deliverables_due_date ON public.project_deliverables USING btree (due_date);


--
-- Name: idx_project_deliverables_milestone_id; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_project_deliverables_milestone_id ON public.project_deliverables USING btree (milestone_id);


--
-- Name: idx_project_deliverables_project_id; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_project_deliverables_project_id ON public.project_deliverables USING btree (project_id);


--
-- Name: idx_project_deliverables_status; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_project_deliverables_status ON public.project_deliverables USING btree (status);


--
-- Name: idx_project_milestones_due_date; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_project_milestones_due_date ON public.project_milestones USING btree (due_date);


--
-- Name: idx_project_milestones_project_id; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_project_milestones_project_id ON public.project_milestones USING btree (project_id);


--
-- Name: idx_project_milestones_sort_order; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_project_milestones_sort_order ON public.project_milestones USING btree (sort_order);


--
-- Name: idx_projects_client_id; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_projects_client_id ON public.projects USING btree (client_id) WHERE (deleted_at IS NULL);


--
-- Name: idx_projects_client_id_status; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_projects_client_id_status ON public.projects USING btree (client_id, status) WHERE (deleted_at IS NULL);


--
-- Name: idx_projects_created_at; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_projects_created_at ON public.projects USING btree (created_at DESC) WHERE (deleted_at IS NULL);


--
-- Name: idx_projects_end_date; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_projects_end_date ON public.projects USING btree (end_date) WHERE (deleted_at IS NULL);


--
-- Name: idx_projects_project_manager_id; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_projects_project_manager_id ON public.projects USING btree (project_manager_id);


--
-- Name: idx_projects_start_date; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_projects_start_date ON public.projects USING btree (start_date) WHERE (deleted_at IS NULL);


--
-- Name: idx_projects_status; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_projects_status ON public.projects USING btree (status) WHERE (deleted_at IS NULL);


--
-- Name: idx_proposal_selections_proposal_id; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_proposal_selections_proposal_id ON public.proposal_selections USING btree (proposal_id);


--
-- Name: idx_proposal_views_ip; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_proposal_views_ip ON public.proposal_views USING btree (viewed_by_ip);


--
-- Name: idx_proposal_views_proposal_id; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_proposal_views_proposal_id ON public.proposal_views USING btree (proposal_id);


--
-- Name: idx_proposal_views_user_id; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_proposal_views_user_id ON public.proposal_views USING btree (viewed_by_user_id);


--
-- Name: idx_proposal_views_viewed_at; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_proposal_views_viewed_at ON public.proposal_views USING btree (viewed_at);


--
-- Name: idx_proposals_client_id; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_proposals_client_id ON public.proposals USING btree (client_id) WHERE (deleted_at IS NULL);


--
-- Name: idx_proposals_created_by; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_proposals_created_by ON public.proposals USING btree (created_by);


--
-- Name: idx_proposals_sent_at; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_proposals_sent_at ON public.proposals USING btree (sent_at) WHERE (sent_at IS NOT NULL);


--
-- Name: idx_proposals_service_template_id; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_proposals_service_template_id ON public.proposals USING btree (service_template_id) WHERE (service_template_id IS NOT NULL);


--
-- Name: idx_proposals_status; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_proposals_status ON public.proposals USING btree (status) WHERE (deleted_at IS NULL);


--
-- Name: idx_proposals_valid_until; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_proposals_valid_until ON public.proposals USING btree (valid_until) WHERE (valid_until IS NOT NULL);


--
-- Name: idx_request_comments_request_id; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_request_comments_request_id ON public.request_comments USING btree (request_id);


--
-- Name: idx_request_comments_user_id; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_request_comments_user_id ON public.request_comments USING btree (user_id);


--
-- Name: idx_request_time_entries_request_id; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_request_time_entries_request_id ON public.request_time_entries USING btree (request_id);


--
-- Name: idx_request_time_entries_request_logged_at; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_request_time_entries_request_logged_at ON public.request_time_entries USING btree (request_id, logged_at);


--
-- Name: idx_request_time_entries_user_id; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_request_time_entries_user_id ON public.request_time_entries USING btree (user_id);


--
-- Name: idx_request_time_entries_user_logged_at; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_request_time_entries_user_logged_at ON public.request_time_entries USING btree (user_id, logged_at);


--
-- Name: idx_requests_assigned_to; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_requests_assigned_to ON public.requests USING btree (assigned_to) WHERE (deleted_at IS NULL);


--
-- Name: idx_requests_client_id; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_requests_client_id ON public.requests USING btree (client_id) WHERE (deleted_at IS NULL);


--
-- Name: idx_requests_created_by; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_requests_created_by ON public.requests USING btree (created_by) WHERE (deleted_at IS NULL);


--
-- Name: idx_requests_priority; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_requests_priority ON public.requests USING btree (priority) WHERE (deleted_at IS NULL);


--
-- Name: idx_requests_status; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_requests_status ON public.requests USING btree (status) WHERE (deleted_at IS NULL);


--
-- Name: idx_role_features_feature_id; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_role_features_feature_id ON public.role_features USING btree (feature_id);


--
-- Name: idx_role_features_role_id; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_role_features_role_id ON public.role_features USING btree (role_id);


--
-- Name: idx_role_permissions_permission_id; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_role_permissions_permission_id ON public.role_permissions USING btree (permission_id);


--
-- Name: idx_role_permissions_role_id; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_role_permissions_role_id ON public.role_permissions USING btree (role_id);


--
-- Name: idx_service_templates_active; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_service_templates_active ON public.service_templates USING btree (is_active);


--
-- Name: idx_service_templates_category; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_service_templates_category ON public.service_templates USING btree (category) WHERE (category IS NOT NULL);


--
-- Name: idx_service_templates_created_by; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_service_templates_created_by ON public.service_templates USING btree (created_by);


--
-- Name: idx_staff_assignments_client_id; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_staff_assignments_client_id ON public.staff_assignments USING btree (client_id);


--
-- Name: idx_staff_assignments_user_id; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_staff_assignments_user_id ON public.staff_assignments USING btree (user_id);


--
-- Name: idx_staff_task_assignees_task_id; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_staff_task_assignees_task_id ON public.staff_task_assignees USING btree (task_id);


--
-- Name: idx_staff_task_assignees_user_id; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_staff_task_assignees_user_id ON public.staff_task_assignees USING btree (user_id);


--
-- Name: idx_staff_task_boards_is_archived; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_staff_task_boards_is_archived ON public.staff_task_boards USING btree (is_archived);


--
-- Name: idx_staff_task_boards_is_default_sort_order; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_staff_task_boards_is_default_sort_order ON public.staff_task_boards USING btree (is_default DESC, sort_order DESC);


--
-- Name: idx_staff_task_checklists_task_id_position; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_staff_task_checklists_task_id_position ON public.staff_task_checklists USING btree (task_id, "position");


--
-- Name: idx_staff_task_columns_board_id_position; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_staff_task_columns_board_id_position ON public.staff_task_columns USING btree (board_id, "position");


--
-- Name: idx_staff_task_comments_task_id_created_at; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_staff_task_comments_task_id_created_at ON public.staff_task_comments USING btree (task_id, created_at DESC);


--
-- Name: idx_staff_task_label_relations_label_id; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_staff_task_label_relations_label_id ON public.staff_task_label_relations USING btree (label_id);


--
-- Name: idx_staff_task_labels_board_id; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_staff_task_labels_board_id ON public.staff_task_labels USING btree (board_id);


--
-- Name: idx_staff_tasks_board_id; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_staff_tasks_board_id ON public.staff_tasks USING btree (board_id);


--
-- Name: idx_staff_tasks_column_id_position; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_staff_tasks_column_id_position ON public.staff_tasks USING btree (column_id, "position");


--
-- Name: idx_staff_tasks_parent_id; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_staff_tasks_parent_id ON public.staff_tasks USING btree (parent_id) WHERE (parent_id IS NOT NULL);


--
-- Name: idx_support_ticket_comments_created_at; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_support_ticket_comments_created_at ON public.support_ticket_comments USING btree (created_at);


--
-- Name: idx_support_ticket_comments_ticket_id; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_support_ticket_comments_ticket_id ON public.support_ticket_comments USING btree (support_ticket_id);


--
-- Name: idx_support_ticket_comments_ticket_id_created_at; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_support_ticket_comments_ticket_id_created_at ON public.support_ticket_comments USING btree (support_ticket_id, created_at DESC) WHERE (deleted_at IS NULL);


--
-- Name: idx_support_ticket_comments_user_id; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_support_ticket_comments_user_id ON public.support_ticket_comments USING btree (user_id);


--
-- Name: idx_support_tickets_assigned_to; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_support_tickets_assigned_to ON public.support_tickets USING btree (assigned_to);


--
-- Name: idx_support_tickets_assigned_to_status; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_support_tickets_assigned_to_status ON public.support_tickets USING btree (assigned_to, status) WHERE (deleted_at IS NULL);


--
-- Name: idx_support_tickets_category; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_support_tickets_category ON public.support_tickets USING btree (category);


--
-- Name: idx_support_tickets_client_id; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_support_tickets_client_id ON public.support_tickets USING btree (client_id);


--
-- Name: idx_support_tickets_client_id_status_priority; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_support_tickets_client_id_status_priority ON public.support_tickets USING btree (client_id, status, priority DESC) WHERE (deleted_at IS NULL);


--
-- Name: idx_support_tickets_created_at; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_support_tickets_created_at ON public.support_tickets USING btree (created_at);


--
-- Name: idx_support_tickets_created_by; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_support_tickets_created_by ON public.support_tickets USING btree (created_by);


--
-- Name: idx_support_tickets_deleted_at; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_support_tickets_deleted_at ON public.support_tickets USING btree (deleted_at) WHERE (deleted_at IS NULL);


--
-- Name: idx_support_tickets_priority; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_support_tickets_priority ON public.support_tickets USING btree (priority);


--
-- Name: idx_support_tickets_sla_resolution_due_at; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_support_tickets_sla_resolution_due_at ON public.support_tickets USING btree (sla_resolution_due_at) WHERE ((deleted_at IS NULL) AND (sla_resolution_breached = false));


--
-- Name: idx_support_tickets_sla_response_due_at; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_support_tickets_sla_response_due_at ON public.support_tickets USING btree (sla_response_due_at) WHERE ((deleted_at IS NULL) AND (sla_response_breached = false));


--
-- Name: idx_support_tickets_status; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_support_tickets_status ON public.support_tickets USING btree (status);


--
-- Name: idx_support_tickets_ticket_number; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_support_tickets_ticket_number ON public.support_tickets USING btree (ticket_number);


--
-- Name: idx_time_entries_client_id; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_time_entries_client_id ON public.time_entries USING btree (client_id);


--
-- Name: idx_time_entries_client_started_at; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_time_entries_client_started_at ON public.time_entries USING btree (client_id, started_at);


--
-- Name: idx_time_entries_request_id; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_time_entries_request_id ON public.time_entries USING btree (request_id);


--
-- Name: idx_time_entries_request_started_at; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_time_entries_request_started_at ON public.time_entries USING btree (request_id, started_at);


--
-- Name: idx_time_entries_started_at; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_time_entries_started_at ON public.time_entries USING btree (started_at);


--
-- Name: idx_time_entries_status; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_time_entries_status ON public.time_entries USING btree (status);


--
-- Name: idx_time_entries_user_id; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_time_entries_user_id ON public.time_entries USING btree (user_id);


--
-- Name: idx_time_entries_user_started_at; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_time_entries_user_started_at ON public.time_entries USING btree (user_id, started_at);


--
-- Name: idx_time_entry_locks_period; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_time_entry_locks_period ON public.time_entry_locks USING btree (period_start, period_end);


--
-- Name: idx_time_entry_locks_user_id; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_time_entry_locks_user_id ON public.time_entry_locks USING btree (user_id);


--
-- Name: idx_user_features_feature_id; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_user_features_feature_id ON public.user_features USING btree (feature_id);


--
-- Name: idx_user_features_user_id; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_user_features_user_id ON public.user_features USING btree (user_id);


--
-- Name: idx_user_roles_role_id; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_user_roles_role_id ON public.user_roles USING btree (role_id);


--
-- Name: idx_user_roles_user_id; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_user_roles_user_id ON public.user_roles USING btree (user_id);


--
-- Name: idx_users_client_id; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_users_client_id ON public.users USING btree (client_id) WHERE (deleted_at IS NULL);


--
-- Name: idx_users_email; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_users_email ON public.users USING btree (email) WHERE (deleted_at IS NULL);


--
-- Name: idx_users_status; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_users_status ON public.users USING btree (status) WHERE (deleted_at IS NULL);


--
-- Name: kb_articles_category_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX kb_articles_category_idx ON public.knowledge_base_articles USING btree (category_id);


--
-- Name: kb_articles_published_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX kb_articles_published_idx ON public.knowledge_base_articles USING btree (is_published);


--
-- Name: kb_articles_slug_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE UNIQUE INDEX kb_articles_slug_idx ON public.knowledge_base_articles USING btree (slug);


--
-- Name: kb_categories_parent_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX kb_categories_parent_idx ON public.knowledge_base_categories USING btree (parent_id);


--
-- Name: kb_categories_slug_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE UNIQUE INDEX kb_categories_slug_idx ON public.knowledge_base_categories USING btree (slug);


--
-- Name: kb_feedback_article_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX kb_feedback_article_idx ON public.knowledge_base_feedback USING btree (article_id);


--
-- Name: message_reads_user_id_read_at_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX message_reads_user_id_read_at_idx ON public.message_reads USING btree (user_id, read_at);


--
-- Name: messages_conversation_id_created_at_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX messages_conversation_id_created_at_idx ON public.messages USING btree (conversation_id, created_at);


--
-- Name: partners_code_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE UNIQUE INDEX partners_code_idx ON public.partners USING btree (code);


--
-- Name: partners_email_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX partners_email_idx ON public.partners USING btree (email);


--
-- Name: partners_status_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX partners_status_idx ON public.partners USING btree (status);


--
-- Name: project_review_comments_project_id_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX project_review_comments_project_id_idx ON public.project_review_comments USING btree (project_id);


--
-- Name: project_review_comments_review_item_id_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX project_review_comments_review_item_id_idx ON public.project_review_comments USING btree (review_item_id);


--
-- Name: project_review_comments_status_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX project_review_comments_status_idx ON public.project_review_comments USING btree (status);


--
-- Name: project_review_items_id_project_id_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE UNIQUE INDEX project_review_items_id_project_id_idx ON public.project_review_items USING btree (id, project_id);


--
-- Name: project_review_items_project_id_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX project_review_items_project_id_idx ON public.project_review_items USING btree (project_id);


--
-- Name: project_review_items_status_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX project_review_items_status_idx ON public.project_review_items USING btree (status);


--
-- Name: referrals_code_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX referrals_code_idx ON public.referrals USING btree (referral_code);


--
-- Name: referrals_converted_client_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX referrals_converted_client_idx ON public.referrals USING btree (converted_client_id);


--
-- Name: referrals_partner_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX referrals_partner_idx ON public.referrals USING btree (partner_id);


--
-- Name: referrals_status_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX referrals_status_idx ON public.referrals USING btree (status);


--
-- Name: staff_guide_categories_slug_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE UNIQUE INDEX staff_guide_categories_slug_idx ON public.staff_guide_categories USING btree (slug);


--
-- Name: staff_guide_views_guide_user_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX staff_guide_views_guide_user_idx ON public.staff_guide_views USING btree (guide_id, user_id);


--
-- Name: staff_guides_category_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX staff_guides_category_idx ON public.staff_guides USING btree (category_id);


--
-- Name: staff_guides_published_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX staff_guides_published_idx ON public.staff_guides USING btree (is_published);


--
-- Name: staff_guides_service_tier_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX staff_guides_service_tier_idx ON public.staff_guides USING btree (service_tier);


--
-- Name: staff_guides_slug_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE UNIQUE INDEX staff_guides_slug_idx ON public.staff_guides USING btree (slug);


--
-- Name: survey_answers_response_question_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX survey_answers_response_question_idx ON public.survey_answers USING btree (response_id, question_id);


--
-- Name: survey_questions_survey_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX survey_questions_survey_idx ON public.survey_questions USING btree (survey_id);


--
-- Name: survey_responses_respondent_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX survey_responses_respondent_idx ON public.survey_responses USING btree (respondent_id);


--
-- Name: survey_responses_survey_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX survey_responses_survey_idx ON public.survey_responses USING btree (survey_id);


--
-- Name: surveys_active_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX surveys_active_idx ON public.surveys USING btree (is_active);


--
-- Name: surveys_client_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX surveys_client_idx ON public.surveys USING btree (client_id);


--
-- Name: system_settings_category_key_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE UNIQUE INDEX system_settings_category_key_idx ON public.system_settings USING btree (category, key);


--
-- Name: documents document_versioning; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER document_versioning BEFORE INSERT ON public.documents FOR EACH ROW EXECUTE FUNCTION public.handle_document_version();


--
-- Name: announcements trigger_announcements_updated_at; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER trigger_announcements_updated_at BEFORE UPDATE ON public.announcements FOR EACH ROW EXECUTE FUNCTION public.update_announcements_updated_at();


--
-- Name: clients trigger_clients_updated_at; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER trigger_clients_updated_at BEFORE UPDATE ON public.clients FOR EACH ROW EXECUTE FUNCTION public.update_core_tables_updated_at();


--
-- Name: invoice_items trigger_invoice_items_updated_at; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER trigger_invoice_items_updated_at BEFORE UPDATE ON public.invoice_items FOR EACH ROW EXECUTE FUNCTION public.update_application_tables_updated_at();


--
-- Name: invoices trigger_invoices_updated_at; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER trigger_invoices_updated_at BEFORE UPDATE ON public.invoices FOR EACH ROW EXECUTE FUNCTION public.update_application_tables_updated_at();


--
-- Name: project_budgets trigger_project_budgets_updated_at; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER trigger_project_budgets_updated_at BEFORE UPDATE ON public.project_budgets FOR EACH ROW EXECUTE FUNCTION public.update_application_tables_updated_at();


--
-- Name: project_cost_entries trigger_project_cost_entries_updated_at; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER trigger_project_cost_entries_updated_at BEFORE UPDATE ON public.project_cost_entries FOR EACH ROW EXECUTE FUNCTION public.update_application_tables_updated_at();


--
-- Name: project_deliverables trigger_project_deliverables_updated_at; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER trigger_project_deliverables_updated_at BEFORE UPDATE ON public.project_deliverables FOR EACH ROW EXECUTE FUNCTION public.update_application_tables_updated_at();


--
-- Name: project_milestones trigger_project_milestones_updated_at; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER trigger_project_milestones_updated_at BEFORE UPDATE ON public.project_milestones FOR EACH ROW EXECUTE FUNCTION public.update_application_tables_updated_at();


--
-- Name: project_review_comments trigger_project_review_comments_updated_at; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER trigger_project_review_comments_updated_at BEFORE UPDATE ON public.project_review_comments FOR EACH ROW EXECUTE FUNCTION public.update_application_tables_updated_at();


--
-- Name: project_review_items trigger_project_review_items_updated_at; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER trigger_project_review_items_updated_at BEFORE UPDATE ON public.project_review_items FOR EACH ROW EXECUTE FUNCTION public.update_application_tables_updated_at();


--
-- Name: projects trigger_projects_updated_at; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER trigger_projects_updated_at BEFORE UPDATE ON public.projects FOR EACH ROW EXECUTE FUNCTION public.update_application_tables_updated_at();


--
-- Name: proposal_selections trigger_proposal_selections_updated_at; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER trigger_proposal_selections_updated_at BEFORE UPDATE ON public.proposal_selections FOR EACH ROW EXECUTE FUNCTION public.update_application_tables_updated_at();


--
-- Name: proposals trigger_proposals_updated_at; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER trigger_proposals_updated_at BEFORE UPDATE ON public.proposals FOR EACH ROW EXECUTE FUNCTION public.update_application_tables_updated_at();


--
-- Name: request_comments trigger_request_comments_updated_at; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER trigger_request_comments_updated_at BEFORE UPDATE ON public.request_comments FOR EACH ROW EXECUTE FUNCTION public.update_application_tables_updated_at();


--
-- Name: request_time_entries trigger_request_time_entries_updated_at; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER trigger_request_time_entries_updated_at BEFORE UPDATE ON public.request_time_entries FOR EACH ROW EXECUTE FUNCTION public.update_application_tables_updated_at();


--
-- Name: requests trigger_requests_updated_at; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER trigger_requests_updated_at BEFORE UPDATE ON public.requests FOR EACH ROW EXECUTE FUNCTION public.update_application_tables_updated_at();


--
-- Name: service_templates trigger_service_templates_updated_at; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER trigger_service_templates_updated_at BEFORE UPDATE ON public.service_templates FOR EACH ROW EXECUTE FUNCTION public.update_application_tables_updated_at();


--
-- Name: support_ticket_comments trigger_support_ticket_comments_updated_at; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER trigger_support_ticket_comments_updated_at BEFORE UPDATE ON public.support_ticket_comments FOR EACH ROW EXECUTE FUNCTION public.update_support_tickets_updated_at();


--
-- Name: support_tickets trigger_support_tickets_updated_at; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER trigger_support_tickets_updated_at BEFORE UPDATE ON public.support_tickets FOR EACH ROW EXECUTE FUNCTION public.update_support_tickets_updated_at();


--
-- Name: time_entries trigger_time_entries_updated_at; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER trigger_time_entries_updated_at BEFORE UPDATE ON public.time_entries FOR EACH ROW EXECUTE FUNCTION public.update_application_tables_updated_at();


--
-- Name: time_entry_locks trigger_time_entry_locks_updated_at; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER trigger_time_entry_locks_updated_at BEFORE UPDATE ON public.time_entry_locks FOR EACH ROW EXECUTE FUNCTION public.update_application_tables_updated_at();


--
-- Name: users trigger_users_updated_at; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER trigger_users_updated_at BEFORE UPDATE ON public.users FOR EACH ROW EXECUTE FUNCTION public.update_core_tables_updated_at();


--
-- Name: account_health update_account_health_updated_at; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER update_account_health_updated_at BEFORE UPDATE ON public.account_health FOR EACH ROW EXECUTE FUNCTION public.update_updated_at_column();


--
-- Name: ad_accounts update_ad_accounts_updated_at; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER update_ad_accounts_updated_at BEFORE UPDATE ON public.ad_accounts FOR EACH ROW EXECUTE FUNCTION public.update_updated_at_column();


--
-- Name: ad_campaigns update_ad_campaigns_updated_at; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER update_ad_campaigns_updated_at BEFORE UPDATE ON public.ad_campaigns FOR EACH ROW EXECUTE FUNCTION public.update_updated_at_column();


--
-- Name: ad_creatives update_ad_creatives_updated_at; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER update_ad_creatives_updated_at BEFORE UPDATE ON public.ad_creatives FOR EACH ROW EXECUTE FUNCTION public.update_updated_at_column();


--
-- Name: ad_metrics update_ad_metrics_updated_at; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER update_ad_metrics_updated_at BEFORE UPDATE ON public.ad_metrics FOR EACH ROW EXECUTE FUNCTION public.update_updated_at_column();


--
-- Name: ad_sets update_ad_sets_updated_at; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER update_ad_sets_updated_at BEFORE UPDATE ON public.ad_sets FOR EACH ROW EXECUTE FUNCTION public.update_updated_at_column();


--
-- Name: ads update_ads_updated_at; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER update_ads_updated_at BEFORE UPDATE ON public.ads FOR EACH ROW EXECUTE FUNCTION public.update_updated_at_column();


--
-- Name: ai_conversations update_ai_conversations_updated_at; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER update_ai_conversations_updated_at BEFORE UPDATE ON public.ai_conversations FOR EACH ROW EXECUTE FUNCTION public.update_updated_at_column();


--
-- Name: ai_messages update_ai_messages_updated_at; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER update_ai_messages_updated_at BEFORE UPDATE ON public.ai_messages FOR EACH ROW EXECUTE FUNCTION public.update_updated_at_column();


--
-- Name: ai_providers update_ai_providers_updated_at; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER update_ai_providers_updated_at BEFORE UPDATE ON public.ai_providers FOR EACH ROW EXECUTE FUNCTION public.update_updated_at_column();


--
-- Name: ai_tasks update_ai_tasks_updated_at; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER update_ai_tasks_updated_at BEFORE UPDATE ON public.ai_tasks FOR EACH ROW EXECUTE FUNCTION public.update_updated_at_column();


--
-- Name: ai_workflows update_ai_workflows_updated_at; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER update_ai_workflows_updated_at BEFORE UPDATE ON public.ai_workflows FOR EACH ROW EXECUTE FUNCTION public.update_updated_at_column();


--
-- Name: automation_logs update_automation_logs_updated_at; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER update_automation_logs_updated_at BEFORE UPDATE ON public.automation_logs FOR EACH ROW EXECUTE FUNCTION public.update_updated_at_column();


--
-- Name: automation_rules update_automation_rules_updated_at; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER update_automation_rules_updated_at BEFORE UPDATE ON public.automation_rules FOR EACH ROW EXECUTE FUNCTION public.update_updated_at_column();


--
-- Name: automation_runs update_automation_runs_updated_at; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER update_automation_runs_updated_at BEFORE UPDATE ON public.automation_runs FOR EACH ROW EXECUTE FUNCTION public.update_updated_at_column();


--
-- Name: brand_assets update_brand_assets_updated_at; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER update_brand_assets_updated_at BEFORE UPDATE ON public.brand_assets FOR EACH ROW EXECUTE FUNCTION public.update_updated_at_column();


--
-- Name: brand_audits update_brand_audits_updated_at; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER update_brand_audits_updated_at BEFORE UPDATE ON public.brand_audits FOR EACH ROW EXECUTE FUNCTION public.update_updated_at_column();


--
-- Name: brand_colors update_brand_colors_updated_at; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER update_brand_colors_updated_at BEFORE UPDATE ON public.brand_colors FOR EACH ROW EXECUTE FUNCTION public.update_updated_at_column();


--
-- Name: brand_competitors update_brand_competitors_updated_at; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER update_brand_competitors_updated_at BEFORE UPDATE ON public.brand_competitors FOR EACH ROW EXECUTE FUNCTION public.update_updated_at_column();


--
-- Name: brand_fonts update_brand_fonts_updated_at; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER update_brand_fonts_updated_at BEFORE UPDATE ON public.brand_fonts FOR EACH ROW EXECUTE FUNCTION public.update_updated_at_column();


--
-- Name: brand_guide_sections update_brand_guide_sections_updated_at; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER update_brand_guide_sections_updated_at BEFORE UPDATE ON public.brand_guide_sections FOR EACH ROW EXECUTE FUNCTION public.update_updated_at_column();


--
-- Name: brand_guides update_brand_guides_updated_at; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER update_brand_guides_updated_at BEFORE UPDATE ON public.brand_guides FOR EACH ROW EXECUTE FUNCTION public.update_updated_at_column();


--
-- Name: brand_inconsistencies update_brand_inconsistencies_updated_at; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER update_brand_inconsistencies_updated_at BEFORE UPDATE ON public.brand_inconsistencies FOR EACH ROW EXECUTE FUNCTION public.update_updated_at_column();


--
-- Name: brand_mentions update_brand_mentions_updated_at; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER update_brand_mentions_updated_at BEFORE UPDATE ON public.brand_mentions FOR EACH ROW EXECUTE FUNCTION public.update_updated_at_column();


--
-- Name: brand_templates update_brand_templates_updated_at; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER update_brand_templates_updated_at BEFORE UPDATE ON public.brand_templates FOR EACH ROW EXECUTE FUNCTION public.update_updated_at_column();


--
-- Name: campaign_assets update_campaign_assets_updated_at; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER update_campaign_assets_updated_at BEFORE UPDATE ON public.campaign_assets FOR EACH ROW EXECUTE FUNCTION public.update_updated_at_column();


--
-- Name: campaign_metrics update_campaign_metrics_updated_at; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER update_campaign_metrics_updated_at BEFORE UPDATE ON public.campaign_metrics FOR EACH ROW EXECUTE FUNCTION public.update_updated_at_column();


--
-- Name: campaigns update_campaigns_updated_at; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER update_campaigns_updated_at BEFORE UPDATE ON public.campaigns FOR EACH ROW EXECUTE FUNCTION public.update_updated_at_column();


--
-- Name: client_health_snapshots update_client_health_snapshots_updated_at; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER update_client_health_snapshots_updated_at BEFORE UPDATE ON public.client_health_snapshots FOR EACH ROW EXECUTE FUNCTION public.update_updated_at_column();


--
-- Name: content_calendar_items update_content_calendar_items_updated_at; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER update_content_calendar_items_updated_at BEFORE UPDATE ON public.content_calendar_items FOR EACH ROW EXECUTE FUNCTION public.update_updated_at_column();


--
-- Name: content_templates update_content_templates_updated_at; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER update_content_templates_updated_at BEFORE UPDATE ON public.content_templates FOR EACH ROW EXECUTE FUNCTION public.update_updated_at_column();


--
-- Name: contracts update_contracts_updated_at; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER update_contracts_updated_at BEFORE UPDATE ON public.contracts FOR EACH ROW EXECUTE FUNCTION public.update_updated_at_column();


--
-- Name: conversation_participants update_conversation_participants_updated_at; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER update_conversation_participants_updated_at BEFORE UPDATE ON public.conversation_participants FOR EACH ROW EXECUTE FUNCTION public.update_updated_at_column();


--
-- Name: conversations update_conversations_updated_at; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER update_conversations_updated_at BEFORE UPDATE ON public.conversations FOR EACH ROW EXECUTE FUNCTION public.update_updated_at_column();


--
-- Name: custom_dashboards update_custom_dashboards_updated_at; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER update_custom_dashboards_updated_at BEFORE UPDATE ON public.custom_dashboards FOR EACH ROW EXECUTE FUNCTION public.update_updated_at_column();


--
-- Name: data_privacy_requests update_data_privacy_requests_updated_at; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER update_data_privacy_requests_updated_at BEFORE UPDATE ON public.data_privacy_requests FOR EACH ROW EXECUTE FUNCTION public.update_updated_at_column();


--
-- Name: document_shares update_document_shares_updated_at; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER update_document_shares_updated_at BEFORE UPDATE ON public.document_shares FOR EACH ROW EXECUTE FUNCTION public.update_updated_at_column();


--
-- Name: documents update_documents_updated_at; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER update_documents_updated_at BEFORE UPDATE ON public.documents FOR EACH ROW EXECUTE FUNCTION public.update_updated_at_column();


--
-- Name: email_templates update_email_templates_updated_at; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER update_email_templates_updated_at BEFORE UPDATE ON public.email_templates FOR EACH ROW EXECUTE FUNCTION public.update_updated_at_column();


--
-- Name: form_templates update_form_templates_updated_at; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER update_form_templates_updated_at BEFORE UPDATE ON public.form_templates FOR EACH ROW EXECUTE FUNCTION public.update_updated_at_column();


--
-- Name: invoice_templates update_invoice_templates_updated_at; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER update_invoice_templates_updated_at BEFORE UPDATE ON public.invoice_templates FOR EACH ROW EXECUTE FUNCTION public.update_updated_at_column();


--
-- Name: knowledge_base_articles update_knowledge_base_articles_updated_at; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER update_knowledge_base_articles_updated_at BEFORE UPDATE ON public.knowledge_base_articles FOR EACH ROW EXECUTE FUNCTION public.update_updated_at_column();


--
-- Name: knowledge_base_categories update_knowledge_base_categories_updated_at; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER update_knowledge_base_categories_updated_at BEFORE UPDATE ON public.knowledge_base_categories FOR EACH ROW EXECUTE FUNCTION public.update_updated_at_column();


--
-- Name: leads update_leads_updated_at; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER update_leads_updated_at BEFORE UPDATE ON public.leads FOR EACH ROW EXECUTE FUNCTION public.update_updated_at_column();


--
-- Name: maintenance_plan_billing_history update_maintenance_plan_billing_history_updated_at; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER update_maintenance_plan_billing_history_updated_at BEFORE UPDATE ON public.maintenance_plan_billing_history FOR EACH ROW EXECUTE FUNCTION public.update_updated_at_column();


--
-- Name: maintenance_plan_templates update_maintenance_plan_templates_updated_at; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER update_maintenance_plan_templates_updated_at BEFORE UPDATE ON public.maintenance_plan_templates FOR EACH ROW EXECUTE FUNCTION public.update_updated_at_column();


--
-- Name: maintenance_plan_usage update_maintenance_plan_usage_updated_at; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER update_maintenance_plan_usage_updated_at BEFORE UPDATE ON public.maintenance_plan_usage FOR EACH ROW EXECUTE FUNCTION public.update_updated_at_column();


--
-- Name: maintenance_plans update_maintenance_plans_updated_at; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER update_maintenance_plans_updated_at BEFORE UPDATE ON public.maintenance_plans FOR EACH ROW EXECUTE FUNCTION public.update_updated_at_column();


--
-- Name: meeting_attendees update_meeting_attendees_updated_at; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER update_meeting_attendees_updated_at BEFORE UPDATE ON public.meeting_attendees FOR EACH ROW EXECUTE FUNCTION public.update_updated_at_column();


--
-- Name: meeting_notes update_meeting_notes_updated_at; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER update_meeting_notes_updated_at BEFORE UPDATE ON public.meeting_notes FOR EACH ROW EXECUTE FUNCTION public.update_updated_at_column();


--
-- Name: meetings update_meetings_updated_at; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER update_meetings_updated_at BEFORE UPDATE ON public.meetings FOR EACH ROW EXECUTE FUNCTION public.update_updated_at_column();


--
-- Name: message_attachments update_message_attachments_updated_at; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER update_message_attachments_updated_at BEFORE UPDATE ON public.message_attachments FOR EACH ROW EXECUTE FUNCTION public.update_updated_at_column();


--
-- Name: message_reads update_message_reads_updated_at; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER update_message_reads_updated_at BEFORE UPDATE ON public.message_reads FOR EACH ROW EXECUTE FUNCTION public.update_updated_at_column();


--
-- Name: messages update_messages_updated_at; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER update_messages_updated_at BEFORE UPDATE ON public.messages FOR EACH ROW EXECUTE FUNCTION public.update_updated_at_column();


--
-- Name: partners update_partners_updated_at; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER update_partners_updated_at BEFORE UPDATE ON public.partners FOR EACH ROW EXECUTE FUNCTION public.update_updated_at_column();


--
-- Name: prompt_templates update_prompt_templates_updated_at; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER update_prompt_templates_updated_at BEFORE UPDATE ON public.prompt_templates FOR EACH ROW EXECUTE FUNCTION public.update_updated_at_column();


--
-- Name: referrals update_referrals_updated_at; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER update_referrals_updated_at BEFORE UPDATE ON public.referrals FOR EACH ROW EXECUTE FUNCTION public.update_updated_at_column();


--
-- Name: report_deliveries update_report_deliveries_updated_at; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER update_report_deliveries_updated_at BEFORE UPDATE ON public.report_deliveries FOR EACH ROW EXECUTE FUNCTION public.update_updated_at_column();


--
-- Name: report_schedules update_report_schedules_updated_at; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER update_report_schedules_updated_at BEFORE UPDATE ON public.report_schedules FOR EACH ROW EXECUTE FUNCTION public.update_updated_at_column();


--
-- Name: report_templates update_report_templates_updated_at; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER update_report_templates_updated_at BEFORE UPDATE ON public.report_templates FOR EACH ROW EXECUTE FUNCTION public.update_updated_at_column();


--
-- Name: social_accounts update_social_accounts_updated_at; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER update_social_accounts_updated_at BEFORE UPDATE ON public.social_accounts FOR EACH ROW EXECUTE FUNCTION public.update_updated_at_column();


--
-- Name: social_posts update_social_posts_updated_at; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER update_social_posts_updated_at BEFORE UPDATE ON public.social_posts FOR EACH ROW EXECUTE FUNCTION public.update_updated_at_column();


--
-- Name: staff_guide_categories update_staff_guide_categories_updated_at; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER update_staff_guide_categories_updated_at BEFORE UPDATE ON public.staff_guide_categories FOR EACH ROW EXECUTE FUNCTION public.update_updated_at_column();


--
-- Name: staff_guides update_staff_guides_updated_at; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER update_staff_guides_updated_at BEFORE UPDATE ON public.staff_guides FOR EACH ROW EXECUTE FUNCTION public.update_updated_at_column();


--
-- Name: staff_task_assignees update_staff_task_assignees_updated_at; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER update_staff_task_assignees_updated_at BEFORE UPDATE ON public.staff_task_assignees FOR EACH ROW EXECUTE FUNCTION public.update_updated_at_column();


--
-- Name: staff_task_boards update_staff_task_boards_updated_at; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER update_staff_task_boards_updated_at BEFORE UPDATE ON public.staff_task_boards FOR EACH ROW EXECUTE FUNCTION public.update_updated_at_column();


--
-- Name: staff_task_checklists update_staff_task_checklists_updated_at; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER update_staff_task_checklists_updated_at BEFORE UPDATE ON public.staff_task_checklists FOR EACH ROW EXECUTE FUNCTION public.update_updated_at_column();


--
-- Name: staff_task_columns update_staff_task_columns_updated_at; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER update_staff_task_columns_updated_at BEFORE UPDATE ON public.staff_task_columns FOR EACH ROW EXECUTE FUNCTION public.update_updated_at_column();


--
-- Name: staff_task_comments update_staff_task_comments_updated_at; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER update_staff_task_comments_updated_at BEFORE UPDATE ON public.staff_task_comments FOR EACH ROW EXECUTE FUNCTION public.update_updated_at_column();


--
-- Name: staff_task_labels update_staff_task_labels_updated_at; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER update_staff_task_labels_updated_at BEFORE UPDATE ON public.staff_task_labels FOR EACH ROW EXECUTE FUNCTION public.update_updated_at_column();


--
-- Name: staff_tasks update_staff_tasks_updated_at; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER update_staff_tasks_updated_at BEFORE UPDATE ON public.staff_tasks FOR EACH ROW EXECUTE FUNCTION public.update_updated_at_column();


--
-- Name: storage_connections update_storage_connections_updated_at; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER update_storage_connections_updated_at BEFORE UPDATE ON public.storage_connections FOR EACH ROW EXECUTE FUNCTION public.update_updated_at_column();


--
-- Name: storage_files update_storage_files_updated_at; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER update_storage_files_updated_at BEFORE UPDATE ON public.storage_files FOR EACH ROW EXECUTE FUNCTION public.update_updated_at_column();


--
-- Name: survey_questions update_survey_questions_updated_at; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER update_survey_questions_updated_at BEFORE UPDATE ON public.survey_questions FOR EACH ROW EXECUTE FUNCTION public.update_updated_at_column();


--
-- Name: survey_responses update_survey_responses_updated_at; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER update_survey_responses_updated_at BEFORE UPDATE ON public.survey_responses FOR EACH ROW EXECUTE FUNCTION public.update_updated_at_column();


--
-- Name: surveys update_surveys_updated_at; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER update_surveys_updated_at BEFORE UPDATE ON public.surveys FOR EACH ROW EXECUTE FUNCTION public.update_updated_at_column();


--
-- Name: webhook_deliveries update_webhook_deliveries_updated_at; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER update_webhook_deliveries_updated_at BEFORE UPDATE ON public.webhook_deliveries FOR EACH ROW EXECUTE FUNCTION public.update_updated_at_column();


--
-- Name: webhook_endpoints update_webhook_endpoints_updated_at; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER update_webhook_endpoints_updated_at BEFORE UPDATE ON public.webhook_endpoints FOR EACH ROW EXECUTE FUNCTION public.update_updated_at_column();


--
-- Name: white_label_configs update_white_label_configs_updated_at; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER update_white_label_configs_updated_at BEFORE UPDATE ON public.white_label_configs FOR EACH ROW EXECUTE FUNCTION public.update_updated_at_column();


--
-- Name: account_health account_health_client_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.account_health
    ADD CONSTRAINT account_health_client_id_fkey FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE CASCADE;


--
-- Name: activity_logs activity_logs_causer_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.activity_logs
    ADD CONSTRAINT activity_logs_causer_id_fkey FOREIGN KEY (causer_id) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: activity_logs activity_logs_client_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.activity_logs
    ADD CONSTRAINT activity_logs_client_id_fkey FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE CASCADE;


--
-- Name: ad_accounts ad_accounts_client_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ad_accounts
    ADD CONSTRAINT ad_accounts_client_id_fkey FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE CASCADE;


--
-- Name: ad_campaigns ad_campaigns_ad_account_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ad_campaigns
    ADD CONSTRAINT ad_campaigns_ad_account_id_fkey FOREIGN KEY (ad_account_id) REFERENCES public.ad_accounts(id) ON DELETE CASCADE;


--
-- Name: ad_creatives ad_creatives_ad_account_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ad_creatives
    ADD CONSTRAINT ad_creatives_ad_account_id_fkey FOREIGN KEY (ad_account_id) REFERENCES public.ad_accounts(id) ON DELETE CASCADE;


--
-- Name: ad_metrics ad_metrics_ad_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ad_metrics
    ADD CONSTRAINT ad_metrics_ad_id_fkey FOREIGN KEY (ad_id) REFERENCES public.ads(id) ON DELETE CASCADE;


--
-- Name: ad_sets ad_sets_campaign_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ad_sets
    ADD CONSTRAINT ad_sets_campaign_id_fkey FOREIGN KEY (campaign_id) REFERENCES public.ad_campaigns(id) ON DELETE CASCADE;


--
-- Name: ads ads_ad_set_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ads
    ADD CONSTRAINT ads_ad_set_id_fkey FOREIGN KEY (ad_set_id) REFERENCES public.ad_sets(id) ON DELETE CASCADE;


--
-- Name: ads ads_creative_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ads
    ADD CONSTRAINT ads_creative_id_fkey FOREIGN KEY (creative_id) REFERENCES public.ad_creatives(id);


--
-- Name: ai_conversations ai_conversations_client_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ai_conversations
    ADD CONSTRAINT ai_conversations_client_id_fkey FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE CASCADE;


--
-- Name: ai_conversations ai_conversations_user_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ai_conversations
    ADD CONSTRAINT ai_conversations_user_id_fkey FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: ai_insight_reports ai_insight_reports_client_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ai_insight_reports
    ADD CONSTRAINT ai_insight_reports_client_id_fkey FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE CASCADE;


--
-- Name: ai_message_feedback ai_message_feedback_message_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ai_message_feedback
    ADD CONSTRAINT ai_message_feedback_message_id_fkey FOREIGN KEY (message_id) REFERENCES public.ai_messages(id) ON DELETE CASCADE;


--
-- Name: ai_message_feedback ai_message_feedback_user_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ai_message_feedback
    ADD CONSTRAINT ai_message_feedback_user_id_fkey FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: ai_messages ai_messages_conversation_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ai_messages
    ADD CONSTRAINT ai_messages_conversation_id_fkey FOREIGN KEY (conversation_id) REFERENCES public.ai_conversations(id) ON DELETE CASCADE;


--
-- Name: ai_tasks ai_tasks_client_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ai_tasks
    ADD CONSTRAINT ai_tasks_client_id_fkey FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE CASCADE;


--
-- Name: ai_tasks ai_tasks_user_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ai_tasks
    ADD CONSTRAINT ai_tasks_user_id_fkey FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: ai_usage_tracking ai_usage_tracking_client_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ai_usage_tracking
    ADD CONSTRAINT ai_usage_tracking_client_id_fkey FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE CASCADE;


--
-- Name: ai_usage_tracking ai_usage_tracking_user_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ai_usage_tracking
    ADD CONSTRAINT ai_usage_tracking_user_id_fkey FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: ai_workflows ai_workflows_client_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ai_workflows
    ADD CONSTRAINT ai_workflows_client_id_fkey FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE CASCADE;


--
-- Name: ai_workflows ai_workflows_created_by_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ai_workflows
    ADD CONSTRAINT ai_workflows_created_by_fkey FOREIGN KEY (created_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: announcements announcements_client_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.announcements
    ADD CONSTRAINT announcements_client_id_fkey FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE CASCADE;


--
-- Name: announcements announcements_created_by_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.announcements
    ADD CONSTRAINT announcements_created_by_fkey FOREIGN KEY (created_by) REFERENCES public.users(id);


--
-- Name: automation_logs automation_logs_automation_rule_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.automation_logs
    ADD CONSTRAINT automation_logs_automation_rule_id_fkey FOREIGN KEY (automation_rule_id) REFERENCES public.automation_rules(id) ON DELETE CASCADE;


--
-- Name: automation_rules automation_rules_created_by_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.automation_rules
    ADD CONSTRAINT automation_rules_created_by_fkey FOREIGN KEY (created_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: automation_rules automation_rules_updated_by_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.automation_rules
    ADD CONSTRAINT automation_rules_updated_by_fkey FOREIGN KEY (updated_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: automation_runs automation_runs_automation_rule_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.automation_runs
    ADD CONSTRAINT automation_runs_automation_rule_id_fkey FOREIGN KEY (automation_rule_id) REFERENCES public.automation_rules(id) ON DELETE CASCADE;


--
-- Name: brand_assets brand_assets_client_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.brand_assets
    ADD CONSTRAINT brand_assets_client_id_fkey FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE SET NULL;


--
-- Name: brand_audits brand_audits_client_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.brand_audits
    ADD CONSTRAINT brand_audits_client_id_fkey FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE SET NULL;


--
-- Name: brand_colors brand_colors_brand_guide_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.brand_colors
    ADD CONSTRAINT brand_colors_brand_guide_id_fkey FOREIGN KEY (brand_guide_id) REFERENCES public.brand_guides(id) ON DELETE CASCADE;


--
-- Name: brand_competitors brand_competitors_client_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.brand_competitors
    ADD CONSTRAINT brand_competitors_client_id_fkey FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE SET NULL;


--
-- Name: brand_fonts brand_fonts_brand_guide_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.brand_fonts
    ADD CONSTRAINT brand_fonts_brand_guide_id_fkey FOREIGN KEY (brand_guide_id) REFERENCES public.brand_guides(id) ON DELETE CASCADE;


--
-- Name: brand_guide_sections brand_guide_sections_brand_guide_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.brand_guide_sections
    ADD CONSTRAINT brand_guide_sections_brand_guide_id_fkey FOREIGN KEY (brand_guide_id) REFERENCES public.brand_guides(id) ON DELETE CASCADE;


--
-- Name: brand_guides brand_guides_client_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.brand_guides
    ADD CONSTRAINT brand_guides_client_id_fkey FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE SET NULL;


--
-- Name: brand_guides brand_guides_created_by_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.brand_guides
    ADD CONSTRAINT brand_guides_created_by_fkey FOREIGN KEY (created_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: brand_inconsistencies brand_inconsistencies_brand_audit_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.brand_inconsistencies
    ADD CONSTRAINT brand_inconsistencies_brand_audit_id_fkey FOREIGN KEY (brand_audit_id) REFERENCES public.brand_audits(id) ON DELETE CASCADE;


--
-- Name: brand_mentions brand_mentions_client_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.brand_mentions
    ADD CONSTRAINT brand_mentions_client_id_fkey FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE SET NULL;


--
-- Name: brand_mentions brand_mentions_responded_by_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.brand_mentions
    ADD CONSTRAINT brand_mentions_responded_by_fkey FOREIGN KEY (responded_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: brand_templates brand_templates_brand_guide_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.brand_templates
    ADD CONSTRAINT brand_templates_brand_guide_id_fkey FOREIGN KEY (brand_guide_id) REFERENCES public.brand_guides(id) ON DELETE CASCADE;


--
-- Name: calendar_connections calendar_connections_user_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.calendar_connections
    ADD CONSTRAINT calendar_connections_user_id_fkey FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: campaign_assets campaign_assets_campaign_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.campaign_assets
    ADD CONSTRAINT campaign_assets_campaign_id_fkey FOREIGN KEY (campaign_id) REFERENCES public.campaigns(id) ON DELETE CASCADE;


--
-- Name: campaign_metrics campaign_metrics_campaign_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.campaign_metrics
    ADD CONSTRAINT campaign_metrics_campaign_id_fkey FOREIGN KEY (campaign_id) REFERENCES public.campaigns(id) ON DELETE CASCADE;


--
-- Name: campaigns campaigns_client_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.campaigns
    ADD CONSTRAINT campaigns_client_id_fkey FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE CASCADE;


--
-- Name: campaigns campaigns_created_by_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.campaigns
    ADD CONSTRAINT campaigns_created_by_fkey FOREIGN KEY (created_by) REFERENCES public.users(id);


--
-- Name: client_features client_features_client_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_features
    ADD CONSTRAINT client_features_client_id_fkey FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE CASCADE;


--
-- Name: client_features client_features_disabled_by_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_features
    ADD CONSTRAINT client_features_disabled_by_fkey FOREIGN KEY (disabled_by) REFERENCES public.users(id);


--
-- Name: client_features client_features_enabled_by_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_features
    ADD CONSTRAINT client_features_enabled_by_fkey FOREIGN KEY (enabled_by) REFERENCES public.users(id);


--
-- Name: client_features client_features_feature_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_features
    ADD CONSTRAINT client_features_feature_id_fkey FOREIGN KEY (feature_id) REFERENCES public.features(id) ON DELETE CASCADE;


--
-- Name: client_health_snapshots client_health_snapshots_client_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_health_snapshots
    ADD CONSTRAINT client_health_snapshots_client_id_fkey FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE CASCADE;


--
-- Name: content_calendar_items content_calendar_items_approved_by_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.content_calendar_items
    ADD CONSTRAINT content_calendar_items_approved_by_fkey FOREIGN KEY (approved_by) REFERENCES public.users(id);


--
-- Name: content_calendar_items content_calendar_items_client_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.content_calendar_items
    ADD CONSTRAINT content_calendar_items_client_id_fkey FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE CASCADE;


--
-- Name: content_calendar_items content_calendar_items_created_by_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.content_calendar_items
    ADD CONSTRAINT content_calendar_items_created_by_fkey FOREIGN KEY (created_by) REFERENCES public.users(id);


--
-- Name: content_templates content_templates_client_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.content_templates
    ADD CONSTRAINT content_templates_client_id_fkey FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE CASCADE;


--
-- Name: content_templates content_templates_created_by_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.content_templates
    ADD CONSTRAINT content_templates_created_by_fkey FOREIGN KEY (created_by) REFERENCES public.users(id);


--
-- Name: contracts contracts_client_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contracts
    ADD CONSTRAINT contracts_client_id_fkey FOREIGN KEY (client_id) REFERENCES public.clients(id);


--
-- Name: contracts contracts_document_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contracts
    ADD CONSTRAINT contracts_document_id_fkey FOREIGN KEY (document_id) REFERENCES public.documents(id);


--
-- Name: conversation_participants conversation_participants_conversation_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.conversation_participants
    ADD CONSTRAINT conversation_participants_conversation_id_fkey FOREIGN KEY (conversation_id) REFERENCES public.conversations(id) ON DELETE CASCADE;


--
-- Name: conversation_participants conversation_participants_user_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.conversation_participants
    ADD CONSTRAINT conversation_participants_user_id_fkey FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: conversations conversations_client_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.conversations
    ADD CONSTRAINT conversations_client_id_fkey FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE CASCADE;


--
-- Name: custom_dashboards custom_dashboards_created_by_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.custom_dashboards
    ADD CONSTRAINT custom_dashboards_created_by_fkey FOREIGN KEY (created_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: custom_dashboards custom_dashboards_user_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.custom_dashboards
    ADD CONSTRAINT custom_dashboards_user_id_fkey FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: data_privacy_requests data_privacy_requests_client_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.data_privacy_requests
    ADD CONSTRAINT data_privacy_requests_client_id_fkey FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE CASCADE;


--
-- Name: data_privacy_requests data_privacy_requests_user_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.data_privacy_requests
    ADD CONSTRAINT data_privacy_requests_user_id_fkey FOREIGN KEY (user_id) REFERENCES public.users(id);


--
-- Name: document_shares document_shares_document_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.document_shares
    ADD CONSTRAINT document_shares_document_id_fkey FOREIGN KEY (document_id) REFERENCES public.documents(id) ON DELETE CASCADE;


--
-- Name: documents documents_client_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.documents
    ADD CONSTRAINT documents_client_id_fkey FOREIGN KEY (client_id) REFERENCES public.clients(id);


--
-- Name: documents documents_parent_document_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.documents
    ADD CONSTRAINT documents_parent_document_id_fkey FOREIGN KEY (parent_document_id) REFERENCES public.documents(id);


--
-- Name: documents documents_uploaded_by_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.documents
    ADD CONSTRAINT documents_uploaded_by_fkey FOREIGN KEY (uploaded_by) REFERENCES public.users(id);


--
-- Name: encrypted_settings encrypted_settings_client_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.encrypted_settings
    ADD CONSTRAINT encrypted_settings_client_id_fkey FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE CASCADE;


--
-- Name: encrypted_settings encrypted_settings_updated_by_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.encrypted_settings
    ADD CONSTRAINT encrypted_settings_updated_by_fkey FOREIGN KEY (updated_by) REFERENCES public.users(id);


--
-- Name: clients fk_clients_primary_contact_id; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.clients
    ADD CONSTRAINT fk_clients_primary_contact_id FOREIGN KEY (primary_contact_id) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: time_entries fk_time_entries_project_id; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.time_entries
    ADD CONSTRAINT fk_time_entries_project_id FOREIGN KEY (project_id) REFERENCES public.projects(id) ON DELETE SET NULL;


--
-- Name: form_templates form_templates_client_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.form_templates
    ADD CONSTRAINT form_templates_client_id_fkey FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE CASCADE;


--
-- Name: form_templates form_templates_created_by_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.form_templates
    ADD CONSTRAINT form_templates_created_by_fkey FOREIGN KEY (created_by) REFERENCES public.users(id);


--
-- Name: invoice_items invoice_items_invoice_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.invoice_items
    ADD CONSTRAINT invoice_items_invoice_id_fkey FOREIGN KEY (invoice_id) REFERENCES public.invoices(id) ON DELETE CASCADE;


--
-- Name: invoices invoices_client_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.invoices
    ADD CONSTRAINT invoices_client_id_fkey FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE CASCADE;


--
-- Name: invoices invoices_created_by_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.invoices
    ADD CONSTRAINT invoices_created_by_fkey FOREIGN KEY (created_by) REFERENCES public.users(id);


--
-- Name: knowledge_base_articles knowledge_base_articles_category_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.knowledge_base_articles
    ADD CONSTRAINT knowledge_base_articles_category_id_fkey FOREIGN KEY (category_id) REFERENCES public.knowledge_base_categories(id) ON DELETE CASCADE;


--
-- Name: knowledge_base_articles knowledge_base_articles_created_by_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.knowledge_base_articles
    ADD CONSTRAINT knowledge_base_articles_created_by_fkey FOREIGN KEY (created_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: knowledge_base_categories knowledge_base_categories_parent_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.knowledge_base_categories
    ADD CONSTRAINT knowledge_base_categories_parent_id_fkey FOREIGN KEY (parent_id) REFERENCES public.knowledge_base_categories(id) ON DELETE SET NULL;


--
-- Name: knowledge_base_feedback knowledge_base_feedback_article_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.knowledge_base_feedback
    ADD CONSTRAINT knowledge_base_feedback_article_id_fkey FOREIGN KEY (article_id) REFERENCES public.knowledge_base_articles(id) ON DELETE CASCADE;


--
-- Name: knowledge_base_feedback knowledge_base_feedback_user_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.knowledge_base_feedback
    ADD CONSTRAINT knowledge_base_feedback_user_id_fkey FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: lead_activities lead_activities_lead_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.lead_activities
    ADD CONSTRAINT lead_activities_lead_id_fkey FOREIGN KEY (lead_id) REFERENCES public.leads(id) ON DELETE CASCADE;


--
-- Name: lead_activities lead_activities_user_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.lead_activities
    ADD CONSTRAINT lead_activities_user_id_fkey FOREIGN KEY (user_id) REFERENCES public.users(id);


--
-- Name: leads leads_assigned_to_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.leads
    ADD CONSTRAINT leads_assigned_to_fkey FOREIGN KEY (assigned_to) REFERENCES public.users(id);


--
-- Name: leads leads_client_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.leads
    ADD CONSTRAINT leads_client_id_fkey FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE CASCADE;


--
-- Name: maintenance_plan_billing_history maintenance_plan_billing_history_plan_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.maintenance_plan_billing_history
    ADD CONSTRAINT maintenance_plan_billing_history_plan_id_fkey FOREIGN KEY (plan_id) REFERENCES public.maintenance_plans(id) ON DELETE CASCADE;


--
-- Name: maintenance_plan_templates maintenance_plan_templates_created_by_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.maintenance_plan_templates
    ADD CONSTRAINT maintenance_plan_templates_created_by_fkey FOREIGN KEY (created_by) REFERENCES public.users(id);


--
-- Name: maintenance_plan_usage maintenance_plan_usage_approved_by_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.maintenance_plan_usage
    ADD CONSTRAINT maintenance_plan_usage_approved_by_fkey FOREIGN KEY (approved_by) REFERENCES public.users(id);


--
-- Name: maintenance_plan_usage maintenance_plan_usage_logged_by_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.maintenance_plan_usage
    ADD CONSTRAINT maintenance_plan_usage_logged_by_fkey FOREIGN KEY (logged_by) REFERENCES public.users(id);


--
-- Name: maintenance_plan_usage maintenance_plan_usage_plan_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.maintenance_plan_usage
    ADD CONSTRAINT maintenance_plan_usage_plan_id_fkey FOREIGN KEY (plan_id) REFERENCES public.maintenance_plans(id) ON DELETE CASCADE;


--
-- Name: maintenance_plan_usage maintenance_plan_usage_support_ticket_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.maintenance_plan_usage
    ADD CONSTRAINT maintenance_plan_usage_support_ticket_id_fkey FOREIGN KEY (support_ticket_id) REFERENCES public.support_tickets(id);


--
-- Name: maintenance_plans maintenance_plans_client_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.maintenance_plans
    ADD CONSTRAINT maintenance_plans_client_id_fkey FOREIGN KEY (client_id) REFERENCES public.clients(id);


--
-- Name: maintenance_plans maintenance_plans_created_by_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.maintenance_plans
    ADD CONSTRAINT maintenance_plans_created_by_fkey FOREIGN KEY (created_by) REFERENCES public.users(id);


--
-- Name: maintenance_plans maintenance_plans_template_id_maintenance_plan_templates_id_fk; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.maintenance_plans
    ADD CONSTRAINT maintenance_plans_template_id_maintenance_plan_templates_id_fk FOREIGN KEY (template_id) REFERENCES public.maintenance_plan_templates(id) ON DELETE SET NULL;


--
-- Name: meeting_attendees meeting_attendees_meeting_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.meeting_attendees
    ADD CONSTRAINT meeting_attendees_meeting_id_fkey FOREIGN KEY (meeting_id) REFERENCES public.meetings(id) ON DELETE CASCADE;


--
-- Name: meeting_attendees meeting_attendees_user_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.meeting_attendees
    ADD CONSTRAINT meeting_attendees_user_id_fkey FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: meeting_notes meeting_notes_created_by_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.meeting_notes
    ADD CONSTRAINT meeting_notes_created_by_fkey FOREIGN KEY (created_by) REFERENCES public.users(id);


--
-- Name: meeting_notes meeting_notes_meeting_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.meeting_notes
    ADD CONSTRAINT meeting_notes_meeting_id_fkey FOREIGN KEY (meeting_id) REFERENCES public.meetings(id) ON DELETE CASCADE;


--
-- Name: meetings meetings_client_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.meetings
    ADD CONSTRAINT meetings_client_id_fkey FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE CASCADE;


--
-- Name: meetings meetings_created_by_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.meetings
    ADD CONSTRAINT meetings_created_by_fkey FOREIGN KEY (created_by) REFERENCES public.users(id);


--
-- Name: meetings meetings_request_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.meetings
    ADD CONSTRAINT meetings_request_id_fkey FOREIGN KEY (request_id) REFERENCES public.requests(id) ON DELETE SET NULL;


--
-- Name: message_attachments message_attachments_message_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.message_attachments
    ADD CONSTRAINT message_attachments_message_id_fkey FOREIGN KEY (message_id) REFERENCES public.messages(id) ON DELETE CASCADE;


--
-- Name: message_reads message_reads_message_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.message_reads
    ADD CONSTRAINT message_reads_message_id_fkey FOREIGN KEY (message_id) REFERENCES public.messages(id) ON DELETE CASCADE;


--
-- Name: message_reads message_reads_user_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.message_reads
    ADD CONSTRAINT message_reads_user_id_fkey FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: messages messages_conversation_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.messages
    ADD CONSTRAINT messages_conversation_id_fkey FOREIGN KEY (conversation_id) REFERENCES public.conversations(id) ON DELETE CASCADE;


--
-- Name: messages messages_pinned_by_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.messages
    ADD CONSTRAINT messages_pinned_by_fkey FOREIGN KEY (pinned_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: messages messages_sender_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.messages
    ADD CONSTRAINT messages_sender_id_fkey FOREIGN KEY (sender_id) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: project_budgets project_budgets_project_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.project_budgets
    ADD CONSTRAINT project_budgets_project_id_fkey FOREIGN KEY (project_id) REFERENCES public.projects(id) ON DELETE CASCADE;


--
-- Name: project_cost_entries project_cost_entries_approved_by_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.project_cost_entries
    ADD CONSTRAINT project_cost_entries_approved_by_fkey FOREIGN KEY (approved_by) REFERENCES public.users(id);


--
-- Name: project_cost_entries project_cost_entries_budget_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.project_cost_entries
    ADD CONSTRAINT project_cost_entries_budget_id_fkey FOREIGN KEY (budget_id) REFERENCES public.project_budgets(id);


--
-- Name: project_cost_entries project_cost_entries_project_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.project_cost_entries
    ADD CONSTRAINT project_cost_entries_project_id_fkey FOREIGN KEY (project_id) REFERENCES public.projects(id) ON DELETE CASCADE;


--
-- Name: project_cost_entries project_cost_entries_user_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.project_cost_entries
    ADD CONSTRAINT project_cost_entries_user_id_fkey FOREIGN KEY (user_id) REFERENCES public.users(id);


--
-- Name: project_deliverables project_deliverables_milestone_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.project_deliverables
    ADD CONSTRAINT project_deliverables_milestone_id_fkey FOREIGN KEY (milestone_id) REFERENCES public.project_milestones(id);


--
-- Name: project_deliverables project_deliverables_project_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.project_deliverables
    ADD CONSTRAINT project_deliverables_project_id_fkey FOREIGN KEY (project_id) REFERENCES public.projects(id) ON DELETE CASCADE;


--
-- Name: project_milestones project_milestones_project_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.project_milestones
    ADD CONSTRAINT project_milestones_project_id_fkey FOREIGN KEY (project_id) REFERENCES public.projects(id) ON DELETE CASCADE;


--
-- Name: project_review_comments project_review_comments_author_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.project_review_comments
    ADD CONSTRAINT project_review_comments_author_id_fkey FOREIGN KEY (author_id) REFERENCES public.users(id);


--
-- Name: project_review_comments project_review_comments_project_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.project_review_comments
    ADD CONSTRAINT project_review_comments_project_id_fkey FOREIGN KEY (project_id) REFERENCES public.projects(id) ON DELETE CASCADE;


--
-- Name: project_review_comments project_review_comments_review_item_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.project_review_comments
    ADD CONSTRAINT project_review_comments_review_item_id_fkey FOREIGN KEY (review_item_id) REFERENCES public.project_review_items(id) ON DELETE CASCADE;


--
-- Name: project_review_comments project_review_comments_review_project_fk; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.project_review_comments
    ADD CONSTRAINT project_review_comments_review_project_fk FOREIGN KEY (review_item_id, project_id) REFERENCES public.project_review_items(id, project_id) ON DELETE CASCADE;


--
-- Name: project_review_items project_review_items_created_by_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.project_review_items
    ADD CONSTRAINT project_review_items_created_by_fkey FOREIGN KEY (created_by) REFERENCES public.users(id);


--
-- Name: project_review_items project_review_items_project_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.project_review_items
    ADD CONSTRAINT project_review_items_project_id_fkey FOREIGN KEY (project_id) REFERENCES public.projects(id) ON DELETE CASCADE;


--
-- Name: projects projects_client_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.projects
    ADD CONSTRAINT projects_client_id_fkey FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE CASCADE;


--
-- Name: projects projects_project_manager_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.projects
    ADD CONSTRAINT projects_project_manager_id_fkey FOREIGN KEY (project_manager_id) REFERENCES public.users(id);


--
-- Name: prompt_templates prompt_templates_created_by_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.prompt_templates
    ADD CONSTRAINT prompt_templates_created_by_fkey FOREIGN KEY (created_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: proposal_selections proposal_selections_proposal_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.proposal_selections
    ADD CONSTRAINT proposal_selections_proposal_id_fkey FOREIGN KEY (proposal_id) REFERENCES public.proposals(id) ON DELETE CASCADE;


--
-- Name: proposal_views proposal_views_proposal_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.proposal_views
    ADD CONSTRAINT proposal_views_proposal_id_fkey FOREIGN KEY (proposal_id) REFERENCES public.proposals(id) ON DELETE CASCADE;


--
-- Name: proposal_views proposal_views_viewed_by_user_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.proposal_views
    ADD CONSTRAINT proposal_views_viewed_by_user_id_fkey FOREIGN KEY (viewed_by_user_id) REFERENCES public.users(id);


--
-- Name: proposals proposals_client_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.proposals
    ADD CONSTRAINT proposals_client_id_fkey FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE CASCADE;


--
-- Name: proposals proposals_created_by_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.proposals
    ADD CONSTRAINT proposals_created_by_fkey FOREIGN KEY (created_by) REFERENCES public.users(id);


--
-- Name: proposals proposals_service_template_id_service_templates_id_fk; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.proposals
    ADD CONSTRAINT proposals_service_template_id_service_templates_id_fk FOREIGN KEY (service_template_id) REFERENCES public.service_templates(id);


--
-- Name: referrals referrals_client_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.referrals
    ADD CONSTRAINT referrals_client_id_fkey FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE SET NULL;


--
-- Name: referrals referrals_converted_client_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.referrals
    ADD CONSTRAINT referrals_converted_client_id_fkey FOREIGN KEY (converted_client_id) REFERENCES public.clients(id) ON DELETE SET NULL;


--
-- Name: referrals referrals_partner_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.referrals
    ADD CONSTRAINT referrals_partner_id_fkey FOREIGN KEY (partner_id) REFERENCES public.partners(id) ON DELETE CASCADE;


--
-- Name: report_deliveries report_deliveries_report_schedule_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.report_deliveries
    ADD CONSTRAINT report_deliveries_report_schedule_id_fkey FOREIGN KEY (report_schedule_id) REFERENCES public.report_schedules(id) ON DELETE SET NULL;


--
-- Name: report_deliveries report_deliveries_report_template_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.report_deliveries
    ADD CONSTRAINT report_deliveries_report_template_id_fkey FOREIGN KEY (report_template_id) REFERENCES public.report_templates(id) ON DELETE CASCADE;


--
-- Name: report_schedules report_schedules_created_by_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.report_schedules
    ADD CONSTRAINT report_schedules_created_by_fkey FOREIGN KEY (created_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: report_schedules report_schedules_report_template_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.report_schedules
    ADD CONSTRAINT report_schedules_report_template_id_fkey FOREIGN KEY (report_template_id) REFERENCES public.report_templates(id) ON DELETE CASCADE;


--
-- Name: report_templates report_templates_created_by_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.report_templates
    ADD CONSTRAINT report_templates_created_by_fkey FOREIGN KEY (created_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: request_comments request_comments_request_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.request_comments
    ADD CONSTRAINT request_comments_request_id_fkey FOREIGN KEY (request_id) REFERENCES public.requests(id) ON DELETE CASCADE;


--
-- Name: request_comments request_comments_user_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.request_comments
    ADD CONSTRAINT request_comments_user_id_fkey FOREIGN KEY (user_id) REFERENCES public.users(id);


--
-- Name: request_time_entries request_time_entries_request_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.request_time_entries
    ADD CONSTRAINT request_time_entries_request_id_fkey FOREIGN KEY (request_id) REFERENCES public.requests(id) ON DELETE CASCADE;


--
-- Name: request_time_entries request_time_entries_user_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.request_time_entries
    ADD CONSTRAINT request_time_entries_user_id_fkey FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: requests requests_assigned_to_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.requests
    ADD CONSTRAINT requests_assigned_to_fkey FOREIGN KEY (assigned_to) REFERENCES public.users(id);


--
-- Name: requests requests_client_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.requests
    ADD CONSTRAINT requests_client_id_fkey FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE CASCADE;


--
-- Name: requests requests_created_by_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.requests
    ADD CONSTRAINT requests_created_by_fkey FOREIGN KEY (created_by) REFERENCES public.users(id);


--
-- Name: role_features role_features_feature_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.role_features
    ADD CONSTRAINT role_features_feature_id_fkey FOREIGN KEY (feature_id) REFERENCES public.features(id) ON DELETE CASCADE;


--
-- Name: role_features role_features_role_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.role_features
    ADD CONSTRAINT role_features_role_id_fkey FOREIGN KEY (role_id) REFERENCES public.roles(id) ON DELETE CASCADE;


--
-- Name: role_permissions role_permissions_permission_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.role_permissions
    ADD CONSTRAINT role_permissions_permission_id_fkey FOREIGN KEY (permission_id) REFERENCES public.permissions(id) ON DELETE CASCADE;


--
-- Name: role_permissions role_permissions_role_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.role_permissions
    ADD CONSTRAINT role_permissions_role_id_fkey FOREIGN KEY (role_id) REFERENCES public.roles(id) ON DELETE CASCADE;


--
-- Name: service_templates service_templates_created_by_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.service_templates
    ADD CONSTRAINT service_templates_created_by_fkey FOREIGN KEY (created_by) REFERENCES public.users(id);


--
-- Name: social_accounts social_accounts_client_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.social_accounts
    ADD CONSTRAINT social_accounts_client_id_fkey FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE CASCADE;


--
-- Name: social_posts social_posts_account_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.social_posts
    ADD CONSTRAINT social_posts_account_id_fkey FOREIGN KEY (account_id) REFERENCES public.social_accounts(id) ON DELETE CASCADE;


--
-- Name: social_posts social_posts_created_by_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.social_posts
    ADD CONSTRAINT social_posts_created_by_fkey FOREIGN KEY (created_by) REFERENCES public.users(id);


--
-- Name: staff_assignments staff_assignments_client_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.staff_assignments
    ADD CONSTRAINT staff_assignments_client_id_fkey FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE CASCADE;


--
-- Name: staff_assignments staff_assignments_user_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.staff_assignments
    ADD CONSTRAINT staff_assignments_user_id_fkey FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: staff_guide_views staff_guide_views_guide_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.staff_guide_views
    ADD CONSTRAINT staff_guide_views_guide_id_fkey FOREIGN KEY (guide_id) REFERENCES public.staff_guides(id) ON DELETE CASCADE;


--
-- Name: staff_guide_views staff_guide_views_user_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.staff_guide_views
    ADD CONSTRAINT staff_guide_views_user_id_fkey FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: staff_guides staff_guides_author_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.staff_guides
    ADD CONSTRAINT staff_guides_author_id_fkey FOREIGN KEY (author_id) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: staff_guides staff_guides_category_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.staff_guides
    ADD CONSTRAINT staff_guides_category_id_fkey FOREIGN KEY (category_id) REFERENCES public.staff_guide_categories(id) ON DELETE CASCADE;


--
-- Name: staff_task_assignees staff_task_assignees_task_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.staff_task_assignees
    ADD CONSTRAINT staff_task_assignees_task_id_fkey FOREIGN KEY (task_id) REFERENCES public.staff_tasks(id) ON DELETE CASCADE;


--
-- Name: staff_task_assignees staff_task_assignees_user_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.staff_task_assignees
    ADD CONSTRAINT staff_task_assignees_user_id_fkey FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: staff_task_boards staff_task_boards_created_by_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.staff_task_boards
    ADD CONSTRAINT staff_task_boards_created_by_fkey FOREIGN KEY (created_by) REFERENCES public.users(id);


--
-- Name: staff_task_checklists staff_task_checklists_task_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.staff_task_checklists
    ADD CONSTRAINT staff_task_checklists_task_id_fkey FOREIGN KEY (task_id) REFERENCES public.staff_tasks(id) ON DELETE CASCADE;


--
-- Name: staff_task_columns staff_task_columns_board_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.staff_task_columns
    ADD CONSTRAINT staff_task_columns_board_id_fkey FOREIGN KEY (board_id) REFERENCES public.staff_task_boards(id) ON DELETE CASCADE;


--
-- Name: staff_task_comments staff_task_comments_task_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.staff_task_comments
    ADD CONSTRAINT staff_task_comments_task_id_fkey FOREIGN KEY (task_id) REFERENCES public.staff_tasks(id) ON DELETE CASCADE;


--
-- Name: staff_task_comments staff_task_comments_user_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.staff_task_comments
    ADD CONSTRAINT staff_task_comments_user_id_fkey FOREIGN KEY (user_id) REFERENCES public.users(id);


--
-- Name: staff_task_label_relations staff_task_label_relations_label_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.staff_task_label_relations
    ADD CONSTRAINT staff_task_label_relations_label_id_fkey FOREIGN KEY (label_id) REFERENCES public.staff_task_labels(id) ON DELETE CASCADE;


--
-- Name: staff_task_label_relations staff_task_label_relations_task_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.staff_task_label_relations
    ADD CONSTRAINT staff_task_label_relations_task_id_fkey FOREIGN KEY (task_id) REFERENCES public.staff_tasks(id) ON DELETE CASCADE;


--
-- Name: staff_task_labels staff_task_labels_board_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.staff_task_labels
    ADD CONSTRAINT staff_task_labels_board_id_fkey FOREIGN KEY (board_id) REFERENCES public.staff_task_boards(id) ON DELETE CASCADE;


--
-- Name: staff_tasks staff_tasks_board_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.staff_tasks
    ADD CONSTRAINT staff_tasks_board_id_fkey FOREIGN KEY (board_id) REFERENCES public.staff_task_boards(id) ON DELETE CASCADE;


--
-- Name: staff_tasks staff_tasks_column_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.staff_tasks
    ADD CONSTRAINT staff_tasks_column_id_fkey FOREIGN KEY (column_id) REFERENCES public.staff_task_columns(id) ON DELETE CASCADE;


--
-- Name: staff_tasks staff_tasks_created_by_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.staff_tasks
    ADD CONSTRAINT staff_tasks_created_by_fkey FOREIGN KEY (created_by) REFERENCES public.users(id);


--
-- Name: staff_tasks staff_tasks_parent_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.staff_tasks
    ADD CONSTRAINT staff_tasks_parent_id_fkey FOREIGN KEY (parent_id) REFERENCES public.staff_tasks(id);


--
-- Name: staff_tasks staff_tasks_reporter_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.staff_tasks
    ADD CONSTRAINT staff_tasks_reporter_id_fkey FOREIGN KEY (reporter_id) REFERENCES public.users(id);


--
-- Name: storage_connections storage_connections_client_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.storage_connections
    ADD CONSTRAINT storage_connections_client_id_fkey FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE CASCADE;


--
-- Name: storage_files storage_files_connection_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.storage_files
    ADD CONSTRAINT storage_files_connection_id_fkey FOREIGN KEY (connection_id) REFERENCES public.storage_connections(id) ON DELETE CASCADE;


--
-- Name: support_ticket_comments support_ticket_comments_support_ticket_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.support_ticket_comments
    ADD CONSTRAINT support_ticket_comments_support_ticket_id_fkey FOREIGN KEY (support_ticket_id) REFERENCES public.support_tickets(id) ON DELETE CASCADE;


--
-- Name: support_ticket_comments support_ticket_comments_user_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.support_ticket_comments
    ADD CONSTRAINT support_ticket_comments_user_id_fkey FOREIGN KEY (user_id) REFERENCES public.users(id);


--
-- Name: support_tickets support_tickets_assigned_to_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.support_tickets
    ADD CONSTRAINT support_tickets_assigned_to_fkey FOREIGN KEY (assigned_to) REFERENCES public.users(id);


--
-- Name: support_tickets support_tickets_client_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.support_tickets
    ADD CONSTRAINT support_tickets_client_id_fkey FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE CASCADE;


--
-- Name: support_tickets support_tickets_created_by_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.support_tickets
    ADD CONSTRAINT support_tickets_created_by_fkey FOREIGN KEY (created_by) REFERENCES public.users(id);


--
-- Name: survey_answers survey_answers_question_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.survey_answers
    ADD CONSTRAINT survey_answers_question_id_fkey FOREIGN KEY (question_id) REFERENCES public.survey_questions(id) ON DELETE CASCADE;


--
-- Name: survey_answers survey_answers_response_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.survey_answers
    ADD CONSTRAINT survey_answers_response_id_fkey FOREIGN KEY (response_id) REFERENCES public.survey_responses(id) ON DELETE CASCADE;


--
-- Name: survey_questions survey_questions_survey_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.survey_questions
    ADD CONSTRAINT survey_questions_survey_id_fkey FOREIGN KEY (survey_id) REFERENCES public.surveys(id) ON DELETE CASCADE;


--
-- Name: survey_responses survey_responses_client_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.survey_responses
    ADD CONSTRAINT survey_responses_client_id_fkey FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE SET NULL;


--
-- Name: survey_responses survey_responses_respondent_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.survey_responses
    ADD CONSTRAINT survey_responses_respondent_id_fkey FOREIGN KEY (respondent_id) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: survey_responses survey_responses_survey_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.survey_responses
    ADD CONSTRAINT survey_responses_survey_id_fkey FOREIGN KEY (survey_id) REFERENCES public.surveys(id) ON DELETE CASCADE;


--
-- Name: surveys surveys_client_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.surveys
    ADD CONSTRAINT surveys_client_id_fkey FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE CASCADE;


--
-- Name: surveys surveys_created_by_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.surveys
    ADD CONSTRAINT surveys_created_by_fkey FOREIGN KEY (created_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: system_settings system_settings_updated_by_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.system_settings
    ADD CONSTRAINT system_settings_updated_by_fkey FOREIGN KEY (updated_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: time_entries time_entries_approved_by_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.time_entries
    ADD CONSTRAINT time_entries_approved_by_fkey FOREIGN KEY (approved_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: time_entries time_entries_client_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.time_entries
    ADD CONSTRAINT time_entries_client_id_fkey FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE SET NULL;


--
-- Name: time_entries time_entries_locked_by_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.time_entries
    ADD CONSTRAINT time_entries_locked_by_fkey FOREIGN KEY (locked_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: time_entries time_entries_request_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.time_entries
    ADD CONSTRAINT time_entries_request_id_fkey FOREIGN KEY (request_id) REFERENCES public.requests(id) ON DELETE SET NULL;


--
-- Name: time_entries time_entries_user_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.time_entries
    ADD CONSTRAINT time_entries_user_id_fkey FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: time_entry_locks time_entry_locks_locked_by_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.time_entry_locks
    ADD CONSTRAINT time_entry_locks_locked_by_fkey FOREIGN KEY (locked_by) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: time_entry_locks time_entry_locks_user_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.time_entry_locks
    ADD CONSTRAINT time_entry_locks_user_id_fkey FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: user_features user_features_feature_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_features
    ADD CONSTRAINT user_features_feature_id_fkey FOREIGN KEY (feature_id) REFERENCES public.features(id) ON DELETE CASCADE;


--
-- Name: user_features user_features_user_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_features
    ADD CONSTRAINT user_features_user_id_fkey FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: user_roles user_roles_assigned_by_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_roles
    ADD CONSTRAINT user_roles_assigned_by_fkey FOREIGN KEY (assigned_by) REFERENCES auth.users(id);


--
-- Name: user_roles user_roles_role_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_roles
    ADD CONSTRAINT user_roles_role_id_fkey FOREIGN KEY (role_id) REFERENCES public.roles(id) ON DELETE CASCADE;


--
-- Name: user_roles user_roles_user_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_roles
    ADD CONSTRAINT user_roles_user_id_fkey FOREIGN KEY (user_id) REFERENCES auth.users(id) ON DELETE CASCADE;


--
-- Name: users users_client_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_client_id_fkey FOREIGN KEY (client_id) REFERENCES public.clients(id);


--
-- Name: users users_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_id_fkey FOREIGN KEY (id) REFERENCES auth.users(id) ON DELETE CASCADE;


--
-- Name: webhook_deliveries webhook_deliveries_endpoint_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.webhook_deliveries
    ADD CONSTRAINT webhook_deliveries_endpoint_id_fkey FOREIGN KEY (endpoint_id) REFERENCES public.webhook_endpoints(id) ON DELETE CASCADE;


--
-- Name: webhook_endpoints webhook_endpoints_client_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.webhook_endpoints
    ADD CONSTRAINT webhook_endpoints_client_id_fkey FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE CASCADE;


--
-- Name: webhook_endpoints webhook_endpoints_created_by_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.webhook_endpoints
    ADD CONSTRAINT webhook_endpoints_created_by_fkey FOREIGN KEY (created_by) REFERENCES public.users(id);


--
-- Name: white_label_configs white_label_configs_client_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.white_label_configs
    ADD CONSTRAINT white_label_configs_client_id_fkey FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE CASCADE;


--
-- Name: email_templates Admin can manage email templates; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY "Admin can manage email templates" ON public.email_templates USING ((EXISTS ( SELECT 1
   FROM (public.user_roles ur
     JOIN public.roles r ON ((ur.role_id = r.id)))
  WHERE ((ur.user_id = auth.uid()) AND (r.name = ANY (ARRAY['super_admin'::text, 'admin'::text]))))));


--
-- Name: invoice_templates Admin can manage invoice templates; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY "Admin can manage invoice templates" ON public.invoice_templates USING ((EXISTS ( SELECT 1
   FROM (public.user_roles ur
     JOIN public.roles r ON ((ur.role_id = r.id)))
  WHERE ((ur.user_id = auth.uid()) AND (r.name = ANY (ARRAY['super_admin'::text, 'admin'::text]))))));


--
-- Name: time_entry_locks Admins can create period locks; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY "Admins can create period locks" ON public.time_entry_locks FOR INSERT WITH CHECK ((EXISTS ( SELECT 1
   FROM (public.user_roles ur
     JOIN public.roles r ON ((ur.role_id = r.id)))
  WHERE ((ur.user_id = auth.uid()) AND (r.name = ANY (ARRAY['super_admin'::text, 'admin'::text, 'account_manager'::text]))))));


--
-- Name: activity_logs Admins can delete activity logs; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY "Admins can delete activity logs" ON public.activity_logs FOR DELETE USING ((EXISTS ( SELECT 1
   FROM (public.user_roles ur
     JOIN public.roles r ON ((ur.role_id = r.id)))
  WHERE ((ur.user_id = auth.uid()) AND (r.name = ANY (ARRAY['super_admin'::text, 'admin'::text]))))));


--
-- Name: support_ticket_comments Admins can manage all support ticket comments; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY "Admins can manage all support ticket comments" ON public.support_ticket_comments USING (((EXISTS ( SELECT 1
   FROM public.users u
  WHERE ((u.id = auth.uid()) AND (u.is_super_admin = true)))) OR (EXISTS ( SELECT 1
   FROM (public.user_roles ur
     JOIN public.roles r ON ((ur.role_id = r.id)))
  WHERE ((ur.user_id = auth.uid()) AND (r.name = ANY (ARRAY['super_admin'::text, 'admin'::text, 'account_manager'::text, 'staff'::text]))))))) WITH CHECK (((EXISTS ( SELECT 1
   FROM public.users u
  WHERE ((u.id = auth.uid()) AND (u.is_super_admin = true)))) OR (EXISTS ( SELECT 1
   FROM (public.user_roles ur
     JOIN public.roles r ON ((ur.role_id = r.id)))
  WHERE ((ur.user_id = auth.uid()) AND (r.name = ANY (ARRAY['super_admin'::text, 'admin'::text, 'account_manager'::text, 'staff'::text])))))));


--
-- Name: support_tickets Admins can manage all support tickets; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY "Admins can manage all support tickets" ON public.support_tickets USING (((EXISTS ( SELECT 1
   FROM public.users u
  WHERE ((u.id = auth.uid()) AND (u.is_super_admin = true)))) OR (EXISTS ( SELECT 1
   FROM (public.user_roles ur
     JOIN public.roles r ON ((ur.role_id = r.id)))
  WHERE ((ur.user_id = auth.uid()) AND (r.name = ANY (ARRAY['super_admin'::text, 'admin'::text, 'account_manager'::text, 'staff'::text]))))))) WITH CHECK (((EXISTS ( SELECT 1
   FROM public.users u
  WHERE ((u.id = auth.uid()) AND (u.is_super_admin = true)))) OR (EXISTS ( SELECT 1
   FROM (public.user_roles ur
     JOIN public.roles r ON ((ur.role_id = r.id)))
  WHERE ((ur.user_id = auth.uid()) AND (r.name = ANY (ARRAY['super_admin'::text, 'admin'::text, 'account_manager'::text, 'staff'::text])))))));


--
-- Name: clients Admins can manage clients; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY "Admins can manage clients" ON public.clients USING ((EXISTS ( SELECT 1
   FROM (public.user_roles ur
     JOIN public.roles r ON ((ur.role_id = r.id)))
  WHERE ((ur.user_id = auth.uid()) AND (r.name = ANY (ARRAY['super_admin'::text, 'admin'::text]))))));


--
-- Name: time_entry_locks Admins can manage locks; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY "Admins can manage locks" ON public.time_entry_locks USING ((EXISTS ( SELECT 1
   FROM (public.user_roles ur
     JOIN public.roles r ON ((ur.role_id = r.id)))
  WHERE ((ur.user_id = auth.uid()) AND (r.name = ANY (ARRAY['super_admin'::text, 'admin'::text, 'account_manager'::text]))))));


--
-- Name: service_templates Admins can manage service templates; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY "Admins can manage service templates" ON public.service_templates TO authenticated USING (((EXISTS ( SELECT 1
   FROM public.users u
  WHERE ((u.id = auth.uid()) AND (u.is_super_admin = true)))) OR (EXISTS ( SELECT 1
   FROM (public.user_roles ur
     JOIN public.roles r ON ((r.id = ur.role_id)))
  WHERE ((ur.user_id = auth.uid()) AND (r.name = ANY (ARRAY['admin'::text, 'super_admin'::text]))))))) WITH CHECK (((EXISTS ( SELECT 1
   FROM public.users u
  WHERE ((u.id = auth.uid()) AND (u.is_super_admin = true)))) OR (EXISTS ( SELECT 1
   FROM (public.user_roles ur
     JOIN public.roles r ON ((r.id = ur.role_id)))
  WHERE ((ur.user_id = auth.uid()) AND (r.name = ANY (ARRAY['admin'::text, 'super_admin'::text])))))));


--
-- Name: staff_assignments Admins can manage staff assignments; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY "Admins can manage staff assignments" ON public.staff_assignments USING ((EXISTS ( SELECT 1
   FROM (public.user_roles ur
     JOIN public.roles r ON ((ur.role_id = r.id)))
  WHERE ((ur.user_id = auth.uid()) AND (r.name = ANY (ARRAY['super_admin'::text, 'admin'::text, 'account_manager'::text]))))));


--
-- Name: support_ticket_comments Admins can view all support ticket comments; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY "Admins can view all support ticket comments" ON public.support_ticket_comments FOR SELECT USING (((EXISTS ( SELECT 1
   FROM public.users u
  WHERE ((u.id = auth.uid()) AND (u.is_super_admin = true)))) OR (EXISTS ( SELECT 1
   FROM (public.user_roles ur
     JOIN public.roles r ON ((ur.role_id = r.id)))
  WHERE ((ur.user_id = auth.uid()) AND (r.name = ANY (ARRAY['super_admin'::text, 'admin'::text, 'account_manager'::text, 'staff'::text])))))));


--
-- Name: support_tickets Admins can view all support tickets; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY "Admins can view all support tickets" ON public.support_tickets FOR SELECT USING (((EXISTS ( SELECT 1
   FROM public.users u
  WHERE ((u.id = auth.uid()) AND (u.is_super_admin = true)))) OR (EXISTS ( SELECT 1
   FROM (public.user_roles ur
     JOIN public.roles r ON ((ur.role_id = r.id)))
  WHERE ((ur.user_id = auth.uid()) AND (r.name = ANY (ARRAY['super_admin'::text, 'admin'::text, 'account_manager'::text, 'staff'::text])))))));


--
-- Name: proposal_views Anyone can insert proposal views; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY "Anyone can insert proposal views" ON public.proposal_views FOR INSERT WITH CHECK (true);


--
-- Name: permissions Anyone can view permissions; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY "Anyone can view permissions" ON public.permissions FOR SELECT USING (true);


--
-- Name: role_permissions Anyone can view role permissions; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY "Anyone can view role permissions" ON public.role_permissions FOR SELECT USING (true);


--
-- Name: roles Anyone can view roles; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY "Anyone can view roles" ON public.roles FOR SELECT USING (true);


--
-- Name: announcements Clients can view their announcements; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY "Clients can view their announcements" ON public.announcements FOR SELECT USING (((client_id IS NULL) OR (client_id = public.get_current_user_client_id()) OR (EXISTS ( SELECT 1
   FROM (public.user_roles ur
     JOIN public.roles r ON ((ur.role_id = r.id)))
  WHERE ((ur.user_id = auth.uid()) AND (r.name = ANY (ARRAY['super_admin'::text, 'admin'::text, 'account_manager'::text, 'staff'::text])))))));


--
-- Name: document_shares Document owners can create shares; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY "Document owners can create shares" ON public.document_shares FOR INSERT WITH CHECK (((EXISTS ( SELECT 1
   FROM public.documents d
  WHERE ((d.id = document_shares.document_id) AND (d.uploaded_by = auth.uid())))) OR (EXISTS ( SELECT 1
   FROM (public.user_roles ur
     JOIN public.roles r ON ((ur.role_id = r.id)))
  WHERE ((ur.user_id = auth.uid()) AND (r.name = ANY (ARRAY['super_admin'::text, 'admin'::text])))))));


--
-- Name: user_roles Only admins can assign roles; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY "Only admins can assign roles" ON public.user_roles FOR INSERT WITH CHECK (public.user_has_permission(auth.uid(), 'users.assign_roles'::text));


--
-- Name: role_permissions Only admins can manage role permissions; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY "Only admins can manage role permissions" ON public.role_permissions USING (public.user_has_permission(auth.uid(), 'roles.update'::text));


--
-- Name: roles Only admins can manage roles; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY "Only admins can manage roles" ON public.roles USING (public.user_has_permission(auth.uid(), 'roles.update'::text));


--
-- Name: user_roles Only admins can remove roles; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY "Only admins can remove roles" ON public.user_roles FOR DELETE USING (public.user_has_permission(auth.uid(), 'users.assign_roles'::text));


--
-- Name: project_review_comments Project members can create review comments; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY "Project members can create review comments" ON public.project_review_comments FOR INSERT TO authenticated WITH CHECK ((EXISTS ( SELECT 1
   FROM (public.projects p
     JOIN public.users u ON ((u.id = auth.uid())))
  WHERE ((p.id = project_review_comments.project_id) AND (p.deleted_at IS NULL) AND ((u.is_super_admin = true) OR (u.client_id = p.client_id) OR (EXISTS ( SELECT 1
           FROM (public.user_roles ur
             JOIN public.roles r ON ((r.id = ur.role_id)))
          WHERE ((ur.user_id = auth.uid()) AND (r.name = ANY (ARRAY['admin'::text, 'super_admin'::text, 'account_manager'::text, 'staff'::text]))))))))));


--
-- Name: project_review_items Project members can create review items; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY "Project members can create review items" ON public.project_review_items FOR INSERT TO authenticated WITH CHECK ((EXISTS ( SELECT 1
   FROM (public.projects p
     JOIN public.users u ON ((u.id = auth.uid())))
  WHERE ((p.id = project_review_items.project_id) AND (p.deleted_at IS NULL) AND ((u.is_super_admin = true) OR (u.client_id = p.client_id) OR (EXISTS ( SELECT 1
           FROM (public.user_roles ur
             JOIN public.roles r ON ((r.id = ur.role_id)))
          WHERE ((ur.user_id = auth.uid()) AND (r.name = ANY (ARRAY['admin'::text, 'super_admin'::text, 'account_manager'::text, 'staff'::text]))))))))));


--
-- Name: project_review_comments Project members can update review comments; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY "Project members can update review comments" ON public.project_review_comments FOR UPDATE TO authenticated USING ((EXISTS ( SELECT 1
   FROM (public.projects p
     JOIN public.users u ON ((u.id = auth.uid())))
  WHERE ((p.id = project_review_comments.project_id) AND (p.deleted_at IS NULL) AND ((u.is_super_admin = true) OR (u.client_id = p.client_id) OR (EXISTS ( SELECT 1
           FROM (public.user_roles ur
             JOIN public.roles r ON ((r.id = ur.role_id)))
          WHERE ((ur.user_id = auth.uid()) AND (r.name = ANY (ARRAY['admin'::text, 'super_admin'::text, 'account_manager'::text, 'staff'::text])))))))))) WITH CHECK ((EXISTS ( SELECT 1
   FROM (public.projects p
     JOIN public.users u ON ((u.id = auth.uid())))
  WHERE ((p.id = project_review_comments.project_id) AND (p.deleted_at IS NULL) AND ((u.is_super_admin = true) OR (u.client_id = p.client_id) OR (EXISTS ( SELECT 1
           FROM (public.user_roles ur
             JOIN public.roles r ON ((r.id = ur.role_id)))
          WHERE ((ur.user_id = auth.uid()) AND (r.name = ANY (ARRAY['admin'::text, 'super_admin'::text, 'account_manager'::text, 'staff'::text]))))))))));


--
-- Name: project_review_items Project members can update review items; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY "Project members can update review items" ON public.project_review_items FOR UPDATE TO authenticated USING ((EXISTS ( SELECT 1
   FROM (public.projects p
     JOIN public.users u ON ((u.id = auth.uid())))
  WHERE ((p.id = project_review_items.project_id) AND (p.deleted_at IS NULL) AND ((u.is_super_admin = true) OR (u.client_id = p.client_id) OR (EXISTS ( SELECT 1
           FROM (public.user_roles ur
             JOIN public.roles r ON ((r.id = ur.role_id)))
          WHERE ((ur.user_id = auth.uid()) AND (r.name = ANY (ARRAY['admin'::text, 'super_admin'::text, 'account_manager'::text, 'staff'::text])))))))))) WITH CHECK ((EXISTS ( SELECT 1
   FROM (public.projects p
     JOIN public.users u ON ((u.id = auth.uid())))
  WHERE ((p.id = project_review_items.project_id) AND (p.deleted_at IS NULL) AND ((u.is_super_admin = true) OR (u.client_id = p.client_id) OR (EXISTS ( SELECT 1
           FROM (public.user_roles ur
             JOIN public.roles r ON ((r.id = ur.role_id)))
          WHERE ((ur.user_id = auth.uid()) AND (r.name = ANY (ARRAY['admin'::text, 'super_admin'::text, 'account_manager'::text, 'staff'::text]))))))))));


--
-- Name: project_review_comments Project members can view review comments; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY "Project members can view review comments" ON public.project_review_comments FOR SELECT TO authenticated USING ((EXISTS ( SELECT 1
   FROM (public.projects p
     JOIN public.users u ON ((u.id = auth.uid())))
  WHERE ((p.id = project_review_comments.project_id) AND (p.deleted_at IS NULL) AND ((u.is_super_admin = true) OR (u.client_id = p.client_id) OR (EXISTS ( SELECT 1
           FROM (public.user_roles ur
             JOIN public.roles r ON ((r.id = ur.role_id)))
          WHERE ((ur.user_id = auth.uid()) AND (r.name = ANY (ARRAY['admin'::text, 'super_admin'::text, 'account_manager'::text, 'staff'::text]))))))))));


--
-- Name: project_review_items Project members can view review items; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY "Project members can view review items" ON public.project_review_items FOR SELECT TO authenticated USING ((EXISTS ( SELECT 1
   FROM (public.projects p
     JOIN public.users u ON ((u.id = auth.uid())))
  WHERE ((p.id = project_review_items.project_id) AND (p.deleted_at IS NULL) AND ((u.is_super_admin = true) OR (u.client_id = p.client_id) OR (EXISTS ( SELECT 1
           FROM (public.user_roles ur
             JOIN public.roles r ON ((r.id = ur.role_id)))
          WHERE ((ur.user_id = auth.uid()) AND (r.name = ANY (ARRAY['admin'::text, 'super_admin'::text, 'account_manager'::text, 'staff'::text]))))))))));


--
-- Name: document_shares Share creators can delete their shares; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY "Share creators can delete their shares" ON public.document_shares FOR DELETE USING (((shared_by = auth.uid()) OR (EXISTS ( SELECT 1
   FROM (public.user_roles ur
     JOIN public.roles r ON ((ur.role_id = r.id)))
  WHERE ((ur.user_id = auth.uid()) AND (r.name = ANY (ARRAY['super_admin'::text, 'admin'::text])))))));


--
-- Name: announcements Staff can manage announcements; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY "Staff can manage announcements" ON public.announcements USING ((EXISTS ( SELECT 1
   FROM (public.user_roles ur
     JOIN public.roles r ON ((ur.role_id = r.id)))
  WHERE ((ur.user_id = auth.uid()) AND (r.name = ANY (ARRAY['super_admin'::text, 'admin'::text, 'account_manager'::text]))))));


--
-- Name: contracts Staff can manage contracts; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY "Staff can manage contracts" ON public.contracts USING (((client_id = ( SELECT users.client_id
   FROM public.users
  WHERE (users.id = auth.uid()))) OR (EXISTS ( SELECT 1
   FROM (public.user_roles ur
     JOIN public.roles r ON ((ur.role_id = r.id)))
  WHERE ((ur.user_id = auth.uid()) AND (r.name = ANY (ARRAY['super_admin'::text, 'admin'::text, 'account_manager'::text])))))));


--
-- Name: project_cost_entries Staff can manage cost entries; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY "Staff can manage cost entries" ON public.project_cost_entries USING ((EXISTS ( SELECT 1
   FROM (public.user_roles ur
     JOIN public.roles r ON ((ur.role_id = r.id)))
  WHERE ((ur.user_id = auth.uid()) AND (r.name = ANY (ARRAY['super_admin'::text, 'admin'::text, 'account_manager'::text]))))));


--
-- Name: project_deliverables Staff can manage deliverables; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY "Staff can manage deliverables" ON public.project_deliverables USING ((EXISTS ( SELECT 1
   FROM (public.user_roles ur
     JOIN public.roles r ON ((ur.role_id = r.id)))
  WHERE ((ur.user_id = auth.uid()) AND (r.name = ANY (ARRAY['super_admin'::text, 'admin'::text, 'account_manager'::text]))))));


--
-- Name: invoices Staff can manage invoices; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY "Staff can manage invoices" ON public.invoices USING ((EXISTS ( SELECT 1
   FROM (public.user_roles ur
     JOIN public.roles r ON ((ur.role_id = r.id)))
  WHERE ((ur.user_id = auth.uid()) AND (r.name = ANY (ARRAY['super_admin'::text, 'admin'::text, 'account_manager'::text]))))));


--
-- Name: project_milestones Staff can manage milestones; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY "Staff can manage milestones" ON public.project_milestones USING ((EXISTS ( SELECT 1
   FROM (public.user_roles ur
     JOIN public.roles r ON ((ur.role_id = r.id)))
  WHERE ((ur.user_id = auth.uid()) AND (r.name = ANY (ARRAY['super_admin'::text, 'admin'::text, 'account_manager'::text]))))));


--
-- Name: project_budgets Staff can manage project budgets; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY "Staff can manage project budgets" ON public.project_budgets USING ((EXISTS ( SELECT 1
   FROM (public.user_roles ur
     JOIN public.roles r ON ((ur.role_id = r.id)))
  WHERE ((ur.user_id = auth.uid()) AND (r.name = ANY (ARRAY['super_admin'::text, 'admin'::text, 'account_manager'::text]))))));


--
-- Name: projects Staff can manage projects; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY "Staff can manage projects" ON public.projects USING ((EXISTS ( SELECT 1
   FROM (public.user_roles ur
     JOIN public.roles r ON ((ur.role_id = r.id)))
  WHERE ((ur.user_id = auth.uid()) AND (r.name = ANY (ARRAY['super_admin'::text, 'admin'::text, 'account_manager'::text]))))));


--
-- Name: proposal_selections Staff can manage proposal selections; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY "Staff can manage proposal selections" ON public.proposal_selections USING ((EXISTS ( SELECT 1
   FROM (public.user_roles ur
     JOIN public.roles r ON ((ur.role_id = r.id)))
  WHERE ((ur.user_id = auth.uid()) AND (r.name = ANY (ARRAY['super_admin'::text, 'admin'::text, 'account_manager'::text]))))));


--
-- Name: proposals Staff can manage proposals; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY "Staff can manage proposals" ON public.proposals USING ((EXISTS ( SELECT 1
   FROM (public.user_roles ur
     JOIN public.roles r ON ((ur.role_id = r.id)))
  WHERE ((ur.user_id = auth.uid()) AND (r.name = ANY (ARRAY['super_admin'::text, 'admin'::text, 'account_manager'::text]))))));


--
-- Name: service_templates Staff can view service templates; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY "Staff can view service templates" ON public.service_templates FOR SELECT TO authenticated USING (((EXISTS ( SELECT 1
   FROM public.users u
  WHERE ((u.id = auth.uid()) AND (u.is_super_admin = true)))) OR (EXISTS ( SELECT 1
   FROM (public.user_roles ur
     JOIN public.roles r ON ((r.id = ur.role_id)))
  WHERE ((ur.user_id = auth.uid()) AND (r.name = ANY (ARRAY['admin'::text, 'super_admin'::text, 'staff'::text])))))));


--
-- Name: activity_logs System can insert activity logs; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY "System can insert activity logs" ON public.activity_logs FOR INSERT WITH CHECK (true);


--
-- Name: request_comments Users can create comments; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY "Users can create comments" ON public.request_comments FOR INSERT WITH CHECK (((request_id IN ( SELECT requests.id
   FROM public.requests
  WHERE (requests.client_id IN ( SELECT users.client_id
           FROM public.users
          WHERE (users.id = auth.uid()))))) OR (EXISTS ( SELECT 1
   FROM (public.user_roles ur
     JOIN public.roles r ON ((ur.role_id = r.id)))
  WHERE ((ur.user_id = auth.uid()) AND (r.name = ANY (ARRAY['super_admin'::text, 'admin'::text])))))));


--
-- Name: support_ticket_comments Users can create comments on their client's tickets; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY "Users can create comments on their client's tickets" ON public.support_ticket_comments FOR INSERT WITH CHECK ((support_ticket_id IN ( SELECT support_tickets.id
   FROM public.support_tickets
  WHERE (support_tickets.client_id IN ( SELECT users.client_id
           FROM public.users
          WHERE (users.id = auth.uid()))))));


--
-- Name: request_time_entries Users can create request time entries; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY "Users can create request time entries" ON public.request_time_entries FOR INSERT WITH CHECK (((user_id = auth.uid()) OR (EXISTS ( SELECT 1
   FROM (public.user_roles ur
     JOIN public.roles r ON ((ur.role_id = r.id)))
  WHERE ((ur.user_id = auth.uid()) AND (r.name = ANY (ARRAY['super_admin'::text, 'admin'::text, 'account_manager'::text])))))));


--
-- Name: requests Users can create requests for their client; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY "Users can create requests for their client" ON public.requests FOR INSERT WITH CHECK (((client_id IN ( SELECT users.client_id
   FROM public.users
  WHERE (users.id = auth.uid()))) OR (EXISTS ( SELECT 1
   FROM (public.user_roles ur
     JOIN public.roles r ON ((ur.role_id = r.id)))
  WHERE ((ur.user_id = auth.uid()) AND (r.name = ANY (ARRAY['super_admin'::text, 'admin'::text, 'account_manager'::text])))))));


--
-- Name: support_tickets Users can create support tickets for their client; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY "Users can create support tickets for their client" ON public.support_tickets FOR INSERT WITH CHECK ((client_id IN ( SELECT users.client_id
   FROM public.users
  WHERE (users.id = auth.uid()))));


--
-- Name: time_entries Users can create their own time entries; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY "Users can create their own time entries" ON public.time_entries FOR INSERT WITH CHECK (((user_id = auth.uid()) OR (EXISTS ( SELECT 1
   FROM (public.user_roles ur
     JOIN public.roles r ON ((ur.role_id = r.id)))
  WHERE ((ur.user_id = auth.uid()) AND (r.name = ANY (ARRAY['super_admin'::text, 'admin'::text, 'account_manager'::text])))))));


--
-- Name: invoice_items Users can delete invoice items; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY "Users can delete invoice items" ON public.invoice_items FOR DELETE USING (((invoice_id IN ( SELECT invoices.id
   FROM public.invoices
  WHERE (invoices.client_id IN ( SELECT users.client_id
           FROM public.users
          WHERE (users.id = auth.uid()))))) OR (EXISTS ( SELECT 1
   FROM (public.user_roles ur
     JOIN public.roles r ON ((ur.role_id = r.id)))
  WHERE ((ur.user_id = auth.uid()) AND (r.name = ANY (ARRAY['super_admin'::text, 'admin'::text, 'account_manager'::text])))))));


--
-- Name: encrypted_settings Users can delete own client encrypted settings; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY "Users can delete own client encrypted settings" ON public.encrypted_settings FOR DELETE USING ((client_id IN ( SELECT users.client_id
   FROM public.users
  WHERE (users.id = auth.uid()))));


--
-- Name: request_comments Users can delete own comments; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY "Users can delete own comments" ON public.request_comments FOR DELETE USING (((user_id = auth.uid()) OR (EXISTS ( SELECT 1
   FROM (public.user_roles ur
     JOIN public.roles r ON ((ur.role_id = r.id)))
  WHERE ((ur.user_id = auth.uid()) AND (r.name = ANY (ARRAY['super_admin'::text, 'admin'::text])))))));


--
-- Name: support_tickets Users can delete their client's support tickets; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY "Users can delete their client's support tickets" ON public.support_tickets FOR DELETE USING ((client_id IN ( SELECT users.client_id
   FROM public.users
  WHERE (users.id = auth.uid()))));


--
-- Name: calendar_connections Users can delete their own calendar connections; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY "Users can delete their own calendar connections" ON public.calendar_connections FOR DELETE USING ((user_id = auth.uid()));


--
-- Name: support_ticket_comments Users can delete their own comments; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY "Users can delete their own comments" ON public.support_ticket_comments FOR DELETE USING ((user_id = auth.uid()));


--
-- Name: documents Users can delete their own documents; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY "Users can delete their own documents" ON public.documents FOR DELETE USING (((uploaded_by = auth.uid()) OR (EXISTS ( SELECT 1
   FROM (public.user_roles ur
     JOIN public.roles r ON ((ur.role_id = r.id)))
  WHERE ((ur.user_id = auth.uid()) AND (r.name = ANY (ARRAY['super_admin'::text, 'admin'::text])))))));


--
-- Name: request_time_entries Users can delete their own request time entries; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY "Users can delete their own request time entries" ON public.request_time_entries FOR DELETE USING (((user_id = auth.uid()) OR (EXISTS ( SELECT 1
   FROM (public.user_roles ur
     JOIN public.roles r ON ((ur.role_id = r.id)))
  WHERE ((ur.user_id = auth.uid()) AND (r.name = ANY (ARRAY['super_admin'::text, 'admin'::text, 'account_manager'::text])))))));


--
-- Name: time_entries Users can delete their own time entries; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY "Users can delete their own time entries" ON public.time_entries FOR DELETE USING ((((user_id = auth.uid()) AND (locked_at IS NULL) AND (billed_at IS NULL)) OR (EXISTS ( SELECT 1
   FROM (public.user_roles ur
     JOIN public.roles r ON ((ur.role_id = r.id)))
  WHERE ((ur.user_id = auth.uid()) AND (r.name = ANY (ARRAY['super_admin'::text, 'admin'::text, 'account_manager'::text])))))));


--
-- Name: invoice_items Users can insert invoice items; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY "Users can insert invoice items" ON public.invoice_items FOR INSERT WITH CHECK (((invoice_id IN ( SELECT invoices.id
   FROM public.invoices
  WHERE (invoices.client_id IN ( SELECT users.client_id
           FROM public.users
          WHERE (users.id = auth.uid()))))) OR (EXISTS ( SELECT 1
   FROM (public.user_roles ur
     JOIN public.roles r ON ((ur.role_id = r.id)))
  WHERE ((ur.user_id = auth.uid()) AND (r.name = ANY (ARRAY['super_admin'::text, 'admin'::text, 'account_manager'::text])))))));


--
-- Name: encrypted_settings Users can insert own client encrypted settings; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY "Users can insert own client encrypted settings" ON public.encrypted_settings FOR INSERT WITH CHECK ((client_id IN ( SELECT users.client_id
   FROM public.users
  WHERE (users.id = auth.uid()))));


--
-- Name: calendar_connections Users can insert their own calendar connections; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY "Users can insert their own calendar connections" ON public.calendar_connections FOR INSERT WITH CHECK ((user_id = auth.uid()));


--
-- Name: invoice_items Users can update invoice items; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY "Users can update invoice items" ON public.invoice_items FOR UPDATE USING (((invoice_id IN ( SELECT invoices.id
   FROM public.invoices
  WHERE (invoices.client_id IN ( SELECT users.client_id
           FROM public.users
          WHERE (users.id = auth.uid()))))) OR (EXISTS ( SELECT 1
   FROM (public.user_roles ur
     JOIN public.roles r ON ((ur.role_id = r.id)))
  WHERE ((ur.user_id = auth.uid()) AND (r.name = ANY (ARRAY['super_admin'::text, 'admin'::text, 'account_manager'::text])))))));


--
-- Name: encrypted_settings Users can update own client encrypted settings; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY "Users can update own client encrypted settings" ON public.encrypted_settings FOR UPDATE USING ((client_id IN ( SELECT users.client_id
   FROM public.users
  WHERE (users.id = auth.uid()))));


--
-- Name: request_comments Users can update own comments; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY "Users can update own comments" ON public.request_comments FOR UPDATE USING (((user_id = auth.uid()) OR (EXISTS ( SELECT 1
   FROM (public.user_roles ur
     JOIN public.roles r ON ((ur.role_id = r.id)))
  WHERE ((ur.user_id = auth.uid()) AND (r.name = ANY (ARRAY['super_admin'::text, 'admin'::text])))))));


--
-- Name: requests Users can update their client's requests; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY "Users can update their client's requests" ON public.requests FOR UPDATE USING (((client_id IN ( SELECT users.client_id
   FROM public.users
  WHERE (users.id = auth.uid()))) OR (EXISTS ( SELECT 1
   FROM (public.user_roles ur
     JOIN public.roles r ON ((ur.role_id = r.id)))
  WHERE ((ur.user_id = auth.uid()) AND (r.name = ANY (ARRAY['super_admin'::text, 'admin'::text, 'account_manager'::text])))))));


--
-- Name: support_tickets Users can update their client's support tickets; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY "Users can update their client's support tickets" ON public.support_tickets FOR UPDATE USING ((client_id IN ( SELECT users.client_id
   FROM public.users
  WHERE (users.id = auth.uid()))));


--
-- Name: calendar_connections Users can update their own calendar connections; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY "Users can update their own calendar connections" ON public.calendar_connections FOR UPDATE USING ((user_id = auth.uid()));


--
-- Name: support_ticket_comments Users can update their own comments; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY "Users can update their own comments" ON public.support_ticket_comments FOR UPDATE USING ((user_id = auth.uid()));


--
-- Name: documents Users can update their own documents; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY "Users can update their own documents" ON public.documents FOR UPDATE USING (((uploaded_by = auth.uid()) OR (client_id = ( SELECT users.client_id
   FROM public.users
  WHERE (users.id = auth.uid()))) OR (EXISTS ( SELECT 1
   FROM (public.user_roles ur
     JOIN public.roles r ON ((ur.role_id = r.id)))
  WHERE ((ur.user_id = auth.uid()) AND (r.name = ANY (ARRAY['super_admin'::text, 'admin'::text])))))));


--
-- Name: request_time_entries Users can update their own request time entries; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY "Users can update their own request time entries" ON public.request_time_entries FOR UPDATE USING (((user_id = auth.uid()) OR (EXISTS ( SELECT 1
   FROM (public.user_roles ur
     JOIN public.roles r ON ((ur.role_id = r.id)))
  WHERE ((ur.user_id = auth.uid()) AND (r.name = ANY (ARRAY['super_admin'::text, 'admin'::text, 'account_manager'::text])))))));


--
-- Name: time_entries Users can update their own time entries; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY "Users can update their own time entries" ON public.time_entries FOR UPDATE USING ((((user_id = auth.uid()) AND (locked_at IS NULL)) OR (EXISTS ( SELECT 1
   FROM (public.user_roles ur
     JOIN public.roles r ON ((ur.role_id = r.id)))
  WHERE ((ur.user_id = auth.uid()) AND (r.name = ANY (ARRAY['super_admin'::text, 'admin'::text, 'account_manager'::text])))))));


--
-- Name: documents Users can upload documents for their client; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY "Users can upload documents for their client" ON public.documents FOR INSERT WITH CHECK (((client_id = ( SELECT users.client_id
   FROM public.users
  WHERE (users.id = auth.uid()))) OR (EXISTS ( SELECT 1
   FROM (public.user_roles ur
     JOIN public.roles r ON ((ur.role_id = r.id)))
  WHERE ((ur.user_id = auth.uid()) AND (r.name = ANY (ARRAY['super_admin'::text, 'admin'::text, 'account_manager'::text])))))));


--
-- Name: project_budgets Users can view budgets for their client's projects; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY "Users can view budgets for their client's projects" ON public.project_budgets FOR SELECT USING (((project_id IN ( SELECT projects.id
   FROM public.projects
  WHERE (projects.client_id IN ( SELECT users.client_id
           FROM public.users
          WHERE (users.id = auth.uid()))))) OR (EXISTS ( SELECT 1
   FROM (public.user_roles ur
     JOIN public.roles r ON ((ur.role_id = r.id)))
  WHERE ((ur.user_id = auth.uid()) AND (r.name = ANY (ARRAY['super_admin'::text, 'admin'::text, 'account_manager'::text])))))));


--
-- Name: request_comments Users can view comments on their client's requests; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY "Users can view comments on their client's requests" ON public.request_comments FOR SELECT USING (((request_id IN ( SELECT requests.id
   FROM public.requests
  WHERE (requests.client_id IN ( SELECT users.client_id
           FROM public.users
          WHERE (users.id = auth.uid()))))) OR (EXISTS ( SELECT 1
   FROM (public.user_roles ur
     JOIN public.roles r ON ((ur.role_id = r.id)))
  WHERE ((ur.user_id = auth.uid()) AND (r.name = ANY (ARRAY['super_admin'::text, 'admin'::text])))))));


--
-- Name: support_ticket_comments Users can view comments on their client's tickets; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY "Users can view comments on their client's tickets" ON public.support_ticket_comments FOR SELECT USING ((support_ticket_id IN ( SELECT support_tickets.id
   FROM public.support_tickets
  WHERE (support_tickets.client_id IN ( SELECT users.client_id
           FROM public.users
          WHERE (users.id = auth.uid()))))));


--
-- Name: contracts Users can view contracts for their client; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY "Users can view contracts for their client" ON public.contracts FOR SELECT USING (((client_id = ( SELECT users.client_id
   FROM public.users
  WHERE (users.id = auth.uid()))) OR (EXISTS ( SELECT 1
   FROM (public.user_roles ur
     JOIN public.roles r ON ((ur.role_id = r.id)))
  WHERE ((ur.user_id = auth.uid()) AND (r.name = ANY (ARRAY['super_admin'::text, 'admin'::text, 'account_manager'::text])))))));


--
-- Name: project_cost_entries Users can view cost entries for their client's projects; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY "Users can view cost entries for their client's projects" ON public.project_cost_entries FOR SELECT USING (((project_id IN ( SELECT projects.id
   FROM public.projects
  WHERE (projects.client_id IN ( SELECT users.client_id
           FROM public.users
          WHERE (users.id = auth.uid()))))) OR (EXISTS ( SELECT 1
   FROM (public.user_roles ur
     JOIN public.roles r ON ((ur.role_id = r.id)))
  WHERE ((ur.user_id = auth.uid()) AND (r.name = ANY (ARRAY['super_admin'::text, 'admin'::text, 'account_manager'::text])))))));


--
-- Name: project_deliverables Users can view deliverables for their client's projects; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY "Users can view deliverables for their client's projects" ON public.project_deliverables FOR SELECT USING (((project_id IN ( SELECT projects.id
   FROM public.projects
  WHERE (projects.client_id IN ( SELECT users.client_id
           FROM public.users
          WHERE (users.id = auth.uid()))))) OR (EXISTS ( SELECT 1
   FROM (public.user_roles ur
     JOIN public.roles r ON ((ur.role_id = r.id)))
  WHERE ((ur.user_id = auth.uid()) AND (r.name = ANY (ARRAY['super_admin'::text, 'admin'::text, 'account_manager'::text])))))));


--
-- Name: documents Users can view documents for their client; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY "Users can view documents for their client" ON public.documents FOR SELECT USING (((client_id = ( SELECT users.client_id
   FROM public.users
  WHERE (users.id = auth.uid()))) OR (id IN ( SELECT document_shares.document_id
   FROM public.document_shares
  WHERE ((document_shares.shared_with_user_id = auth.uid()) AND ((document_shares.expires_at IS NULL) OR (document_shares.expires_at > now()))))) OR (EXISTS ( SELECT 1
   FROM (public.user_roles ur
     JOIN public.roles r ON ((ur.role_id = r.id)))
  WHERE ((ur.user_id = auth.uid()) AND (r.name = ANY (ARRAY['super_admin'::text, 'admin'::text])))))));


--
-- Name: invoice_items Users can view invoice items; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY "Users can view invoice items" ON public.invoice_items FOR SELECT USING (((invoice_id IN ( SELECT invoices.id
   FROM public.invoices
  WHERE (invoices.client_id IN ( SELECT users.client_id
           FROM public.users
          WHERE (users.id = auth.uid()))))) OR (EXISTS ( SELECT 1
   FROM (public.user_roles ur
     JOIN public.roles r ON ((ur.role_id = r.id)))
  WHERE ((ur.user_id = auth.uid()) AND (r.name = ANY (ARRAY['super_admin'::text, 'admin'::text, 'account_manager'::text])))))));


--
-- Name: project_milestones Users can view milestones for their client's projects; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY "Users can view milestones for their client's projects" ON public.project_milestones FOR SELECT USING (((project_id IN ( SELECT projects.id
   FROM public.projects
  WHERE (projects.client_id IN ( SELECT users.client_id
           FROM public.users
          WHERE (users.id = auth.uid()))))) OR (EXISTS ( SELECT 1
   FROM (public.user_roles ur
     JOIN public.roles r ON ((ur.role_id = r.id)))
  WHERE ((ur.user_id = auth.uid()) AND (r.name = ANY (ARRAY['super_admin'::text, 'admin'::text, 'account_manager'::text])))))));


--
-- Name: encrypted_settings Users can view own client encrypted settings; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY "Users can view own client encrypted settings" ON public.encrypted_settings FOR SELECT USING ((client_id IN ( SELECT users.client_id
   FROM public.users
  WHERE (users.id = auth.uid()))));


--
-- Name: request_time_entries Users can view request time entries; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY "Users can view request time entries" ON public.request_time_entries FOR SELECT USING (((user_id = auth.uid()) OR (request_id IN ( SELECT requests.id
   FROM public.requests
  WHERE (requests.client_id IN ( SELECT users.client_id
           FROM public.users
          WHERE (users.id = auth.uid()))))) OR (EXISTS ( SELECT 1
   FROM (public.user_roles ur
     JOIN public.roles r ON ((ur.role_id = r.id)))
  WHERE ((ur.user_id = auth.uid()) AND (r.name = ANY (ARRAY['super_admin'::text, 'admin'::text])))))));


--
-- Name: proposal_selections Users can view selections for their client's proposals; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY "Users can view selections for their client's proposals" ON public.proposal_selections FOR SELECT USING (((proposal_id IN ( SELECT proposals.id
   FROM public.proposals
  WHERE (proposals.client_id IN ( SELECT users.client_id
           FROM public.users
          WHERE (users.id = auth.uid()))))) OR (EXISTS ( SELECT 1
   FROM (public.user_roles ur
     JOIN public.roles r ON ((ur.role_id = r.id)))
  WHERE ((ur.user_id = auth.uid()) AND (r.name = ANY (ARRAY['super_admin'::text, 'admin'::text, 'account_manager'::text])))))));


--
-- Name: staff_assignments Users can view staff assignments for their client; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY "Users can view staff assignments for their client" ON public.staff_assignments FOR SELECT USING (((client_id = public.get_current_user_client_id()) OR (EXISTS ( SELECT 1
   FROM (public.user_roles ur
     JOIN public.roles r ON ((ur.role_id = r.id)))
  WHERE ((ur.user_id = auth.uid()) AND (r.name = ANY (ARRAY['super_admin'::text, 'admin'::text])))))));


--
-- Name: clients Users can view their client or admins view all; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY "Users can view their client or admins view all" ON public.clients FOR SELECT USING (((id = public.get_current_user_client_id()) OR (EXISTS ( SELECT 1
   FROM (public.user_roles ur
     JOIN public.roles r ON ((ur.role_id = r.id)))
  WHERE ((ur.user_id = auth.uid()) AND (r.name = ANY (ARRAY['super_admin'::text, 'admin'::text])))))));


--
-- Name: POLICY "Users can view their client or admins view all" ON clients; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON POLICY "Users can view their client or admins view all" ON public.clients IS 'RBAC-based policy added in migration 001.5';


--
-- Name: activity_logs Users can view their client's activity logs; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY "Users can view their client's activity logs" ON public.activity_logs FOR SELECT USING (((client_id = public.get_current_user_client_id()) OR (EXISTS ( SELECT 1
   FROM (public.user_roles ur
     JOIN public.roles r ON ((ur.role_id = r.id)))
  WHERE ((ur.user_id = auth.uid()) AND (r.name = ANY (ARRAY['super_admin'::text, 'admin'::text])))))));


--
-- Name: invoices Users can view their client's invoices; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY "Users can view their client's invoices" ON public.invoices FOR SELECT USING (((client_id IN ( SELECT users.client_id
   FROM public.users
  WHERE (users.id = auth.uid()))) OR (EXISTS ( SELECT 1
   FROM (public.user_roles ur
     JOIN public.roles r ON ((ur.role_id = r.id)))
  WHERE ((ur.user_id = auth.uid()) AND (r.name = ANY (ARRAY['super_admin'::text, 'admin'::text, 'account_manager'::text])))))));


--
-- Name: projects Users can view their client's projects; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY "Users can view their client's projects" ON public.projects FOR SELECT USING (((client_id IN ( SELECT users.client_id
   FROM public.users
  WHERE (users.id = auth.uid()))) OR (EXISTS ( SELECT 1
   FROM (public.user_roles ur
     JOIN public.roles r ON ((ur.role_id = r.id)))
  WHERE ((ur.user_id = auth.uid()) AND (r.name = ANY (ARRAY['super_admin'::text, 'admin'::text, 'account_manager'::text])))))));


--
-- Name: proposal_views Users can view their client's proposal views; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY "Users can view their client's proposal views" ON public.proposal_views FOR SELECT USING (((proposal_id IN ( SELECT proposals.id
   FROM public.proposals
  WHERE (proposals.client_id IN ( SELECT users.client_id
           FROM public.users
          WHERE (users.id = auth.uid()))))) OR (EXISTS ( SELECT 1
   FROM (public.user_roles ur
     JOIN public.roles r ON ((ur.role_id = r.id)))
  WHERE ((ur.user_id = auth.uid()) AND (r.name = ANY (ARRAY['super_admin'::text, 'admin'::text, 'account_manager'::text])))))));


--
-- Name: proposals Users can view their client's proposals; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY "Users can view their client's proposals" ON public.proposals FOR SELECT USING (((client_id IN ( SELECT users.client_id
   FROM public.users
  WHERE (users.id = auth.uid()))) OR (EXISTS ( SELECT 1
   FROM (public.user_roles ur
     JOIN public.roles r ON ((ur.role_id = r.id)))
  WHERE ((ur.user_id = auth.uid()) AND (r.name = ANY (ARRAY['super_admin'::text, 'admin'::text, 'account_manager'::text])))))));


--
-- Name: requests Users can view their client's requests; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY "Users can view their client's requests" ON public.requests FOR SELECT USING (((client_id IN ( SELECT users.client_id
   FROM public.users
  WHERE (users.id = auth.uid()))) OR (EXISTS ( SELECT 1
   FROM (public.user_roles ur
     JOIN public.roles r ON ((ur.role_id = r.id)))
  WHERE ((ur.user_id = auth.uid()) AND (r.name = ANY (ARRAY['super_admin'::text, 'admin'::text])))))));


--
-- Name: support_tickets Users can view their client's support tickets; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY "Users can view their client's support tickets" ON public.support_tickets FOR SELECT USING ((client_id IN ( SELECT users.client_id
   FROM public.users
  WHERE (users.id = auth.uid()))));


--
-- Name: document_shares Users can view their document shares; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY "Users can view their document shares" ON public.document_shares FOR SELECT USING (((shared_with_user_id = auth.uid()) OR (shared_by = auth.uid()) OR (EXISTS ( SELECT 1
   FROM (public.user_roles ur
     JOIN public.roles r ON ((ur.role_id = r.id)))
  WHERE ((ur.user_id = auth.uid()) AND (r.name = ANY (ARRAY['super_admin'::text, 'admin'::text])))))));


--
-- Name: calendar_connections Users can view their own calendar connections; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY "Users can view their own calendar connections" ON public.calendar_connections FOR SELECT USING ((user_id = auth.uid()));


--
-- Name: time_entry_locks Users can view their own locks; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY "Users can view their own locks" ON public.time_entry_locks FOR SELECT USING (((user_id = auth.uid()) OR (EXISTS ( SELECT 1
   FROM (public.user_roles ur
     JOIN public.roles r ON ((ur.role_id = r.id)))
  WHERE ((ur.user_id = auth.uid()) AND (r.name = ANY (ARRAY['super_admin'::text, 'admin'::text, 'account_manager'::text])))))));


--
-- Name: user_roles Users can view their own roles; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY "Users can view their own roles" ON public.user_roles FOR SELECT USING (((user_id = auth.uid()) OR public.user_has_permission(auth.uid(), 'users.read'::text)));


--
-- Name: time_entries Users can view their own time entries; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY "Users can view their own time entries" ON public.time_entries FOR SELECT USING (((user_id = auth.uid()) OR (client_id IN ( SELECT users.client_id
   FROM public.users
  WHERE (users.id = auth.uid()))) OR (EXISTS ( SELECT 1
   FROM (public.user_roles ur
     JOIN public.roles r ON ((ur.role_id = r.id)))
  WHERE ((ur.user_id = auth.uid()) AND (r.name = ANY (ARRAY['super_admin'::text, 'admin'::text, 'account_manager'::text])))))));


--
-- Name: account_health; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.account_health ENABLE ROW LEVEL SECURITY;

--
-- Name: activity_logs; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.activity_logs ENABLE ROW LEVEL SECURITY;

--
-- Name: ad_accounts; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.ad_accounts ENABLE ROW LEVEL SECURITY;

--
-- Name: ad_campaigns; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.ad_campaigns ENABLE ROW LEVEL SECURITY;

--
-- Name: ad_creatives; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.ad_creatives ENABLE ROW LEVEL SECURITY;

--
-- Name: ad_metrics; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.ad_metrics ENABLE ROW LEVEL SECURITY;

--
-- Name: ad_sets; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.ad_sets ENABLE ROW LEVEL SECURITY;

--
-- Name: message_attachments add_attachments; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY add_attachments ON public.message_attachments FOR INSERT WITH CHECK ((auth.uid() IS NOT NULL));


--
-- Name: features admin_all_features; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY admin_all_features ON public.features USING ((EXISTS ( SELECT 1
   FROM public.users
  WHERE ((users.id = auth.uid()) AND (users.is_super_admin = true)))));


--
-- Name: client_features admin_client_features; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY admin_client_features ON public.client_features USING ((EXISTS ( SELECT 1
   FROM public.users
  WHERE ((users.id = auth.uid()) AND (users.is_super_admin = true)))));


--
-- Name: ai_providers admin_manage_ai_providers; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY admin_manage_ai_providers ON public.ai_providers USING (public.is_admin_or_super_admin());


--
-- Name: ai_insight_reports admin_manage_ai_reports; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY admin_manage_ai_reports ON public.ai_insight_reports USING (public.is_admin_or_super_admin());


--
-- Name: maintenance_plan_billing_history admin_manage_billing; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY admin_manage_billing ON public.maintenance_plan_billing_history USING (public.is_admin_or_super_admin());


--
-- Name: conversations admin_manage_conversations; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY admin_manage_conversations ON public.conversations USING (public.is_admin_or_super_admin());


--
-- Name: report_deliveries admin_manage_deliveries; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY admin_manage_deliveries ON public.report_deliveries USING (public.is_admin_or_super_admin());


--
-- Name: webhook_deliveries admin_manage_deliveries; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY admin_manage_deliveries ON public.webhook_deliveries USING (public.is_admin_or_super_admin());


--
-- Name: staff_guide_categories admin_manage_guide_categories; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY admin_manage_guide_categories ON public.staff_guide_categories USING (public.is_admin_or_super_admin());


--
-- Name: staff_guides admin_manage_guides; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY admin_manage_guides ON public.staff_guides USING (public.is_admin_or_super_admin());


--
-- Name: account_health admin_manage_health; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY admin_manage_health ON public.account_health USING (public.is_admin_or_super_admin());


--
-- Name: knowledge_base_categories admin_manage_kb_categories; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY admin_manage_kb_categories ON public.knowledge_base_categories USING (public.is_admin_or_super_admin());


--
-- Name: automation_logs admin_manage_logs; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY admin_manage_logs ON public.automation_logs USING (public.is_admin_or_super_admin());


--
-- Name: partners admin_manage_partners; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY admin_manage_partners ON public.partners USING (public.is_admin_or_super_admin());


--
-- Name: data_privacy_requests admin_manage_privacy; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY admin_manage_privacy ON public.data_privacy_requests FOR UPDATE USING (public.is_admin_or_super_admin());


--
-- Name: referrals admin_manage_referrals; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY admin_manage_referrals ON public.referrals USING (public.is_admin_or_super_admin());


--
-- Name: report_templates admin_manage_report_templates; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY admin_manage_report_templates ON public.report_templates USING (public.is_admin_or_super_admin());


--
-- Name: automation_rules admin_manage_rules; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY admin_manage_rules ON public.automation_rules USING (public.is_admin_or_super_admin());


--
-- Name: client_health_snapshots admin_manage_snapshots; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY admin_manage_snapshots ON public.client_health_snapshots USING (public.is_admin_or_super_admin());


--
-- Name: storage_connections admin_manage_storage; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY admin_manage_storage ON public.storage_connections USING (public.is_admin_or_super_admin());


--
-- Name: webhook_endpoints admin_manage_webhooks; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY admin_manage_webhooks ON public.webhook_endpoints USING (public.is_admin_or_super_admin());


--
-- Name: white_label_configs admin_manage_white_label; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY admin_manage_white_label ON public.white_label_configs USING (public.is_admin_or_super_admin());


--
-- Name: ads; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.ads ENABLE ROW LEVEL SECURITY;

--
-- Name: ai_conversations; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.ai_conversations ENABLE ROW LEVEL SECURITY;

--
-- Name: ai_insight_reports; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.ai_insight_reports ENABLE ROW LEVEL SECURITY;

--
-- Name: ai_message_feedback; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.ai_message_feedback ENABLE ROW LEVEL SECURITY;

--
-- Name: ai_messages; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.ai_messages ENABLE ROW LEVEL SECURITY;

--
-- Name: ai_providers; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.ai_providers ENABLE ROW LEVEL SECURITY;

--
-- Name: ai_tasks; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.ai_tasks ENABLE ROW LEVEL SECURITY;

--
-- Name: ai_usage_tracking; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.ai_usage_tracking ENABLE ROW LEVEL SECURITY;

--
-- Name: ai_workflows; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.ai_workflows ENABLE ROW LEVEL SECURITY;

--
-- Name: announcements; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.announcements ENABLE ROW LEVEL SECURITY;

--
-- Name: conversations authenticated_create_conversations; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY authenticated_create_conversations ON public.conversations FOR INSERT WITH CHECK ((auth.uid() IS NOT NULL));


--
-- Name: automation_logs; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.automation_logs ENABLE ROW LEVEL SECURITY;

--
-- Name: automation_rules; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.automation_rules ENABLE ROW LEVEL SECURITY;

--
-- Name: automation_runs; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.automation_runs ENABLE ROW LEVEL SECURITY;

--
-- Name: brand_assets; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.brand_assets ENABLE ROW LEVEL SECURITY;

--
-- Name: brand_audits; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.brand_audits ENABLE ROW LEVEL SECURITY;

--
-- Name: brand_colors; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.brand_colors ENABLE ROW LEVEL SECURITY;

--
-- Name: brand_competitors; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.brand_competitors ENABLE ROW LEVEL SECURITY;

--
-- Name: brand_fonts; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.brand_fonts ENABLE ROW LEVEL SECURITY;

--
-- Name: brand_guide_sections; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.brand_guide_sections ENABLE ROW LEVEL SECURITY;

--
-- Name: brand_guides; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.brand_guides ENABLE ROW LEVEL SECURITY;

--
-- Name: brand_inconsistencies; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.brand_inconsistencies ENABLE ROW LEVEL SECURITY;

--
-- Name: brand_mentions; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.brand_mentions ENABLE ROW LEVEL SECURITY;

--
-- Name: brand_templates; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.brand_templates ENABLE ROW LEVEL SECURITY;

--
-- Name: calendar_connections; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.calendar_connections ENABLE ROW LEVEL SECURITY;

--
-- Name: campaign_assets; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.campaign_assets ENABLE ROW LEVEL SECURITY;

--
-- Name: campaign_metrics; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.campaign_metrics ENABLE ROW LEVEL SECURITY;

--
-- Name: campaigns; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.campaigns ENABLE ROW LEVEL SECURITY;

--
-- Name: client_features; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.client_features ENABLE ROW LEVEL SECURITY;

--
-- Name: client_health_snapshots; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.client_health_snapshots ENABLE ROW LEVEL SECURITY;

--
-- Name: clients; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.clients ENABLE ROW LEVEL SECURITY;

--
-- Name: content_calendar_items; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.content_calendar_items ENABLE ROW LEVEL SECURITY;

--
-- Name: content_templates; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.content_templates ENABLE ROW LEVEL SECURITY;

--
-- Name: contracts; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.contracts ENABLE ROW LEVEL SECURITY;

--
-- Name: conversation_participants; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.conversation_participants ENABLE ROW LEVEL SECURITY;

--
-- Name: conversations; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.conversations ENABLE ROW LEVEL SECURITY;

--
-- Name: ai_conversations create_ai_conversations; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY create_ai_conversations ON public.ai_conversations FOR INSERT WITH CHECK ((user_id = auth.uid()));


--
-- Name: ai_message_feedback create_ai_feedback; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY create_ai_feedback ON public.ai_message_feedback FOR INSERT WITH CHECK ((user_id = auth.uid()));


--
-- Name: ai_messages create_ai_messages; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY create_ai_messages ON public.ai_messages FOR INSERT WITH CHECK ((auth.uid() IS NOT NULL));


--
-- Name: ai_tasks create_ai_tasks; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY create_ai_tasks ON public.ai_tasks FOR INSERT WITH CHECK ((user_id = auth.uid()));


--
-- Name: knowledge_base_feedback create_kb_feedback; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY create_kb_feedback ON public.knowledge_base_feedback FOR INSERT WITH CHECK ((auth.uid() IS NOT NULL));


--
-- Name: data_privacy_requests create_privacy_requests; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY create_privacy_requests ON public.data_privacy_requests FOR INSERT WITH CHECK ((user_id = auth.uid()));


--
-- Name: custom_dashboards; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.custom_dashboards ENABLE ROW LEVEL SECURITY;

--
-- Name: data_privacy_requests; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.data_privacy_requests ENABLE ROW LEVEL SECURITY;

--
-- Name: ai_conversations delete_own_ai_conversations; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY delete_own_ai_conversations ON public.ai_conversations FOR DELETE USING (((user_id = auth.uid()) OR public.is_admin_or_super_admin()));


--
-- Name: custom_dashboards delete_own_dashboards; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY delete_own_dashboards ON public.custom_dashboards FOR DELETE USING (((user_id = auth.uid()) OR public.is_admin_or_super_admin()));


--
-- Name: messages delete_own_messages; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY delete_own_messages ON public.messages FOR DELETE USING (((sender_id = auth.uid()) OR public.is_admin_or_super_admin()));


--
-- Name: staff_task_comments delete_own_task_comments; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY delete_own_task_comments ON public.staff_task_comments FOR DELETE USING (((user_id = auth.uid()) OR public.is_admin_or_super_admin()));


--
-- Name: document_shares; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.document_shares ENABLE ROW LEVEL SECURITY;

--
-- Name: documents; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.documents ENABLE ROW LEVEL SECURITY;

--
-- Name: messages edit_own_messages; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY edit_own_messages ON public.messages FOR UPDATE USING ((sender_id = auth.uid()));


--
-- Name: email_templates; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.email_templates ENABLE ROW LEVEL SECURITY;

--
-- Name: encrypted_settings; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.encrypted_settings ENABLE ROW LEVEL SECURITY;

--
-- Name: features; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.features ENABLE ROW LEVEL SECURITY;

--
-- Name: form_templates; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.form_templates ENABLE ROW LEVEL SECURITY;

--
-- Name: ai_usage_tracking insert_ai_usage; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY insert_ai_usage ON public.ai_usage_tracking FOR INSERT WITH CHECK ((auth.uid() IS NOT NULL));


--
-- Name: invoice_items; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.invoice_items ENABLE ROW LEVEL SECURITY;

--
-- Name: invoice_templates; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.invoice_templates ENABLE ROW LEVEL SECURITY;

--
-- Name: invoices; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.invoices ENABLE ROW LEVEL SECURITY;

--
-- Name: knowledge_base_articles; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.knowledge_base_articles ENABLE ROW LEVEL SECURITY;

--
-- Name: knowledge_base_categories; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.knowledge_base_categories ENABLE ROW LEVEL SECURITY;

--
-- Name: knowledge_base_feedback; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.knowledge_base_feedback ENABLE ROW LEVEL SECURITY;

--
-- Name: lead_activities; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.lead_activities ENABLE ROW LEVEL SECURITY;

--
-- Name: leads; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.leads ENABLE ROW LEVEL SECURITY;

--
-- Name: maintenance_plan_billing_history; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.maintenance_plan_billing_history ENABLE ROW LEVEL SECURITY;

--
-- Name: maintenance_plan_templates; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.maintenance_plan_templates ENABLE ROW LEVEL SECURITY;

--
-- Name: maintenance_plan_usage; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.maintenance_plan_usage ENABLE ROW LEVEL SECURITY;

--
-- Name: maintenance_plans; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.maintenance_plans ENABLE ROW LEVEL SECURITY;

--
-- Name: maintenance_plan_templates manage_maintenance_plan_templates; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY manage_maintenance_plan_templates ON public.maintenance_plan_templates USING (public.is_staff_or_above()) WITH CHECK (public.is_staff_or_above());


--
-- Name: ai_conversations manage_own_ai_conversations; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY manage_own_ai_conversations ON public.ai_conversations FOR UPDATE USING ((user_id = auth.uid()));


--
-- Name: ai_tasks manage_own_ai_tasks; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY manage_own_ai_tasks ON public.ai_tasks FOR UPDATE USING (((user_id = auth.uid()) OR public.is_admin_or_super_admin()));


--
-- Name: custom_dashboards manage_own_dashboards; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY manage_own_dashboards ON public.custom_dashboards FOR INSERT WITH CHECK ((user_id = auth.uid()));


--
-- Name: conversation_participants manage_participations; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY manage_participations ON public.conversation_participants USING ((auth.uid() IS NOT NULL));


--
-- Name: staff_tasks manage_tasks; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY manage_tasks ON public.staff_tasks USING (public.is_staff_or_above());


--
-- Name: message_reads mark_own_reads; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY mark_own_reads ON public.message_reads FOR INSERT WITH CHECK ((user_id = auth.uid()));


--
-- Name: meeting_attendees; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.meeting_attendees ENABLE ROW LEVEL SECURITY;

--
-- Name: meeting_notes; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.meeting_notes ENABLE ROW LEVEL SECURITY;

--
-- Name: meetings; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.meetings ENABLE ROW LEVEL SECURITY;

--
-- Name: message_attachments; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.message_attachments ENABLE ROW LEVEL SECURITY;

--
-- Name: message_reads; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.message_reads ENABLE ROW LEVEL SECURITY;

--
-- Name: messages; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.messages ENABLE ROW LEVEL SECURITY;

--
-- Name: staff_task_comments own_task_comments; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY own_task_comments ON public.staff_task_comments FOR UPDATE USING ((user_id = auth.uid()));


--
-- Name: partners; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.partners ENABLE ROW LEVEL SECURITY;

--
-- Name: permissions; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.permissions ENABLE ROW LEVEL SECURITY;

--
-- Name: project_budgets; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.project_budgets ENABLE ROW LEVEL SECURITY;

--
-- Name: project_cost_entries; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.project_cost_entries ENABLE ROW LEVEL SECURITY;

--
-- Name: project_deliverables; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.project_deliverables ENABLE ROW LEVEL SECURITY;

--
-- Name: project_milestones; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.project_milestones ENABLE ROW LEVEL SECURITY;

--
-- Name: project_review_comments; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.project_review_comments ENABLE ROW LEVEL SECURITY;

--
-- Name: project_review_items; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.project_review_items ENABLE ROW LEVEL SECURITY;

--
-- Name: projects; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.projects ENABLE ROW LEVEL SECURITY;

--
-- Name: prompt_templates; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.prompt_templates ENABLE ROW LEVEL SECURITY;

--
-- Name: proposal_selections; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.proposal_selections ENABLE ROW LEVEL SECURITY;

--
-- Name: proposal_views; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.proposal_views ENABLE ROW LEVEL SECURITY;

--
-- Name: proposals; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.proposals ENABLE ROW LEVEL SECURITY;

--
-- Name: knowledge_base_articles public_view_kb_articles; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY public_view_kb_articles ON public.knowledge_base_articles FOR SELECT USING (true);


--
-- Name: knowledge_base_categories public_view_kb_categories; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY public_view_kb_categories ON public.knowledge_base_categories FOR SELECT USING (true);


--
-- Name: referrals; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.referrals ENABLE ROW LEVEL SECURITY;

--
-- Name: report_deliveries; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.report_deliveries ENABLE ROW LEVEL SECURITY;

--
-- Name: report_schedules; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.report_schedules ENABLE ROW LEVEL SECURITY;

--
-- Name: report_templates; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.report_templates ENABLE ROW LEVEL SECURITY;

--
-- Name: request_comments; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.request_comments ENABLE ROW LEVEL SECURITY;

--
-- Name: request_time_entries; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.request_time_entries ENABLE ROW LEVEL SECURITY;

--
-- Name: requests; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.requests ENABLE ROW LEVEL SECURITY;

--
-- Name: role_features; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.role_features ENABLE ROW LEVEL SECURITY;

--
-- Name: role_permissions; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.role_permissions ENABLE ROW LEVEL SECURITY;

--
-- Name: roles; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.roles ENABLE ROW LEVEL SECURITY;

--
-- Name: messages send_messages; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY send_messages ON public.messages FOR INSERT WITH CHECK ((sender_id = auth.uid()));


--
-- Name: service_templates; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.service_templates ENABLE ROW LEVEL SECURITY;

--
-- Name: social_accounts; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.social_accounts ENABLE ROW LEVEL SECURITY;

--
-- Name: social_posts; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.social_posts ENABLE ROW LEVEL SECURITY;

--
-- Name: staff_assignments; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.staff_assignments ENABLE ROW LEVEL SECURITY;

--
-- Name: staff_task_comments staff_create_task_comments; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY staff_create_task_comments ON public.staff_task_comments FOR INSERT WITH CHECK (public.is_staff_or_above());


--
-- Name: staff_guide_categories; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.staff_guide_categories ENABLE ROW LEVEL SECURITY;

--
-- Name: staff_guide_views; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.staff_guide_views ENABLE ROW LEVEL SECURITY;

--
-- Name: staff_guides; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.staff_guides ENABLE ROW LEVEL SECURITY;

--
-- Name: ad_accounts staff_manage_ad_accounts; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY staff_manage_ad_accounts ON public.ad_accounts USING (public.is_staff_or_above());


--
-- Name: ad_campaigns staff_manage_ad_campaigns; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY staff_manage_ad_campaigns ON public.ad_campaigns USING (public.is_staff_or_above());


--
-- Name: ad_creatives staff_manage_ad_creatives; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY staff_manage_ad_creatives ON public.ad_creatives USING (public.is_staff_or_above());


--
-- Name: ad_metrics staff_manage_ad_metrics; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY staff_manage_ad_metrics ON public.ad_metrics USING (public.is_staff_or_above());


--
-- Name: ad_sets staff_manage_ad_sets; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY staff_manage_ad_sets ON public.ad_sets USING (public.is_staff_or_above());


--
-- Name: ads staff_manage_ads; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY staff_manage_ads ON public.ads USING (public.is_staff_or_above());


--
-- Name: ai_workflows staff_manage_ai_workflows; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY staff_manage_ai_workflows ON public.ai_workflows USING (public.is_staff_or_above());


--
-- Name: campaign_assets staff_manage_assets; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY staff_manage_assets ON public.campaign_assets USING (public.is_staff_or_above());


--
-- Name: staff_task_assignees staff_manage_assignees; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY staff_manage_assignees ON public.staff_task_assignees USING (public.is_staff_or_above());


--
-- Name: meeting_attendees staff_manage_attendees; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY staff_manage_attendees ON public.meeting_attendees USING (public.is_staff_or_above());


--
-- Name: brand_audits staff_manage_audits; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY staff_manage_audits ON public.brand_audits USING (public.is_staff_or_above());


--
-- Name: staff_task_boards staff_manage_boards; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY staff_manage_boards ON public.staff_task_boards USING (public.is_staff_or_above());


--
-- Name: brand_assets staff_manage_brand_assets; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY staff_manage_brand_assets ON public.brand_assets USING (public.is_staff_or_above());


--
-- Name: brand_colors staff_manage_brand_colors; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY staff_manage_brand_colors ON public.brand_colors USING (public.is_staff_or_above());


--
-- Name: brand_fonts staff_manage_brand_fonts; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY staff_manage_brand_fonts ON public.brand_fonts USING (public.is_staff_or_above());


--
-- Name: brand_guides staff_manage_brand_guides; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY staff_manage_brand_guides ON public.brand_guides USING (public.is_staff_or_above());


--
-- Name: brand_guide_sections staff_manage_brand_sections; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY staff_manage_brand_sections ON public.brand_guide_sections USING (public.is_staff_or_above());


--
-- Name: brand_templates staff_manage_brand_templates; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY staff_manage_brand_templates ON public.brand_templates USING (public.is_staff_or_above());


--
-- Name: content_calendar_items staff_manage_calendar; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY staff_manage_calendar ON public.content_calendar_items USING (public.is_staff_or_above());


--
-- Name: campaigns staff_manage_campaigns; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY staff_manage_campaigns ON public.campaigns USING (public.is_staff_or_above());


--
-- Name: staff_task_checklists staff_manage_checklists; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY staff_manage_checklists ON public.staff_task_checklists USING (public.is_staff_or_above());


--
-- Name: staff_task_columns staff_manage_columns; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY staff_manage_columns ON public.staff_task_columns USING (public.is_staff_or_above());


--
-- Name: brand_competitors staff_manage_competitors; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY staff_manage_competitors ON public.brand_competitors USING (public.is_staff_or_above());


--
-- Name: content_templates staff_manage_content_templates; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY staff_manage_content_templates ON public.content_templates USING (public.is_staff_or_above());


--
-- Name: form_templates staff_manage_form_templates; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY staff_manage_form_templates ON public.form_templates USING (public.is_staff_or_above());


--
-- Name: brand_inconsistencies staff_manage_inconsistencies; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY staff_manage_inconsistencies ON public.brand_inconsistencies USING (public.is_staff_or_above());


--
-- Name: knowledge_base_articles staff_manage_kb_articles; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY staff_manage_kb_articles ON public.knowledge_base_articles USING (public.is_staff_or_above());


--
-- Name: staff_task_label_relations staff_manage_label_rels; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY staff_manage_label_rels ON public.staff_task_label_relations USING (public.is_staff_or_above());


--
-- Name: staff_task_labels staff_manage_labels; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY staff_manage_labels ON public.staff_task_labels USING (public.is_staff_or_above());


--
-- Name: lead_activities staff_manage_lead_activities; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY staff_manage_lead_activities ON public.lead_activities USING (public.is_staff_or_above());


--
-- Name: leads staff_manage_leads; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY staff_manage_leads ON public.leads USING (public.is_staff_or_above());


--
-- Name: meeting_notes staff_manage_meeting_notes; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY staff_manage_meeting_notes ON public.meeting_notes USING (public.is_staff_or_above());


--
-- Name: meetings staff_manage_meetings; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY staff_manage_meetings ON public.meetings USING (public.is_staff_or_above());


--
-- Name: brand_mentions staff_manage_mentions; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY staff_manage_mentions ON public.brand_mentions USING (public.is_staff_or_above());


--
-- Name: campaign_metrics staff_manage_metrics; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY staff_manage_metrics ON public.campaign_metrics USING (public.is_staff_or_above());


--
-- Name: maintenance_plans staff_manage_plans; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY staff_manage_plans ON public.maintenance_plans USING (public.is_staff_or_above());


--
-- Name: social_posts staff_manage_posts; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY staff_manage_posts ON public.social_posts USING (public.is_staff_or_above());


--
-- Name: prompt_templates staff_manage_prompts; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY staff_manage_prompts ON public.prompt_templates USING (public.is_staff_or_above());


--
-- Name: survey_questions staff_manage_questions; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY staff_manage_questions ON public.survey_questions USING (public.is_staff_or_above());


--
-- Name: report_schedules staff_manage_schedules; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY staff_manage_schedules ON public.report_schedules USING (public.is_staff_or_above());


--
-- Name: social_accounts staff_manage_social; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY staff_manage_social ON public.social_accounts USING (public.is_staff_or_above());


--
-- Name: storage_files staff_manage_storage_files; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY staff_manage_storage_files ON public.storage_files USING (public.is_staff_or_above());


--
-- Name: surveys staff_manage_surveys; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY staff_manage_surveys ON public.surveys USING (public.is_staff_or_above());


--
-- Name: maintenance_plan_usage staff_manage_usage; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY staff_manage_usage ON public.maintenance_plan_usage USING (public.is_staff_or_above());


--
-- Name: staff_task_assignees; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.staff_task_assignees ENABLE ROW LEVEL SECURITY;

--
-- Name: staff_task_boards; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.staff_task_boards ENABLE ROW LEVEL SECURITY;

--
-- Name: staff_task_checklists; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.staff_task_checklists ENABLE ROW LEVEL SECURITY;

--
-- Name: staff_task_columns; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.staff_task_columns ENABLE ROW LEVEL SECURITY;

--
-- Name: staff_task_comments; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.staff_task_comments ENABLE ROW LEVEL SECURITY;

--
-- Name: staff_task_label_relations; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.staff_task_label_relations ENABLE ROW LEVEL SECURITY;

--
-- Name: staff_task_labels; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.staff_task_labels ENABLE ROW LEVEL SECURITY;

--
-- Name: staff_tasks; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.staff_tasks ENABLE ROW LEVEL SECURITY;

--
-- Name: ad_creatives staff_view_ad_creatives; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY staff_view_ad_creatives ON public.ad_creatives FOR SELECT USING (public.is_staff_or_above());


--
-- Name: ad_metrics staff_view_ad_metrics; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY staff_view_ad_metrics ON public.ad_metrics FOR SELECT USING (public.is_staff_or_above());


--
-- Name: ads staff_view_ads; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY staff_view_ads ON public.ads FOR SELECT USING (public.is_staff_or_above());


--
-- Name: staff_task_assignees staff_view_assignees; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY staff_view_assignees ON public.staff_task_assignees FOR SELECT USING (public.is_staff_or_above());


--
-- Name: staff_task_boards staff_view_boards; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY staff_view_boards ON public.staff_task_boards FOR SELECT USING (public.is_staff_or_above());


--
-- Name: staff_task_checklists staff_view_checklists; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY staff_view_checklists ON public.staff_task_checklists FOR SELECT USING (public.is_staff_or_above());


--
-- Name: staff_task_columns staff_view_columns; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY staff_view_columns ON public.staff_task_columns FOR SELECT USING (public.is_staff_or_above());


--
-- Name: staff_guide_categories staff_view_guide_categories; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY staff_view_guide_categories ON public.staff_guide_categories FOR SELECT USING (public.is_staff_or_above());


--
-- Name: staff_guides staff_view_guides; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY staff_view_guides ON public.staff_guides FOR SELECT USING (public.is_staff_or_above());


--
-- Name: staff_task_label_relations staff_view_label_rels; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY staff_view_label_rels ON public.staff_task_label_relations FOR SELECT USING (public.is_staff_or_above());


--
-- Name: staff_task_labels staff_view_labels; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY staff_view_labels ON public.staff_task_labels FOR SELECT USING (public.is_staff_or_above());


--
-- Name: automation_logs staff_view_logs; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY staff_view_logs ON public.automation_logs FOR SELECT USING (public.is_staff_or_above());


--
-- Name: partners staff_view_partners; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY staff_view_partners ON public.partners FOR SELECT USING (public.is_staff_or_above());


--
-- Name: report_templates staff_view_report_templates; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY staff_view_report_templates ON public.report_templates FOR SELECT USING (public.is_staff_or_above());


--
-- Name: automation_rules staff_view_rules; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY staff_view_rules ON public.automation_rules FOR SELECT USING (public.is_staff_or_above());


--
-- Name: staff_task_comments staff_view_task_comments; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY staff_view_task_comments ON public.staff_task_comments FOR SELECT USING (public.is_staff_or_above());


--
-- Name: storage_connections; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.storage_connections ENABLE ROW LEVEL SECURITY;

--
-- Name: storage_files; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.storage_files ENABLE ROW LEVEL SECURITY;

--
-- Name: survey_answers submit_answers; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY submit_answers ON public.survey_answers FOR INSERT WITH CHECK ((auth.uid() IS NOT NULL));


--
-- Name: survey_responses submit_responses; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY submit_responses ON public.survey_responses FOR INSERT WITH CHECK ((auth.uid() IS NOT NULL));


--
-- Name: support_ticket_comments; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.support_ticket_comments ENABLE ROW LEVEL SECURITY;

--
-- Name: support_tickets; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.support_tickets ENABLE ROW LEVEL SECURITY;

--
-- Name: survey_answers; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.survey_answers ENABLE ROW LEVEL SECURITY;

--
-- Name: survey_questions; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.survey_questions ENABLE ROW LEVEL SECURITY;

--
-- Name: survey_responses; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.survey_responses ENABLE ROW LEVEL SECURITY;

--
-- Name: surveys; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.surveys ENABLE ROW LEVEL SECURITY;

--
-- Name: automation_runs system_manage_runs; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY system_manage_runs ON public.automation_runs USING (public.is_admin_or_super_admin());


--
-- Name: system_settings; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.system_settings ENABLE ROW LEVEL SECURITY;

--
-- Name: time_entries; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.time_entries ENABLE ROW LEVEL SECURITY;

--
-- Name: time_entry_locks; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.time_entry_locks ENABLE ROW LEVEL SECURITY;

--
-- Name: staff_guide_views track_guide_views; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY track_guide_views ON public.staff_guide_views FOR INSERT WITH CHECK ((user_id = auth.uid()));


--
-- Name: custom_dashboards update_own_dashboards; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY update_own_dashboards ON public.custom_dashboards FOR UPDATE USING (((user_id = auth.uid()) OR public.is_admin_or_super_admin()));


--
-- Name: user_features; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.user_features ENABLE ROW LEVEL SECURITY;

--
-- Name: user_roles; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.user_roles ENABLE ROW LEVEL SECURITY;

--
-- Name: users; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.users ENABLE ROW LEVEL SECURITY;

--
-- Name: users users_all_for_admins; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY users_all_for_admins ON public.users USING ((EXISTS ( SELECT 1
   FROM (public.user_roles ur
     JOIN public.roles r ON ((ur.role_id = r.id)))
  WHERE ((ur.user_id = auth.uid()) AND (r.name = ANY (ARRAY['super_admin'::text, 'admin'::text]))))));


--
-- Name: client_features users_read_client_features; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY users_read_client_features ON public.client_features FOR SELECT USING ((client_id IN ( SELECT users.client_id
   FROM public.users
  WHERE (users.id = auth.uid()))));


--
-- Name: features users_read_features; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY users_read_features ON public.features FOR SELECT USING ((auth.uid() IS NOT NULL));


--
-- Name: users users_select_admin; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY users_select_admin ON public.users FOR SELECT USING ((EXISTS ( SELECT 1
   FROM (public.user_roles ur
     JOIN public.roles r ON ((ur.role_id = r.id)))
  WHERE ((ur.user_id = auth.uid()) AND (r.name = ANY (ARRAY['super_admin'::text, 'admin'::text]))))));


--
-- Name: POLICY users_select_admin ON users; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON POLICY users_select_admin ON public.users IS 'RBAC-based policy added in migration 001.5';


--
-- Name: users users_select_own; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY users_select_own ON public.users FOR SELECT USING ((id = auth.uid()));


--
-- Name: users users_select_same_client; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY users_select_same_client ON public.users FOR SELECT USING ((client_id = public.get_current_user_client_id()));


--
-- Name: users users_update_own; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY users_update_own ON public.users FOR UPDATE USING ((id = auth.uid()));


--
-- Name: ad_campaigns view_ad_campaigns; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY view_ad_campaigns ON public.ad_campaigns FOR SELECT USING ((EXISTS ( SELECT 1
   FROM public.ad_accounts aa
  WHERE ((aa.id = ad_campaigns.ad_account_id) AND ((aa.client_id = public.get_current_user_client_id()) OR public.is_admin_or_super_admin())))));


--
-- Name: ad_sets view_ad_sets; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY view_ad_sets ON public.ad_sets FOR SELECT USING ((EXISTS ( SELECT 1
   FROM (public.ad_campaigns ac
     JOIN public.ad_accounts aa ON ((aa.id = ac.ad_account_id)))
  WHERE ((ac.id = ad_sets.campaign_id) AND ((aa.client_id = public.get_current_user_client_id()) OR public.is_admin_or_super_admin())))));


--
-- Name: ai_providers view_ai_providers; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY view_ai_providers ON public.ai_providers FOR SELECT USING ((auth.uid() IS NOT NULL));


--
-- Name: maintenance_plan_billing_history view_billing_history; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY view_billing_history ON public.maintenance_plan_billing_history FOR SELECT USING ((EXISTS ( SELECT 1
   FROM public.maintenance_plans mp
  WHERE ((mp.id = maintenance_plan_billing_history.plan_id) AND ((mp.client_id = public.get_current_user_client_id()) OR public.is_admin_or_super_admin())))));


--
-- Name: brand_colors view_brand_colors; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY view_brand_colors ON public.brand_colors FOR SELECT USING ((EXISTS ( SELECT 1
   FROM public.brand_guides bg
  WHERE ((bg.id = brand_colors.brand_guide_id) AND ((bg.client_id IS NULL) OR (bg.client_id = public.get_current_user_client_id()) OR public.is_admin_or_super_admin())))));


--
-- Name: brand_fonts view_brand_fonts; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY view_brand_fonts ON public.brand_fonts FOR SELECT USING ((EXISTS ( SELECT 1
   FROM public.brand_guides bg
  WHERE ((bg.id = brand_fonts.brand_guide_id) AND ((bg.client_id IS NULL) OR (bg.client_id = public.get_current_user_client_id()) OR public.is_admin_or_super_admin())))));


--
-- Name: brand_guide_sections view_brand_sections; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY view_brand_sections ON public.brand_guide_sections FOR SELECT USING ((EXISTS ( SELECT 1
   FROM public.brand_guides bg
  WHERE ((bg.id = brand_guide_sections.brand_guide_id) AND ((bg.client_id IS NULL) OR (bg.client_id = public.get_current_user_client_id()) OR public.is_admin_or_super_admin())))));


--
-- Name: brand_templates view_brand_templates; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY view_brand_templates ON public.brand_templates FOR SELECT USING ((EXISTS ( SELECT 1
   FROM public.brand_guides bg
  WHERE ((bg.id = brand_templates.brand_guide_id) AND ((bg.client_id IS NULL) OR (bg.client_id = public.get_current_user_client_id()) OR public.is_admin_or_super_admin())))));


--
-- Name: campaign_assets view_campaign_assets; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY view_campaign_assets ON public.campaign_assets FOR SELECT USING ((EXISTS ( SELECT 1
   FROM public.campaigns c
  WHERE ((c.id = campaign_assets.campaign_id) AND ((c.client_id = public.get_current_user_client_id()) OR public.is_admin_or_super_admin())))));


--
-- Name: campaign_metrics view_campaign_metrics; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY view_campaign_metrics ON public.campaign_metrics FOR SELECT USING ((EXISTS ( SELECT 1
   FROM public.campaigns c
  WHERE ((c.id = campaign_metrics.campaign_id) AND ((c.client_id = public.get_current_user_client_id()) OR public.is_admin_or_super_admin())))));


--
-- Name: ad_accounts view_client_ad_accounts; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY view_client_ad_accounts ON public.ad_accounts FOR SELECT USING (((client_id = public.get_current_user_client_id()) OR public.is_admin_or_super_admin()));


--
-- Name: ai_insight_reports view_client_ai_reports; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY view_client_ai_reports ON public.ai_insight_reports FOR SELECT USING (((client_id = public.get_current_user_client_id()) OR public.is_admin_or_super_admin()));


--
-- Name: ai_workflows view_client_ai_workflows; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY view_client_ai_workflows ON public.ai_workflows FOR SELECT USING (((client_id = public.get_current_user_client_id()) OR public.is_admin_or_super_admin()));


--
-- Name: brand_audits view_client_audits; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY view_client_audits ON public.brand_audits FOR SELECT USING (((client_id IS NULL) OR (client_id = public.get_current_user_client_id()) OR public.is_admin_or_super_admin()));


--
-- Name: brand_assets view_client_brand_assets; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY view_client_brand_assets ON public.brand_assets FOR SELECT USING (((client_id IS NULL) OR (client_id = public.get_current_user_client_id()) OR public.is_admin_or_super_admin()));


--
-- Name: brand_guides view_client_brand_guides; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY view_client_brand_guides ON public.brand_guides FOR SELECT USING (((client_id IS NULL) OR (client_id = public.get_current_user_client_id()) OR public.is_admin_or_super_admin()));


--
-- Name: content_calendar_items view_client_calendar; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY view_client_calendar ON public.content_calendar_items FOR SELECT USING (((client_id = public.get_current_user_client_id()) OR public.is_admin_or_super_admin()));


--
-- Name: campaigns view_client_campaigns; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY view_client_campaigns ON public.campaigns FOR SELECT USING (((client_id = public.get_current_user_client_id()) OR public.is_admin_or_super_admin()));


--
-- Name: brand_competitors view_client_competitors; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY view_client_competitors ON public.brand_competitors FOR SELECT USING (((client_id IS NULL) OR (client_id = public.get_current_user_client_id()) OR public.is_admin_or_super_admin()));


--
-- Name: conversations view_client_conversations; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY view_client_conversations ON public.conversations FOR SELECT USING (((client_id IS NULL) OR (client_id = public.get_current_user_client_id()) OR public.is_admin_or_super_admin()));


--
-- Name: report_deliveries view_client_deliveries; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY view_client_deliveries ON public.report_deliveries FOR SELECT USING (((client_id = public.get_current_user_client_id()) OR public.is_admin_or_super_admin()));


--
-- Name: account_health view_client_health; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY view_client_health ON public.account_health FOR SELECT USING (((client_id = public.get_current_user_client_id()) OR public.is_admin_or_super_admin()));


--
-- Name: leads view_client_leads; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY view_client_leads ON public.leads FOR SELECT USING (((client_id = public.get_current_user_client_id()) OR public.is_admin_or_super_admin()));


--
-- Name: meetings view_client_meetings; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY view_client_meetings ON public.meetings FOR SELECT USING (((client_id = public.get_current_user_client_id()) OR public.is_admin_or_super_admin()));


--
-- Name: brand_mentions view_client_mentions; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY view_client_mentions ON public.brand_mentions FOR SELECT USING (((client_id IS NULL) OR (client_id = public.get_current_user_client_id()) OR public.is_admin_or_super_admin()));


--
-- Name: maintenance_plans view_client_plans; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY view_client_plans ON public.maintenance_plans FOR SELECT USING (((client_id = public.get_current_user_client_id()) OR public.is_admin_or_super_admin()));


--
-- Name: referrals view_client_referrals; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY view_client_referrals ON public.referrals FOR SELECT USING (((client_id = public.get_current_user_client_id()) OR public.is_admin_or_super_admin()));


--
-- Name: automation_runs view_client_runs; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY view_client_runs ON public.automation_runs FOR SELECT USING (((client_id = public.get_current_user_client_id()) OR public.is_admin_or_super_admin()));


--
-- Name: report_schedules view_client_schedules; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY view_client_schedules ON public.report_schedules FOR SELECT USING (((client_id = public.get_current_user_client_id()) OR public.is_admin_or_super_admin()));


--
-- Name: client_health_snapshots view_client_snapshots; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY view_client_snapshots ON public.client_health_snapshots FOR SELECT USING (((client_id = public.get_current_user_client_id()) OR public.is_admin_or_super_admin()));


--
-- Name: social_accounts view_client_social; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY view_client_social ON public.social_accounts FOR SELECT USING (((client_id = public.get_current_user_client_id()) OR public.is_admin_or_super_admin()));


--
-- Name: storage_connections view_client_storage; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY view_client_storage ON public.storage_connections FOR SELECT USING (((client_id = public.get_current_user_client_id()) OR public.is_admin_or_super_admin()));


--
-- Name: surveys view_client_surveys; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY view_client_surveys ON public.surveys FOR SELECT USING (((client_id = public.get_current_user_client_id()) OR public.is_admin_or_super_admin()));


--
-- Name: staff_tasks view_client_tasks; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY view_client_tasks ON public.staff_tasks FOR SELECT USING ((((client_id IS NULL) AND public.is_staff_or_above()) OR (client_id = public.get_current_user_client_id()) OR public.is_admin_or_super_admin()));


--
-- Name: webhook_endpoints view_client_webhooks; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY view_client_webhooks ON public.webhook_endpoints FOR SELECT USING (((client_id = public.get_current_user_client_id()) OR public.is_admin_or_super_admin()));


--
-- Name: content_templates view_content_templates; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY view_content_templates ON public.content_templates FOR SELECT USING (((client_id IS NULL) OR (client_id = public.get_current_user_client_id()) OR public.is_admin_or_super_admin()));


--
-- Name: messages view_conversation_messages; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY view_conversation_messages ON public.messages FOR SELECT USING (((EXISTS ( SELECT 1
   FROM public.conversation_participants cp
  WHERE ((cp.conversation_id = messages.conversation_id) AND (cp.user_id = auth.uid())))) OR public.is_admin_or_super_admin()));


--
-- Name: form_templates view_form_templates; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY view_form_templates ON public.form_templates FOR SELECT USING (((client_id IS NULL) OR (client_id = public.get_current_user_client_id()) OR public.is_admin_or_super_admin()));


--
-- Name: brand_inconsistencies view_inconsistencies; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY view_inconsistencies ON public.brand_inconsistencies FOR SELECT USING ((EXISTS ( SELECT 1
   FROM public.brand_audits ba
  WHERE ((ba.id = brand_inconsistencies.brand_audit_id) AND ((ba.client_id IS NULL) OR (ba.client_id = public.get_current_user_client_id()) OR public.is_admin_or_super_admin())))));


--
-- Name: lead_activities view_lead_activities; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY view_lead_activities ON public.lead_activities FOR SELECT USING ((EXISTS ( SELECT 1
   FROM public.leads l
  WHERE ((l.id = lead_activities.lead_id) AND ((l.client_id = public.get_current_user_client_id()) OR public.is_admin_or_super_admin())))));


--
-- Name: maintenance_plan_templates view_maintenance_plan_templates; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY view_maintenance_plan_templates ON public.maintenance_plan_templates FOR SELECT USING (((is_active = true) OR public.is_staff_or_above()));


--
-- Name: meeting_attendees view_meeting_attendees; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY view_meeting_attendees ON public.meeting_attendees FOR SELECT USING ((EXISTS ( SELECT 1
   FROM public.meetings m
  WHERE ((m.id = meeting_attendees.meeting_id) AND ((m.client_id = public.get_current_user_client_id()) OR public.is_admin_or_super_admin())))));


--
-- Name: meeting_notes view_meeting_notes; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY view_meeting_notes ON public.meeting_notes FOR SELECT USING ((EXISTS ( SELECT 1
   FROM public.meetings m
  WHERE ((m.id = meeting_notes.meeting_id) AND ((m.client_id = public.get_current_user_client_id()) OR public.is_admin_or_super_admin())))));


--
-- Name: message_attachments view_message_attachments; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY view_message_attachments ON public.message_attachments FOR SELECT USING (((EXISTS ( SELECT 1
   FROM (public.messages m
     JOIN public.conversation_participants cp ON ((cp.conversation_id = m.conversation_id)))
  WHERE ((m.id = message_attachments.message_id) AND (cp.user_id = auth.uid())))) OR public.is_admin_or_super_admin()));


--
-- Name: ai_conversations view_own_ai_conversations; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY view_own_ai_conversations ON public.ai_conversations FOR SELECT USING (((user_id = auth.uid()) OR public.is_admin_or_super_admin()));


--
-- Name: ai_message_feedback view_own_ai_feedback; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY view_own_ai_feedback ON public.ai_message_feedback FOR SELECT USING (((user_id = auth.uid()) OR public.is_admin_or_super_admin()));


--
-- Name: ai_messages view_own_ai_messages; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY view_own_ai_messages ON public.ai_messages FOR SELECT USING ((EXISTS ( SELECT 1
   FROM public.ai_conversations ac
  WHERE ((ac.id = ai_messages.conversation_id) AND ((ac.user_id = auth.uid()) OR public.is_admin_or_super_admin())))));


--
-- Name: ai_tasks view_own_ai_tasks; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY view_own_ai_tasks ON public.ai_tasks FOR SELECT USING (((user_id = auth.uid()) OR (client_id = public.get_current_user_client_id()) OR public.is_admin_or_super_admin()));


--
-- Name: ai_usage_tracking view_own_ai_usage; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY view_own_ai_usage ON public.ai_usage_tracking FOR SELECT USING (((user_id = auth.uid()) OR (client_id = public.get_current_user_client_id()) OR public.is_admin_or_super_admin()));


--
-- Name: survey_answers view_own_answers; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY view_own_answers ON public.survey_answers FOR SELECT USING ((EXISTS ( SELECT 1
   FROM public.survey_responses sr
  WHERE ((sr.id = survey_answers.response_id) AND ((sr.respondent_id = auth.uid()) OR public.is_admin_or_super_admin())))));


--
-- Name: custom_dashboards view_own_dashboards; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY view_own_dashboards ON public.custom_dashboards FOR SELECT USING (((user_id = auth.uid()) OR (client_id = public.get_current_user_client_id()) OR public.is_admin_or_super_admin()));


--
-- Name: staff_guide_views view_own_guide_views; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY view_own_guide_views ON public.staff_guide_views FOR SELECT USING (((user_id = auth.uid()) OR public.is_admin_or_super_admin()));


--
-- Name: knowledge_base_feedback view_own_kb_feedback; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY view_own_kb_feedback ON public.knowledge_base_feedback FOR SELECT USING (((user_id = auth.uid()) OR public.is_admin_or_super_admin()));


--
-- Name: conversation_participants view_own_participations; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY view_own_participations ON public.conversation_participants FOR SELECT USING (((user_id = auth.uid()) OR public.is_admin_or_super_admin()));


--
-- Name: data_privacy_requests view_own_privacy_requests; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY view_own_privacy_requests ON public.data_privacy_requests FOR SELECT USING (((user_id = auth.uid()) OR (client_id = public.get_current_user_client_id()) OR public.is_admin_or_super_admin()));


--
-- Name: message_reads view_own_reads; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY view_own_reads ON public.message_reads FOR SELECT USING (((user_id = auth.uid()) OR public.is_admin_or_super_admin()));


--
-- Name: survey_responses view_own_responses; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY view_own_responses ON public.survey_responses FOR SELECT USING (((respondent_id = auth.uid()) OR (client_id = public.get_current_user_client_id()) OR public.is_admin_or_super_admin()));


--
-- Name: white_label_configs view_own_white_label; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY view_own_white_label ON public.white_label_configs FOR SELECT USING (((client_id = public.get_current_user_client_id()) OR public.is_admin_or_super_admin()));


--
-- Name: maintenance_plan_usage view_plan_usage; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY view_plan_usage ON public.maintenance_plan_usage FOR SELECT USING ((EXISTS ( SELECT 1
   FROM public.maintenance_plans mp
  WHERE ((mp.id = maintenance_plan_usage.plan_id) AND ((mp.client_id = public.get_current_user_client_id()) OR public.is_admin_or_super_admin())))));


--
-- Name: prompt_templates view_prompt_templates; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY view_prompt_templates ON public.prompt_templates FOR SELECT USING ((auth.uid() IS NOT NULL));


--
-- Name: social_posts view_social_posts; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY view_social_posts ON public.social_posts FOR SELECT USING ((EXISTS ( SELECT 1
   FROM public.social_accounts sa
  WHERE ((sa.id = social_posts.account_id) AND ((sa.client_id = public.get_current_user_client_id()) OR public.is_admin_or_super_admin())))));


--
-- Name: storage_files view_storage_files; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY view_storage_files ON public.storage_files FOR SELECT USING ((EXISTS ( SELECT 1
   FROM public.storage_connections sc
  WHERE ((sc.id = storage_files.connection_id) AND ((sc.client_id = public.get_current_user_client_id()) OR public.is_admin_or_super_admin())))));


--
-- Name: survey_questions view_survey_questions; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY view_survey_questions ON public.survey_questions FOR SELECT USING ((EXISTS ( SELECT 1
   FROM public.surveys s
  WHERE ((s.id = survey_questions.survey_id) AND ((s.client_id = public.get_current_user_client_id()) OR public.is_admin_or_super_admin())))));


--
-- Name: webhook_deliveries view_webhook_deliveries; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY view_webhook_deliveries ON public.webhook_deliveries FOR SELECT USING ((EXISTS ( SELECT 1
   FROM public.webhook_endpoints we
  WHERE ((we.id = webhook_deliveries.endpoint_id) AND ((we.client_id = public.get_current_user_client_id()) OR public.is_admin_or_super_admin())))));


--
-- Name: webhook_deliveries; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.webhook_deliveries ENABLE ROW LEVEL SECURITY;

--
-- Name: webhook_endpoints; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.webhook_endpoints ENABLE ROW LEVEL SECURITY;

--
-- Name: white_label_configs; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.white_label_configs ENABLE ROW LEVEL SECURITY;

--
-- PostgreSQL database dump complete
--


