# OpenShip storage — October 28, 2026

The owner requested a separate site-data disk and an 80 GiB OS root. This supersedes the earlier proposed 150 GiB root expansion. The EC2 instance size and public IP are unchanged. The WHM/cPanel server is a separate machine and was not modified.

## Layout

- OpenShip instance: `i-03631eb1405b01b8e`, Oregon, `us-west-2a`.
- OS: encrypted 80 GiB gp3, `vol-0b9a187eb6c156ae1`.
- Site data: encrypted 250 GiB gp3, `vol-09dcc5071eb8b4b1c`, mounted at `/srv/kre8iv-data`, UUID `70b0504e-aa41-42c4-a956-400d19cede3e`.
- Retained rollback root: `vol-038831ff358186e5b`, 100 GiB, detached. Do not delete without an explicit retention decision.

The data filesystem backs `/opt`, `/var/lib/docker`, `/var/lib/containerd`, `/var/lib/openship`, `/var/lib/kre8ivtech`, and `/home/ubuntu/.openship` through persistent bind mounts. Both Docker storage locations are required with the containerd image store. Existing application paths remain valid. New Docker image builds, named volumes, normal OpenShip deployments and application state under these paths use the data disk. Any future service with a custom host path must place persistent data under these paths or directly under `/srv/kre8iv-data`.

Docker, containerd, cron and application service units have `RequiresMountsFor` dependencies. If the data disk is unavailable, restore the mount before starting applications; never remove those dependencies or create replacement data on the OS root. Mounts use UUIDs; do not depend on NVMe device numbering.

## Migration and recovery

All containers and automation were paused for the final data sync, then restarted and checked. A real image build/read-back and a new named-volume write confirmed data-disk backing. The OS was copied to a separate prepared 80 GiB filesystem because EBS cannot shrink a volume in place. The original root was detached before booting the replacement; their filesystem UUIDs match, so do not attach and boot with both roots present.

Private host evidence and recovery state: `/var/tmp/kre8iv-storage-migration`, with a copy under `/srv/kre8iv-data/maintenance/migration-state`. These directories include sensitive container configuration; do not publish their full contents. The controller and its logs are in the operator’s `/tmp/clients-storage-migration` directory.

To roll back the OS, stop the instance, detach the 80 GiB root, attach the retained 100 GiB volume as `/dev/sda1`, then start the instance. Keep the data volume attached. Both OS copies point at the migrated data. Restore the recorded running containers and automation, then verify health. This is an OS rollback, not a database point-in-time restore.

The pre-migration root snapshot is `snap-05ffbfeed603f359d`. Consistent stopped-workload data snapshot: `snap-0ad78f8197eed8a95`. Replacement-root preboot snapshot: `snap-014162bb4c2570d56`. The latter two were requested successfully and were still processing during immediate post-boot checks. Verify completion in AWS before relying on them. Existing daily DLM policy `policy-041d3ef9cbebbe693` targets the project tag present on both new volumes; its seven-snapshot retention was preserved.

CloudFormation manages the instance through `kre8iv-modern-platform` / `PlatformHost`, but its current template contains no explicit instance block-device mappings. The attached volumes were migrated operationally. Any future infrastructure change must preserve these volume IDs, attachments and mount dependencies; review a change set before executing changes that could replace the instance. Do not infer safe replacement from the old AMI defaults.

## Capacity and cost

Initial replacement-root usage was 5.8 GiB out of the 77 GiB filesystem; site-data usage was 88 GiB out of the 246 GiB filesystem. Monitor free space on both filesystems and retain the existing container monitor, backup timers and WAL uploader. AWS volume tags and management-event history identify both volumes. Preserve encryption, private file permissions and existing off-host backups.

The verified Oregon gp3 storage rate was $0.08 per provisioned GB-month: active 80 + 250 GiB volumes total approximately $26.40/month before snapshots and any extra performance charges. The retained 100 GiB rollback volume adds approximately $8/month until removed. Separating drives improves capacity management; it does not lower gp3’s per-GB rate or change EC2 compute charges.
