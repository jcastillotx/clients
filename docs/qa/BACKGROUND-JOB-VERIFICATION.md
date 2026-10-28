# Background job readiness — October 27, 2026

## Repairs

- Unattended jobs use the existing system service-role database client; none depends on browser cookies. The Inngest SDK still verifies production requests using the signing key.
- SLA checks use the actual support-ticket deadlines, pause duration, response state and separate response/resolution breach markers. Staff warnings start at the same 75% threshold as the UI. Successful notices are recorded and subsequent runs do not repeat them. Unassigned warnings are left pending.
- Contract notices persist delivery markers added by migration 045. The urgent window does not also receive the 30-day notice. Renewal validates a positive duration and resets notification markers for the new term.
- Scheduled query, template and delivery errors fail the run rather than silently reporting success. Delivery markers are written after provider success. SMTP is not an exactly-once transport: a process failure after delivery but before recording it can still cause a retry to resend.
- Recurring invoice creation locks the template and atomically commits the invoice, line items and next schedule. A stable ID for each template/period makes retry and concurrent execution converge on one invoice. Generated invoices remain drafts until email succeeds. Existing sent/paid states are not sent again on replay. Monthly/quarterly/yearly date arithmetic clamps to the target month's last valid day.
- `BACKGROUND_SCHEDULES_ENABLED=true` is required to activate the four scheduled jobs. Default is paused. Client folder setup and explicitly requested marketing-agent events are independent of this flag.

## Isolated acceptance

Six checks passed against the local Inngest server, dedicated Supabase database and local mail capture:

1. Eight concurrent generation attempts produced one invoice and one item.
2. An injected line-item insertion failure rolled back the invoice and schedule advance.
3. Inngest generated a recurring invoice, delivered its PDF through local mail capture and recorded it as sent.
4. Contract reminder windows saved their respective markers without browser authentication.
5. Expiration and auto-renewal persisted valid statuses/dates and reset term markers.
6. SLA warnings/breaches persisted on support tickets; a subsequent scheduled invocation did not send duplicates.

Fixtures were removed afterward. Migration 045 was applied only to isolated staging during these checks. The full unit suite passed 447 tests across 81 files. Type-check passed and lint passed with existing warnings. Regression tests exercise disabled schedules, missing credentials, database failures, failed invoice/contract delivery, support warnings and month boundaries.

## Production activation gate

The running production container had neither `INNGEST_EVENT_KEY` nor `INNGEST_SIGNING_KEY` at inspection. The provider dashboard was signed out. Owner sign-in is pending. No production scheduled notices, invoice generation, contract status changes or renewals were triggered by this test run. A read-only workload preview found zero currently due reminders, recurring templates, expiring/expired/renewable active contracts and active support tickets. This is a point-in-time count, not approval to activate future messages.

Before activation: configure the event/signing keys privately, apply migration 045, deploy the tested release, sync the production endpoint with Inngest, verify a synthetic event and inspect the production scheduled workload. Owner approval is required before the first run sends actual client/staff messages or renews agreements. Do not enable the local dev server on production or use simulator credentials.

Reproduction: bundle `lib/invoices/generate-recurring.ts` with the existing esbuild into `staging/artifacts/recurring-helper.cjs`, then run `node staging/scripts/background-workflows.mjs` against the protected local staging fixtures. The script rejects non-local app/auth/database addresses and requires local SMTP capture.
