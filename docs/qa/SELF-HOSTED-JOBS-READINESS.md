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

Production job installation has not been performed. The previous full-root blocker was addressed on October 28 by moving site data, Docker/containerd storage and OpenShip state to a separate 250 GiB drive, then replacing the 100 GiB OS root with an 80 GiB root. The proposed expansion to 150 GiB was superseded by the owner’s storage-layout instruction. Preserve rollback storage and verify both filesystems before provisioning; see `ops/OPENSHIP-STORAGE.md`.

## Prepared deployment

`ops/inngest/compose.yml` defines the persistent, resource-bounded private stack. `provision.py` generates independent host-only keys without displaying them and preserves them on rerun. It starts infrastructure only; portal connectivity and schedules remain separate. `backup.py` exports job stores with checksums and restricted permissions. See `ops/inngest/README.md` for connection, OpenShip service registration, candidate acceptance, backup/monitoring, rollback and conditional activation steps.

The live portal remains on its existing release with `BACKGROUND_SCHEDULES_ENABLED=false` and no Inngest endpoint configured. No production reminder, recurring invoice or contract renewal was enabled by these local tests. The pending provider integration branch is separate and must remain subject to Google/Meta eligibility checks.
