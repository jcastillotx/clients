# Authorization hardening and isolated staging — October 27, 2026

## Repairs

Authorization now uses persisted database roles/permissions and account flags. Editable authentication metadata no longer grants access in API routes, pages, or profile synchronization. The auth-user trigger creates an unprivileged, unassigned profile; only an authorized server write may assign tenant membership or administrator rights.

Database triggers prevent client changes to identity, tenant, privilege, active/deleted state, and permission/security fields. Restrictive RLS policies require an active, non-deleted account across public application tables. Middleware rejects unavailable accounts, preserves cookie forwarding, and fails closed when an admin MFA assurance check fails.

User-management permissions do not implicitly grant role assignment. Account managers may provision clients but cannot assign management roles or change administrator credentials. Staff-only task/time/metrics endpoints require persisted staff access. Maintenance subscriptions, conversations, proposal feedback/cancellation, document parents, and support comments enforce tenant or staff scope. Internal support notes are hidden through both API filtering and restrictive RLS. Avatar proxy requests cannot read arbitrary private storage keys.

## Functional repairs

- Added missing proposal feedback/cancellation, payment persistence, invoice-reminder, task-template, folder, and document-folder schema fields/tables.
- Client proposal acceptance writes only the authorized proposal and permitted status transition, using an authorized server operation.
- Payment intent writes fail visibly instead of returning success after a failed database update. Payment webhooks validate account ownership, amount, currency, and persisted intent ID; concurrent duplicate success events settle once.
- Removed arbitrary cross-client Stripe credential fallback. Shared platform billing must use explicit operator-owned IDs in `PLATFORM_STRIPE_CLIENT_IDS` or platform environment credentials. The only saved production Stripe settings observed before deployment belonged to The Candy Bomber Foundation; those settings must never implicitly fund another client's invoice flow.
- Document preview, protected-link copying, and version-upload controls are connected. File versions preserve old records and latest flags. Avatar upload/download uses the shared signing implementation.
- Invoice delivery generates PDFs, captures attachments, and updates send/reminder state. Background reminder reads now report schema failures rather than silently treating them as empty results. Folder background jobs now have their required table and tenant policies.

## Isolated local environment

See `staging/README.md`. It uses separate Docker volumes, a schema-only baseline, synthetic tenants/accounts, rotated signing/API/database credentials, localhost bindings, local SMTP capture, S3/Stripe simulators, and an Inngest dev server. No production records or provider credentials are copied. The entire staging directory is excluded from application image contexts; secrets and captured mail are also ignored by Git.

## Verification

The persisted staging matrix currently contains 48 passing checks. It covers positive flows and attack cases: role/tenant metadata forgery, protected profile writes, inactive/deleted users, staff boundaries, account-manager role limits, private object traversal, internal-note leakage, requests/tickets/projects, document bytes/versions, messaging, client provisioning, proposal/contract signatures, subscriptions, simulated payment settlement/replay, captured mail/PDFs, password recovery, MFA, background folders and reminders.

The user explicitly selected **complete local simulator testing first**. These checks do not claim real Stripe settlement, bank movement, production email recipient delivery, or Google/Microsoft/marketing provider OAuth acceptance. Dedicated provider sandbox acceptance remains a separate gate. Browser checks cover the client login/navigation and populated desktop pages; no claim of complete device/accessibility certification is made.

Generated evidence: `staging/artifacts/workflows.json`, staging screenshots, and the production release report. Private fixture accounts remain in ignored `staging/.accounts.json`; do not copy them into reports.

## Production release

Application source `5f6ac2a` was deployed on October 27, 2026 as `clients:security-5f6ac2a`. The private candidate and public production endpoint each passed 20 authenticated API checks. A temporary synthetic client signed in successfully and rendered its dashboard and the maintenance-plan catalog; privileged navigation was absent despite forged administrator metadata. The temporary production account and company were removed afterward.

Migrations 030–037 were applied after a protected full database backup. All 125 public application tables have RLS enabled. Public signup remains disabled; email login remains enabled; TOTP enrollment and verification are enabled. The production container is healthy with zero restarts. The previous container is retained as `clients-before-security-20261027`, with protected container/configuration and database snapshots on the host.

Validation: 318 tests in 58 files passed; TypeScript passed; ESLint exited successfully with existing repository warnings; the Linux amd64 production image built successfully. See `simulator-results.json`, `security-release.json`, `security-live-client.png`, and `staging-client-documents.png`.

The platform Stripe environment and explicit shared-account allowlist are currently unconfigured. Client-owned saved Stripe credentials continue to apply only to their own client. Other clients need their own Stripe configuration or a deliberately configured operator-owned platform account before real payment acceptance can be completed. This release and the simulator acceptance do not constitute completion of the external-provider acceptance gate.
