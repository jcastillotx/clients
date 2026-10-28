#!/usr/bin/env python3
"""Run on the OpenShip Linux host after storage capacity is approved.

This starts only the job infrastructure. It never modifies the portal container,
connects production handlers, or enables schedules.
"""
import argparse
import json
import os
from pathlib import Path
import secrets
import shutil
import subprocess
import sys

parser = argparse.ArgumentParser()
parser.add_argument("--directory", type=Path, default=Path("/opt/clients-jobs"))
args = parser.parse_args()
if sys.platform != "linux":
    raise SystemExit("Provision only on the OpenShip Linux host")
if shutil.disk_usage("/").free < 3 * 1024**3:
    raise SystemExit("At least 3 GiB free root storage is required before provisioning")
# Refuse to create persistent job state on the OS disk if a mount is missing.
data_root = Path("/srv/kre8iv-data")
expected_uuid = "70b0504e-aa41-42c4-a956-400d19cede3e"
for storage_path in [str(args.directory.parent), "/var/lib/docker", "/var/lib/containerd"]:
    observed_uuid = subprocess.check_output(
        ["findmnt", "-rn", "-o", "UUID", "--target", storage_path], text=True
    ).strip()
    if observed_uuid != expected_uuid:
        raise SystemExit("Job storage must be mounted on the attached site-data drive")
if shutil.disk_usage(data_root).free < 10 * 1024**3:
    raise SystemExit("At least 10 GiB free site-data storage is required before provisioning")
subprocess.run(["docker", "network", "inspect", "openship-kre8ivdesigns-clients"], check=True, capture_output=True)
root = args.directory
root.mkdir(parents=True, exist_ok=True, mode=0o700)
os.chmod(root, 0o700)
source = Path(__file__).resolve().with_name("compose.yml")
if source != root / "compose.yml":
    shutil.copy2(source, root / "compose.yml")
os.chmod(root / "compose.yml", 0o600)
env_file = root / ".env"
if not env_file.exists():
    values = {
        "JOBS_POSTGRES_PASSWORD": secrets.token_hex(32),
        "INNGEST_EVENT_KEY": secrets.token_hex(32),
        "INNGEST_SIGNING_KEY": secrets.token_hex(32),
        "JOBS_SDK_URL": "http://openship-kre8ivdesigns-clients-clients-designs-candidate:3000/api/inngest",
        "JOBS_HOST_PORT": "20035",
        "JOBS_PORTAL_NETWORK": "openship-kre8ivdesigns-clients",
    }
    env_file.write_text("".join(k + "=" + v + "\n" for k, v in values.items()))
os.chmod(env_file, 0o600)
values = dict(line.split("=", 1) for line in env_file.read_text().splitlines() if line and not line.startswith("#"))
portal = {
    "INNGEST_EVENT_KEY": values["INNGEST_EVENT_KEY"],
    "INNGEST_SIGNING_KEY": values["INNGEST_SIGNING_KEY"],
    "INNGEST_BASE_URL": "http://clients-jobs:8288",
    "INNGEST_DEV": "0",
    "BACKGROUND_SCHEDULES_ENABLED": "false",
}
path = root / "portal-env.json"
path.write_text(json.dumps(portal))
os.chmod(path, 0o600)
command = ["docker", "compose", "--project-name", "clients-jobs", "--env-file", str(env_file), "-f", str(root / "compose.yml")]
result = subprocess.run(command + ["up", "-d", "--wait", "--wait-timeout", "120"], capture_output=True, text=True)
log = root / "provision.log"
log.write_text(result.stdout + result.stderr)
os.chmod(log, 0o600)
if result.returncode:
    raise SystemExit("Job infrastructure failed readiness; inspect the protected provision log")
print(json.dumps({"infrastructure": "ready", "portalModified": False, "schedulesEnabled": False, "dashboard": "loopback port 20035"}))
