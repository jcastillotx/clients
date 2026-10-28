#!/usr/bin/env python3
"""Root-owned, fixed-purpose host release command. No application secrets leave the host."""

import argparse
import fcntl
import hashlib
import json
import os
from pathlib import Path
import re
import signal
import subprocess
import time
from urllib.parse import urlparse
from urllib.request import Request, urlopen

ROOT = Path("/opt/clients-openship/releases")
ACTIVE = "openship-kre8ivdesigns-clients-clients-designs-candidate"
PUBLIC = "https://clients.kre8ivdesigns.com"
VHOST = Path("/var/lib/openship/edge/sites-enabled/clients-kre8ivdesigns-com.conf")
ROUTE = VHOST.with_suffix(".route.json")
BUCKET = "kre8ivtech-builds-556348027172-us-west-2"
PUBLIC_KEYS = {
    "NEXT_PUBLIC_SUPABASE_URL", "NEXT_PUBLIC_SUPABASE_ANON_KEY",
    "NEXT_PUBLIC_SUPABASE_PUBLISHABLE_KEY", "NEXT_PUBLIC_APP_URL",
    "NEXT_PUBLIC_SITE_URL", "NEXT_PUBLIC_APP_NAME",
    "NEXT_PUBLIC_STRIPE_PUBLISHABLE_KEY", "NEXT_PUBLIC_TURNSTILE_SITE_KEY",
}


class ReleaseError(Exception):
    pass


def validate_release(release_id, commit, digest, url):
    if not re.fullmatch(r"[0-9a-f]{40}", commit):
        raise ReleaseError("Invalid commit")
    if not re.fullmatch(re.escape(commit) + r"-[0-9]+-[0-9]+", release_id):
        raise ReleaseError("Invalid release ID")
    if not re.fullmatch(r"[0-9a-f]{64}", digest):
        raise ReleaseError("Invalid artifact digest")
    parsed = urlparse(url)
    hosts = {f"{BUCKET}.s3.amazonaws.com", f"{BUCKET}.s3.us-west-2.amazonaws.com"}
    if (parsed.scheme != "https" or parsed.netloc not in hosts
            or parsed.path != f"/clients/releases/{release_id}/image.tar.gz"
            or parsed.fragment):
        raise ReleaseError("Artifact URL is outside the release prefix")


def docker(*args, optional=False):
    result = subprocess.run(["docker", *args], capture_output=True, text=True, timeout=180)
    if result.returncode:
        if optional:
            return None
        # Docker arguments contain only paths/public metadata; never echo container logs or env.
        raise ReleaseError(f"Docker {args[0]} failed")
    return result.stdout.strip()


def inspect(name):
    value = docker("inspect", name, optional=True)
    return json.loads(value)[0] if value else None


def fetch(url):
    # An unexpected redirect must not count as a successful route probe.
    with urlopen(Request(url, headers={"Cache-Control": "no-cache"}), timeout=15) as response:
        if response.status != 200 or response.geturl() != url:
            raise ReleaseError("Route did not return HTTP 200 directly")
        return response.read()


def verify(base, commit=None):
    health = json.loads(fetch(base + "/api/health"))["data"]
    if health.get("status") != "operational" or health.get("services", {}).get("database") != "ok":
        raise ReleaseError("Database health check failed")
    if commit and health.get("release") != commit:
        raise ReleaseError("Health endpoint reports a different release")
    for path in ["/service-request", "/request-project"]:
        html = fetch(base + path).decode()
        if "Request a New Project" not in html:
            raise ReleaseError("Public request form is missing")
    assets = list(dict.fromkeys(re.findall(r'(?:src|href)="(/_next/static/[^"?]+)', html)))
    if not assets:
        raise ReleaseError("Request form assets are missing")
    for path in assets[:3]:
        fetch(base + path)
    return health


def wait_ready(base, commit=None, attempts=24):
    for attempt in range(attempts):
        try:
            return verify(base, commit)
        except Exception:
            if attempt == attempts - 1:
                raise ReleaseError("Release readiness failed") from None
            time.sleep(2)


def environment(container):
    return dict(item.split("=", 1) for item in container["Config"]["Env"])


def run_container(name, image, port, previous, env_file):
    host = previous["HostConfig"]
    networks = list(previous["NetworkSettings"]["Networks"])
    if len(networks) != 1 or previous["Mounts"]:
        raise ReleaseError("Unexpected network or mounts require an operator review")
    # Refuse to silently drop material security/runtime settings introduced by another release.
    if host.get("Privileged") or host.get("CapAdd") or host.get("Devices"):
        raise ReleaseError("Unexpected privileged runtime settings")
    args = ["run", "-d", "--name", name, "--network", networks[0],
            "-p", f"127.0.0.1:{port}:3000", "--env-file", str(env_file),
            "--memory", str(host["Memory"]), "--memory-swap", str(host["MemorySwap"]),
            "--cpus", str(host["NanoCpus"] / 1_000_000_000),
            "--restart", host["RestartPolicy"]["Name"]]
    if host.get("SecurityOpt"):
        for value in host["SecurityOpt"]:
            args += ["--security-opt", value]
    if host.get("ReadonlyRootfs"):
        args += ["--read-only"]
    args += [image]
    return docker(*args)


def restore_previous(previous, rollback_name, commit):
    current = inspect(ACTIVE)
    if current and current["Id"] != previous["Id"]:
        if current["Config"].get("Labels", {}).get("org.opencontainers.image.revision") != commit:
            raise ReleaseError("Unexpected active container; operator recovery required")
        docker("rm", "-f", ACTIVE)
        current = None
    if not current:
        rollback = inspect(rollback_name)
        if not rollback or rollback["Id"] != previous["Id"]:
            raise ReleaseError("Rollback container missing; operator recovery required")
        docker("rename", rollback_name, ACTIVE)
    docker("start", ACTIVE)
    wait_ready("http://127.0.0.1:20004")
    wait_ready(PUBLIC)


def promote(previous, candidate, image, env_file, commit, release_id):
    rollback = "clients-rollback-" + release_id
    current = inspect(ACTIVE)
    if not current or current["Id"] != previous["Id"]:
        raise ReleaseError("Active container changed during candidate verification")
    docker("rm", "-f", candidate)
    try:
        docker("stop", ACTIVE)
        docker("rename", ACTIVE, rollback)
        run_container(ACTIVE, image, 20004, previous, env_file)
        wait_ready("http://127.0.0.1:20004", commit)
        # Public checks run inside the rollback boundary, not as an unrelated later job.
        wait_ready(PUBLIC, commit, attempts=6)
    except BaseException:
        restore_previous(previous, rollback, commit)
        raise
    return rollback


def deploy(args):
    os.umask(0o077)
    validate_release(args.release_id, args.commit, args.digest, args.download_url)
    ROOT.mkdir(mode=0o700, parents=True, exist_ok=True)
    with (ROOT / ".lock").open("w") as lock:
        try:
            fcntl.flock(lock, fcntl.LOCK_EX | fcntl.LOCK_NB)
        except BlockingIOError:
            raise ReleaseError("Another release is in progress") from None
        folder = ROOT / args.release_id
        if folder.exists():
            raise ReleaseError("Release ID already used; increment the workflow attempt")
        folder.mkdir(mode=0o700)
        image_file = folder / "image.tar.gz"
        candidate = "clients-candidate-" + args.release_id
        try:
            previous = inspect(ACTIVE)
            if not previous or not previous["State"]["Running"]:
                raise ReleaseError("Active container is not running")
            expected_route = f"http://127.0.0.1:20004"
            route = ROUTE.read_bytes()
            vhost = VHOST.read_bytes()
            if json.loads(route)["targetUrl"] != expected_route:
                raise ReleaseError("Saved upstream changed; operator review required")
            if vhost.count(f"proxy_pass {expected_route};".encode()) != 2:
                raise ReleaseError("Live vhost upstream changed; operator review required")
            bindings = previous["HostConfig"]["PortBindings"].get("3000/tcp")
            if bindings != [{"HostIp": "127.0.0.1", "HostPort": "20004"}]:
                raise ReleaseError("Active port binding changed")
            (folder / "previous-inspect.json").write_text(json.dumps(previous))
            try:
                with urlopen(args.download_url, timeout=60) as response, image_file.open("wb") as out:
                    while chunk := response.read(1024 * 1024):
                        out.write(chunk)
            except Exception:
                raise ReleaseError("Artifact download failed") from None
            with image_file.open("rb") as artifact:
                actual = hashlib.file_digest(artifact, "sha256").hexdigest()
            if actual != args.digest:
                raise ReleaseError("Artifact checksum mismatch")
            docker("load", "-i", str(image_file))
            image = "clients-release:" + args.commit
            image_info = json.loads(docker("image", "inspect", image))[0]
            if image_info["Config"].get("Labels", {}).get("org.opencontainers.image.revision") != args.commit:
                raise ReleaseError("Image revision label mismatch")
            if image_info["Architecture"] != "amd64":
                raise ReleaseError("Image must be Linux amd64")
            image_env = json.loads(docker("run", "--rm", "--network", "none", "--entrypoint",
                                          "cat", image, "/app/.release-public.json"))
            live_env = environment(previous)
            expected_public = {key: live_env.get(key, "") for key in PUBLIC_KEYS}
            expected_public["NEXT_PUBLIC_APP_NAME"] = live_env.get("NEXT_PUBLIC_APP_NAME", "Kre8iv Clients")
            if any(image_env.get(key, "") != expected_public[key] for key in PUBLIC_KEYS):
                raise ReleaseError("Public build configuration differs from live runtime")
            live_env["BUILD_COMMIT"] = args.commit
            if any("\n" in value or "\r" in value for value in live_env.values()):
                raise ReleaseError("Multiline runtime values require an operator review")
            env_file = folder / "runtime.env"
            env_file.write_text("\n".join(f"{k}={v}" for k, v in live_env.items()) + "\n")
            run_container(candidate, image, 20014, previous, env_file)
            wait_ready("http://127.0.0.1:20014", args.commit)
            if ROUTE.read_bytes() != route or VHOST.read_bytes() != vhost:
                raise ReleaseError("Upstream configuration changed during release")
            rollback = promote(previous, candidate, image, env_file, args.commit, args.release_id)
            receipt = {"status": "released", "commit": args.commit, "releaseId": args.release_id,
                       "imageId": image_info["Id"], "rollbackContainer": rollback,
                       "publicUrl": PUBLIC, "checks": ["private-health", "private-forms",
                       "live-health", "public-release-identity", "public-forms"]}
            (folder / "receipt.json").write_text(json.dumps(receipt, indent=2) + "\n")
            print(json.dumps(receipt))
        finally:
            if inspect(candidate):
                docker("rm", "-f", candidate)
            image_file.unlink(missing_ok=True)


def interrupted(_signal, _frame):
    raise ReleaseError("Release interrupted")


if __name__ == "__main__":
    parser = argparse.ArgumentParser()
    parser.add_argument("--release-id", required=True)
    parser.add_argument("--commit", required=True)
    parser.add_argument("--digest", required=True)
    parser.add_argument("--download-url", required=True)
    signal.signal(signal.SIGTERM, interrupted)
    try:
        deploy(parser.parse_args())
    except Exception as error:
        # Do not expose an artifact URL, subprocess stderr, or private runtime config in SSM logs.
        print(json.dumps({"status": "failed", "reason": str(error) if isinstance(error, ReleaseError)
                          else type(error).__name__}))
        raise SystemExit(1)
