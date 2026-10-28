# Self-hosted jobs readiness — October 28, 2026

## Verified locally

The portal's actual installed Inngest SDK ran against Inngest v1.49.0 in production signing mode, with external PostgreSQL 17 and Redis 7 append-only persistence. This is distinct from the previous development-server tests. Local synthetic credentials and databases were used; mail went only to local capture and AI output came only from the simulator.

- Six scheduled/database workflow checks passed: concurrent recurring generation, atomic rollback under injected item failure, recurring invoice delivery, contract reminder windows, expiration/renewal persistence and repeat SLA execution without duplicate notices.
- Nine signed-event/recovery checks passed: invalid signatures/event-key rejection, client-created folders, backfill, invoice reminders, campaign/calendar agents, completed-task redelivery, quality check, provider-failure retry, and recovery of queued work plus existing run history after restarting Inngest/Postgres/Redis.
- A quiesced checkpoint was restored into fresh PostgreSQL and Redis containers with networking disabled. Seven registered functions, historical runs and Redis state were readable. Temporary restore containers and synthetic application fixtures were removed.
- The duplicate-delivery test reproduced a completed marketing task creating another draft. The executor now preserves completed results, and the worker serializes delivery per task ID. The retest passed.
- Type-check and lint passed (existing warnings); 471 unit tests across 85 files passed. Added regression coverage preserves tenant validation on completed-task redelivery and refuses live credentials/external endpoints in local signed acceptance.

Structured evidence: `selfhost-background-workflows.json`, `selfhost-job-events.json`, and `selfhost-restore.json`.

## Production installation and activation

Installed on OpenShip on October 28, 2026 using application source `8412f182650fc6a7b405e418878c9247f3bb92f4`, after merging the current portal changes. The 80 GiB root and 250 GiB data drive resolved the earlier storage blocker. Three private persistent job services are registered in OpenShip. Host-generated keys remain private; managed settings use OpenShip’s authenticated configuration encryption. Provisioning reruns preserve the managed ownership-label override.

Eight candidate checks and eight live-portal checks passed before schedule activation: seven registered functions, invalid-key rejection, four signed disabled-schedule guards, a persisted synthetic tenant-folder event with duplicate event delivery, and engine restart/history preservation. The production checkpoint restored seven functions and run history plus Redis state into isolated containers. Temporary restore containers and application fixtures were removed. Encrypted off-host backup retrieval/checksum verification passed, and a daily backup timer is active.

Schedules were enabled only after those checks. All four scheduled handlers then completed against the live portal with zero work due. The five-minute support SLA schedule also fired automatically and persisted a successful zero-work result. The public health endpoint reports the exact deployed source. This validates job operation; test invoice/contract emails used isolated capture, not real client recipients.

The host monitor covers the healthy job containers. A separate existing alert remains for the main website’s retired `kre8iv-main-candidate`: its owner verification script changed, so the monitor refuses its old cleanup proof. This installation did not weaken that verification or remove the unrelated expectation. The Clients acceptance candidate’s expectation was retired manually by its owning release operator after public release verification.

See `selfhost-production-acceptance.json` for structured evidence and `ops/OPENSHIP-STORAGE.md` for the data layout. Google/Meta review and unpublished plan prices remain separate gates.

## Prepared deployment

`ops/inngest/compose.yml` defines the persistent, resource-bounded private stack. `provision.py` generates independent host-only keys without displaying them and preserves them on rerun. It starts infrastructure only; portal connectivity and schedules remain separate. `backup.py` exports job stores with checksums and restricted permissions. See `ops/inngest/README.md` for connection, OpenShip service registration, candidate acceptance, backup/monitoring, rollback and conditional activation steps.

The live portal uses the private Inngest endpoint with schedules enabled after acceptance. Event and signing keys are configured on the host and in encrypted managed settings. The pending provider integration branch remains separate and subject to Google/Meta eligibility checks.
