BEGIN;
ALTER TABLE public.proposals ADD COLUMN IF NOT EXISTS client_feedback text;
ALTER TABLE public.proposals ADD COLUMN IF NOT EXISTS cancelled_at timestamptz;
ALTER TABLE public.proposals DROP CONSTRAINT IF EXISTS proposals_status_check;
ALTER TABLE public.proposals ADD CONSTRAINT proposals_status_check CHECK
  (status IN ('draft','sent','viewed','accepted','rejected','expired','cancelled'));
NOTIFY pgrst, 'reload schema';
COMMIT;
