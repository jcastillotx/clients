CREATE TABLE IF NOT EXISTS marketing_connections (
 id uuid PRIMARY KEY DEFAULT gen_random_uuid(), client_id uuid NOT NULL REFERENCES clients(id),
 user_id uuid NOT NULL REFERENCES users(id), provider text NOT NULL CHECK(provider IN ('google_analytics','google_ads')),
 access_token text NOT NULL, refresh_token text, expires_at timestamptz, resource_id text,
 created_at timestamptz NOT NULL DEFAULT now(), updated_at timestamptz NOT NULL DEFAULT now(),
 UNIQUE(client_id,user_id,provider)
);
CREATE TABLE IF NOT EXISTS marketing_oauth_states (
 state_hash text PRIMARY KEY, user_id uuid NOT NULL REFERENCES users(id),client_id uuid NOT NULL REFERENCES clients(id),
 provider text NOT NULL CHECK(provider IN ('google_analytics','google_ads')), verifier text NOT NULL,
 expires_at timestamptz NOT NULL
);
ALTER TABLE marketing_connections ENABLE ROW LEVEL SECURITY;
ALTER TABLE marketing_oauth_states ENABLE ROW LEVEL SECURITY;
-- Tokens are encrypted by the server, never returned by public APIs.
-- No browser policies: all access uses tenant-authorized server routes.
