#!/usr/bin/env python3
"""Back up Inngest's own stores; never reads the portal/customer database.

Use --quiesce during a maintenance window for a consistent two-store checkpoint.
Online backups capture each store independently and may replay in-flight work.
"""
import argparse
import datetime
import hashlib
import json
import os
from pathlib import Path
import subprocess

parser = argparse.ArgumentParser()
parser.add_argument("--project", choices=["clients-jobs", "clients-selfhost-local"], required=True)
parser.add_argument("--destination", type=Path, required=True)
parser.add_argument("--quiesce", action="store_true")
args = parser.parse_args()
args.destination.mkdir(parents=True, exist_ok=False, mode=0o700)
os.chmod(args.destination, 0o700)
names = {role: f"{args.project}-{role}-1" for role in ["inngest", "postgres", "redis"]}
was_running = subprocess.check_output(["docker", "inspect", "--format", "{{.State.Running}}", names["inngest"]], text=True).strip() == "true"
try:
    if args.quiesce and was_running:
        subprocess.run(["docker", "stop", "--time", "30", names["inngest"]], check=True, capture_output=True)
    with (args.destination / "postgres.dump").open("wb") as output:
        subprocess.run(["docker", "exec", names["postgres"], "pg_dump", "-U", "inngest", "-d", "inngest", "-Fc"], stdout=output, check=True)
    subprocess.run(["docker", "exec", names["redis"], "redis-cli", "SAVE"], check=True, capture_output=True)
    subprocess.run(["docker", "cp", names["redis"] + ":/data/dump.rdb", str(args.destination / "redis.rdb")], check=True, capture_output=True)
    manifest = {"createdAt": datetime.datetime.now(datetime.timezone.utc).isoformat(), "project": args.project, "quiesced": args.quiesce, "sha256": {}}
    for name in ["postgres.dump", "redis.rdb"]:
        path = args.destination / name
        os.chmod(path, 0o600)
        digest = hashlib.sha256()
        with path.open("rb") as source:
            for chunk in iter(lambda: source.read(1024 * 1024), b""):
                digest.update(chunk)
        manifest["sha256"][name] = digest.hexdigest()
    path = args.destination / "manifest.json"
    path.write_text(json.dumps(manifest, indent=2) + "\n")
    os.chmod(path, 0o600)
finally:
    if args.quiesce and was_running:
        subprocess.run(["docker", "start", names["inngest"]], check=True, capture_output=True)
print(json.dumps({"backup": "complete", "quiesced": args.quiesce, "files": 3}))
