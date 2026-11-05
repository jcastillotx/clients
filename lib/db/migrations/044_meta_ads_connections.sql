BEGIN;
ALTER TABLE marketing_connections DROP CONSTRAINT marketing_connections_provider_check;
ALTER TABLE marketing_connections ADD CONSTRAINT marketing_connections_provider_check
  CHECK (provider IN ('google_analytics', 'google_ads', 'google_search_console', 'meta_ads'));
ALTER TABLE marketing_oauth_states DROP CONSTRAINT marketing_oauth_states_provider_check;
ALTER TABLE marketing_oauth_states ADD CONSTRAINT marketing_oauth_states_provider_check
  CHECK (provider IN ('google_analytics', 'google_ads', 'google_search_console', 'meta_ads'));
COMMIT;
