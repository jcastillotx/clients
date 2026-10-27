-- Plans are configured by administrators; no unapproved commercial tiers are seeded.
CREATE TABLE IF NOT EXISTS marketing_plans (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  name text NOT NULL CHECK (length(trim(name)) BETWEEN 1 AND 100),
  tool_groups jsonb NOT NULL DEFAULT '[]'::jsonb CHECK (jsonb_typeof(tool_groups)='array'),
  is_active boolean NOT NULL DEFAULT true,
  created_at timestamptz NOT NULL DEFAULT now(),
  updated_at timestamptz NOT NULL DEFAULT now()
);
CREATE TABLE IF NOT EXISTS client_marketing_plans (
  client_id uuid PRIMARY KEY REFERENCES clients(id),
  plan_id uuid NOT NULL REFERENCES marketing_plans(id),
  status text NOT NULL DEFAULT 'active' CHECK (status IN ('active','paused','cancelled')),
  starts_at timestamptz NOT NULL DEFAULT now(),
  ends_at timestamptz,
  updated_at timestamptz NOT NULL DEFAULT now(),
  CHECK (ends_at IS NULL OR ends_at > starts_at)
);
ALTER TABLE marketing_plans ENABLE ROW LEVEL SECURITY;
ALTER TABLE client_marketing_plans ENABLE ROW LEVEL SECURITY;
-- Management uses authenticated, administrator-authorized server routes only.
-- The direct database role must never be exposed to the browser.
ALTER TABLE marketing_plans ADD COLUMN IF NOT EXISTS monthly_price_cents integer NOT NULL DEFAULT 0 CHECK (monthly_price_cents >= 0);
ALTER TABLE marketing_plans ADD COLUMN IF NOT EXISTS currency text NOT NULL DEFAULT 'usd';
ALTER TABLE marketing_plans ADD COLUMN IF NOT EXISTS published boolean NOT NULL DEFAULT false;
ALTER TABLE marketing_plans ADD COLUMN IF NOT EXISTS slug text UNIQUE;
ALTER TABLE client_marketing_plans ADD COLUMN IF NOT EXISTS stripe_subscription_id text UNIQUE;
ALTER TABLE client_marketing_plans ADD COLUMN IF NOT EXISTS stripe_customer_id text;
ALTER TABLE client_marketing_plans ADD COLUMN IF NOT EXISTS cancel_at_period_end boolean NOT NULL DEFAULT false;
CREATE TABLE IF NOT EXISTS marketing_checkout_sessions (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  client_id uuid NOT NULL REFERENCES clients(id),
  plan_id uuid NOT NULL REFERENCES marketing_plans(id),
  stripe_session_id text UNIQUE,
  stripe_subscription_id text UNIQUE,
  price_cents integer NOT NULL,
  currency text NOT NULL,
  created_at timestamptz NOT NULL DEFAULT now()
);
ALTER TABLE marketing_checkout_sessions ENABLE ROW LEVEL SECURITY;
INSERT INTO marketing_plans(slug,name,monthly_price_cents,tool_groups,published) VALUES
 ('essentials','Marketing Essentials',4900,'["content","strategy","email"]',false),
 ('growth','Marketing Growth',14900,'["content","strategy","email","seo","conversion","analytics","reporting"]',false),
 ('complete','Marketing Complete',29900,'["paid-media","seo","content","conversion","strategy","email","analytics","creative","reporting"]',false)
ON CONFLICT (slug) DO NOTHING;
