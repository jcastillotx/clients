#!/usr/bin/env python3
"""Exercise the actual production image without production private credentials."""
import json
import os
import subprocess
import time
from urllib.request import urlopen

if __name__ == "__main__":
    config = json.loads(os.environ["PUBLIC_BUILD_ARGS_JSON"])
    config.update({"SUPABASE_SERVICE_KEY": "release-smoke-placeholder",
                   "DATABASE_URL": "postgresql://postgres:postgres@127.0.0.1:5432/postgres"})
    args = ["docker", "run", "--rm", "-d", "--name", "clients-release-smoke",
            "-p", "127.0.0.1:3001:3000"]
    for key, value in config.items():
        args += ["-e", key + "=" + value]
    args += ["clients-release:" + os.environ["GITHUB_SHA"]]
    subprocess.run(args, check=True)
    try:
        for attempt in range(30):
            try:
                with urlopen("http://127.0.0.1:3001", timeout=5) as response:
                    if response.status == 200:
                        break
            except Exception:
                pass
            time.sleep(2)
        else:
            raise SystemExit("Image did not start for browser smoke tests")
        env = {**os.environ, "CI": "true", "PLAYWRIGHT_SKIP_WEBSERVER": "1",
               "PLAYWRIGHT_BASE_URL": "http://127.0.0.1:3001"}
        subprocess.run(["pnpm", "exec", "playwright", "test"], env=env, check=True)
    finally:
        subprocess.run(["docker", "rm", "-f", "clients-release-smoke"], check=True)
