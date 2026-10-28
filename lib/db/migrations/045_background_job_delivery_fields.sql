-- Durable notification markers for scheduled jobs. Additive, safe to rerun.
ALTER TABLE public.contracts
  ADD COLUMN IF NOT EXISTS notified_expiring_30_at timestamptz,
  ADD COLUMN IF NOT EXISTS notified_expiring_7_at timestamptz,
  ADD COLUMN IF NOT EXISTS renewed_at timestamptz,
  ADD COLUMN IF NOT EXISTS renewed_count integer NOT NULL DEFAULT 0;
ALTER TABLE public.support_tickets
  ADD COLUMN IF NOT EXISTS sla_response_warned_at timestamptz,
  ADD COLUMN IF NOT EXISTS sla_resolution_warned_at timestamptz;
