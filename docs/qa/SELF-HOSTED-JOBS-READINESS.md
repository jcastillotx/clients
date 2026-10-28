# Self-hosted jobs readiness — October 28, 2026

## Verified locally

The portal's actual installed Inngest SDK ran against Inngest v1.49.0 in production signing mode, with external PostgreSQL 17 and Redis 7 append-only persistence. This is distinct from the previous development-server tests. Local synthetic credentials and databases were used; mail went only to local capture and AI output came only from the simulator.

- Six scheduled/database workflow checks passed: concurrent recurring generation, atomic rollback under injected item failure, recurring invoice delivery, contract reminder windows, expiration/renewal persistence and repeat SLA execution without duplicate notices.
- Nine signed-event/recovery checks passed: invalid signatures/event-key rejection, client-created folders, backfill, invoice reminders, campaign/calendar agents, completed-task redelivery, quality check, provider-failure retry, and recovery of queued work plus existing run history after restarting Inngest/Postgres/Redis.
- A quiesced checkpoint was restored into fresh PostgreSQL and Redis containers with networking disabled. Seven registered functions, historical runs and Redis state were readable. Temporary restore containers and synthetic application fixtures were removed.
- The duplicate-delivery test reproduced a completed marketing task creating another draft. The executor now preserves completed results, and the worker serializes delivery per task ID. The retest passed.
- Type-check and lint passed (existing warnings); 464 unit tests across 83 files passed. Added regression coverage preserves tenant validation on completed-task redelivery and refuses live credentials/external endpoints in local signed acceptance.

Structured evidence: `selfhost-background-workflows.json`, `selfhost-job-events.json`, and `selfhost-restore.json`.

## Production gate

Production installation has not been performed. The OpenShip host has adequate memory but its existing 100 GiB root volume is essentially full. Approximately 334 MiB of redundant generated deployment downloads were removed after verifying that their Docker images remained available. Rollback images, containers and platform/database backups were preserved. The remaining headroom is insufficient for a durable service installation.

Approval to expand the existing root volume to 150 GiB is pending because it changes recurring storage charges. The intended filesystem operation preserves the existing root data and does not change provisioned IOPS/throughput. Do not pull or start new services on the full root volume. The provisioner enforces a 3 GiB minimum free-space guard.

## Prepared deployment

`ops/inngest/compose.yml` defines the persistent, resource-bounded private stack. `provision.py` generates independent host-only keys without displaying them and preserves them on rerun. It starts infrastructure only; portal connectivity and schedules remain separate. `backup.py` exports job stores with checksums and restricted permissions. See `ops/inngest/README.md` for connection, OpenShip service registration, candidate acceptance, backup/monitoring, rollback and conditional activation steps.

The live portal remains on its existing release with `BACKGROUND_SCHEDULES_ENABLED=false` and no Inngest endpoint configured. No production reminder, recurring invoice or contract renewal was enabled by these local tests. The pending provider integration branch is separate and must remain subject to Google/Meta eligibility checks.
