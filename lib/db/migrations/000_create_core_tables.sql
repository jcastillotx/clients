-- Migration: Create Core Tables (Clients and Users)
-- Description: Creates the fundamental tables that other migrations depend on
-- Created: 2026-02-15
-- Order: MUST RUN FIRST (before 001)

-- Enable UUID extension
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";
CREATE EXTENSION IF NOT EXISTS "pgcrypto";

-- Create clients table
CREATE TABLE IF NOT EXISTS public.clients (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  company_name TEXT NOT NULL,
  email TEXT NOT NULL,
  phone TEXT,
  website TEXT,
  address TEXT,
  city TEXT,
  state TEXT,
  zip_code TEXT,
  country TEXT DEFAULT 'US',
  status TEXT NOT NULL DEFAULT 'active' CHECK (status IN ('active', 'inactive', 'pending', 'suspended')),
  enabled_features JSONB,
  google_search_console_data JSONB,
  marketing_strategy JSONB,
  created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  deleted_at TIMESTAMPTZ
);

-- Create users table
-- IMPORTANT: id references auth.users(id) to keep auth and profile in sync
CREATE TABLE IF NOT EXISTS public.users (
  id UUID PRIMARY KEY REFERENCES auth.users(id) ON DELETE CASCADE,
  name TEXT NOT NULL,
  email TEXT NOT NULL UNIQUE,
  phone TEXT,
  avatar TEXT,
  client_id UUID REFERENCES public.clients(id),
  is_active BOOLEAN NOT NULL DEFAULT true,
  is_super_admin BOOLEAN NOT NULL DEFAULT false,
  status TEXT NOT NULL DEFAULT 'active' CHECK (status IN ('active', 'inactive', 'suspended')),
  last_login_at TIMESTAMPTZ,
  manual_permissions JSONB,
  security_settings JSONB,
  created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  deleted_at TIMESTAMPTZ
);

-- Create indexes
CREATE INDEX IF NOT EXISTS idx_clients_status ON public.clients(status) WHERE deleted_at IS NULL;
CREATE INDEX IF NOT EXISTS idx_clients_company_name ON public.clients(company_name) WHERE deleted_at IS NULL;
CREATE INDEX IF NOT EXISTS idx_clients_email ON public.clients(email) WHERE deleted_at IS NULL;

CREATE INDEX IF NOT EXISTS idx_users_email ON public.users(email) WHERE deleted_at IS NULL;
CREATE INDEX IF NOT EXISTS idx_users_client_id ON public.users(client_id) WHERE deleted_at IS NULL;
CREATE INDEX IF NOT EXISTS idx_users_status ON public.users(status) WHERE deleted_at IS NULL;

-- Enable Row Level Security
ALTER TABLE public.clients ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.users ENABLE ROW LEVEL SECURITY;

-- Simple RLS Policies (no RBAC dependencies - those are added in migration 001.5)
-- These allow basic access for initial setup

-- Clients: Users can view their own client
DROP POLICY IF EXISTS "Users can view their own client" ON public.clients;
CREATE POLICY "Users can view their own client" ON public.clients
  FOR SELECT
  USING (
    id IN (SELECT client_id FROM public.users WHERE id = auth.uid())
  );

-- Clients: Allow all operations for now (will be restricted in 001.5)
DROP POLICY IF EXISTS "Users can manage their own client" ON public.clients;
CREATE POLICY "Users can manage their own client" ON public.clients
  FOR ALL
  USING (
    id IN (SELECT client_id FROM public.users WHERE id = auth.uid())
  );

-- Users: Can view their own record
DROP POLICY IF EXISTS "Users can view themselves" ON public.users;
CREATE POLICY "Users can view themselves" ON public.users
  FOR SELECT
  USING (id = auth.uid());

-- Users: Can view users from same client (non-recursive)
DROP POLICY IF EXISTS "Users can view same client" ON public.users;
CREATE POLICY "Users can view same client" ON public.users
  FOR SELECT
  USING (
    client_id = (
      SELECT u.client_id FROM public.users u WHERE u.id = auth.uid() LIMIT 1
    )
  );

-- Users: Allow updates to own record
DROP POLICY IF EXISTS "Users can update themselves" ON public.users;
CREATE POLICY "Users can update themselves" ON public.users
  FOR UPDATE
  USING (id = auth.uid());

-- Create update trigger function
CREATE OR REPLACE FUNCTION update_core_tables_updated_at()
RETURNS TRIGGER AS $$
BEGIN
  NEW.updated_at = NOW();
  RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- Add triggers
DROP TRIGGER IF EXISTS trigger_clients_updated_at ON public.clients;
CREATE TRIGGER trigger_clients_updated_at
  BEFORE UPDATE ON public.clients
  FOR EACH ROW
  EXECUTE FUNCTION update_core_tables_updated_at();

DROP TRIGGER IF EXISTS trigger_users_updated_at ON public.users;
CREATE TRIGGER trigger_users_updated_at
  BEFORE UPDATE ON public.users
  FOR EACH ROW
  EXECUTE FUNCTION update_core_tables_updated_at();

-- Grant permissions
GRANT ALL ON public.clients TO authenticated;
GRANT ALL ON public.users TO authenticated;
GRANT ALL ON public.clients TO service_role;
GRANT ALL ON public.users TO service_role;

-- Create function to handle new user signup (auto-create public.users profile)
CREATE OR REPLACE FUNCTION public.handle_new_user()
RETURNS TRIGGER AS $$
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
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- Create trigger on auth.users to auto-create public.users profile
DROP TRIGGER IF EXISTS on_auth_user_created ON auth.users;
CREATE TRIGGER on_auth_user_created
  AFTER INSERT ON auth.users
  FOR EACH ROW
  EXECUTE FUNCTION public.handle_new_user();

-- Add comments for documentation
COMMENT ON TABLE public.clients IS 'Client companies in the multi-tenant system';
COMMENT ON TABLE public.users IS 'User accounts linked to clients - synced with auth.users';
COMMENT ON COLUMN public.users.id IS 'References auth.users(id) - kept in sync via trigger';
COMMENT ON COLUMN public.users.client_id IS 'Links user to their client company (NULL for super admins)';
COMMENT ON COLUMN public.users.is_super_admin IS 'Super admins can access all clients';
COMMENT ON FUNCTION public.handle_new_user() IS 'Trigger function to auto-create public.users profile when auth.users record is created';
