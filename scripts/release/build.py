#!/usr/bin/env python3
"""Build on the CI runner using an explicit allowlist of public settings."""
import json
import os
import re
import subprocess

KEYS = {
    "NEXT_PUBLIC_SUPABASE_URL", "NEXT_PUBLIC_SUPABASE_ANON_KEY",
    "NEXT_PUBLIC_SUPABASE_PUBLISHABLE_KEY", "NEXT_PUBLIC_APP_URL",
    "NEXT_PUBLIC_SITE_URL", "NEXT_PUBLIC_APP_NAME",
    "NEXT_PUBLIC_STRIPE_PUBLISHABLE_KEY", "NEXT_PUBLIC_TURNSTILE_SITE_KEY",
}

if __name__ == "__main__":
    commit = os.environ["GITHUB_SHA"]
    if not re.fullmatch(r"[0-9a-f]{40}", commit):
        raise SystemExit("Invalid source commit")
    config = json.loads(os.environ["PUBLIC_BUILD_ARGS_JSON"])
    if set(config) - KEYS or not all(isinstance(value, str) for value in config.values()):
        raise SystemExit("Only allowlisted public build settings are accepted")
    if not all(config.get(key) for key in ["NEXT_PUBLIC_SUPABASE_URL", "NEXT_PUBLIC_SUPABASE_ANON_KEY",
                                          "NEXT_PUBLIC_APP_URL", "NEXT_PUBLIC_SITE_URL"]):
        raise SystemExit("Required public settings are missing")
    args = ["docker", "build", "--platform", "linux/amd64", "--label",
            "org.opencontainers.image.revision=" + commit, "-t", "clients-release:" + commit]
    for key, value in config.items():
        args += ["--build-arg", key + "=" + value]
    subprocess.run(args + ["."], check=True)
