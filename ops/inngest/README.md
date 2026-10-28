# Self-hosted client background jobs

Pinned engine: `inngest/inngest:v1.49.0`, tested with the portal's installed Inngest 3.x SDK in signed production mode. PostgreSQL 17 stores registry/history; Redis 7 with append-only persistence stores queues/run state. Separate volumes preserve both through container replacement. PostgreSQL and Redis have no published ports and use an internal Docker network. Inngest additionally joins the portal network as `clients-jobs`; its host port is bound only to `127.0.0.1:20035`. No public dashboard or new cloud account is required.

The deployment uses memory/CPU bounds, rotating container logs, health checks, restart policies and ten executor workers. Its configured maxima total 1,408 MiB and two CPUs. No Connect gateway port is published. Never substitute `inngest dev` or disable signature verification on the production portal.

## Provisioning and activation

1. Confirm disk headroom before any image pull. At initial inspection the 100 GiB root volume was full. Redundant generated image downloads were removed only where their Docker images were retained; rollback containers/images and backups were preserved. Root-volume expansion approval is pending. `provision.py` refuses to proceed with less than 3 GiB free.
2. Copy this directory to a protected directory on the OpenShip host and run `python3 provision.py`. It generates private per-installation keys and database credentials without printing them, preserves existing keys on rerun, and starts only the three infrastructure services.
3. Register the three actual containers as project services in OpenShip, with matching project/service ownership labels, private networking, health monitoring and persistent volumes. Preserve the current portal release and its resource limits. Keep the provisioning `.env`, `portal-env.json`, backups and logs permission-restricted.
4. Use a candidate portal with the tested application source and the generated `portal-env.json`; preserve all unrelated runtime settings. Keep `BACKGROUND_SCHEDULES_ENABLED=false`. Verify signed sync, seven registered functions, invalid-signature/key rejection, a synthetic client-folder event, quality-check execution and restart recovery. Do not invoke global financial/mail schedules against real clients as a test.
5. Promote only after CI, candidate tests, backup/recovery checks and public health checks pass. Update managed OpenShip environment settings and active deployment/container references. Repeat the production workload preview before enabling the schedules under the user's conditional authorization: all tests must pass first. Preserve rollback material.

The local acceptance stack deliberately uses unique `deadbeef`-prefixed synthetic keys and port 8488. Isolated staging refuses those keys unless the explicit local acceptance configuration is enabled. They must never be deployed. Production keys are independent, generated on the host and never copied into the isolated fixtures.

## Backups and recovery

`backup.py` exports only the job database and Redis snapshot, with checksums and private file permissions. It does not access the portal/customer database. Online snapshots capture the stores independently and can replay in-flight work. `--quiesce` stops/restarts only Inngest for a consistent two-store maintenance checkpoint; PostgreSQL and Redis remain running. Take a checkpoint before activation and retain a verified off-host copy. Configure recurring backups/monitoring on the host before treating the installation as operational; do not alter existing backup retention or delete old platform backups to make room.

A synthetic quiesced checkpoint was restored into fresh, network-isolated PostgreSQL/Redis containers. Registry, seven functions, run history and Redis state were readable; restore containers were removed. This proves the tested checkpoint format, not a production disaster-recovery drill. On restore, keep event intake and schedules paused, verify checksums/version, restore into fresh stores, reconnect the app, verify retained runs and controlled replay, and only then resume work.

## Reproduce local acceptance

Start the dedicated Compose project `clients-selfhost-local` using the protected `staging/artifacts/selfhost.env`, loopback port 8488 and a separate portal network. Run the portal with `STAGING_ISOLATED=1`, `STAGING_SELF_HOSTED_JOBS=1`, `INNGEST_DEV=0` and the local synthetic signing/event keys. The guard rejects external endpoints and live credentials. Start the local marketing AI simulator; no provider data or real messages are used.

Bundle `lib/invoices/generate-recurring.ts` to `staging/artifacts/recurring-helper.cjs` with the installed esbuild, then run `node staging/scripts/background-workflows.mjs` and `node staging/scripts/selfhosted-job-events.mjs`. The latter temporarily stops/restarts only its own local app and job stack to verify durable recovery. Use `backup.py --project clients-selfhost-local --destination staging/artifacts/selfhost-backup --quiesce`, then `python3 staging/scripts/selfhost-restore.py` for the isolated restore check.
