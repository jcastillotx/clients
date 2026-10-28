#!/usr/bin/env python3
"""Upload one checked image and invoke the restricted, pinned SSM document."""
import hashlib
import json
import os
from pathlib import Path
import subprocess
import time


def command(*args):
    return subprocess.check_output(args, text=True).strip()


def aws(*args):
    return command("aws", *args, "--region", "us-west-2")


def require_installed_deployer(expected):
    actual = hashlib.sha256(Path(__file__).with_name("deploy.py").read_bytes()).hexdigest()
    if actual != expected:
        raise SystemExit("Host deployer differs from source; run operator setup before releasing")


if __name__ == "__main__":
    require_installed_deployer(os.environ["RELEASE_DEPLOYER_SHA256"])
    commit = os.environ["GITHUB_SHA"]
    release_id = commit + "-" + os.environ["GITHUB_RUN_ID"] + "-" + os.environ["GITHUB_RUN_ATTEMPT"]
    # Do not deploy a checked but superseded main revision.
    main = command("gh", "api", "repos/jcastillotx/clients/commits/main", "--jq", ".sha")
    if main != commit:
        raise SystemExit("main advanced during the build; rerun the release from current main")
    image = Path("release/image.tar.gz")
    with image.open("rb") as source:
        digest = hashlib.file_digest(source, "sha256").hexdigest()
    bucket = "kre8ivtech-builds-556348027172-us-west-2"
    key = f"s3://{bucket}/clients/releases/{release_id}/image.tar.gz"
    aws("s3", "cp", str(image), key, "--only-show-errors")
    try:
        url = aws("s3", "presign", key, "--expires-in", "1800")
        print("::add-mask::" + url, flush=True)
        params = {"releaseId": [release_id], "commitSha": [commit],
                  "archiveSha256": [digest], "downloadUrl": [url]}
        request = {"DocumentName": "Kre8ivClientsRelease", "DocumentVersion": os.environ["RELEASE_DOCUMENT_VERSION"],
                   "DocumentHash": os.environ["RELEASE_DOCUMENT_HASH"], "DocumentHashType": "Sha256",
                   "InstanceIds": ["i-03631eb1405b01b8e"], "Parameters": params,
                   "TimeoutSeconds": 600}
        Path("release/command.json").write_text(json.dumps(request))
        result = json.loads(aws("ssm", "send-command", "--cli-input-json", "file://release/command.json"))
        command_id = result["Command"]["CommandId"]
        print("Release command: " + command_id, flush=True)
        deadline = time.monotonic() + 1200
        while time.monotonic() < deadline:
            # SSM is eventually consistent immediately after SendCommand.
            try:
                invocation = json.loads(aws("ssm", "get-command-invocation", "--command-id", command_id,
                                             "--instance-id", "i-03631eb1405b01b8e"))
            except subprocess.CalledProcessError:
                time.sleep(5)
                continue
            if invocation["Status"] in ["Pending", "InProgress", "Delayed", "Cancelling"]:
                time.sleep(5)
                continue
            receipt = {"commandId": command_id, "commit": commit, "releaseId": release_id,
                       "status": invocation["Status"], "output": invocation.get("StandardOutputContent", "")}
            Path("release/receipt.json").write_text(json.dumps(receipt, indent=2) + "\n")
            print(json.dumps(receipt), flush=True)
            if invocation["Status"] != "Success":
                raise SystemExit("Release failed; inspect the receipt for rollback status")
            with open(os.environ["GITHUB_STEP_SUMMARY"], "a") as summary:
                summary.write(f"Released `{commit}` to https://clients.kre8ivdesigns.com.\n\n")
                summary.write(f"SSM command: `{command_id}`. Private, public, and release identity checks passed.\n")
            break
        else:
            # Do not delete an in-flight artifact; SSM remains authoritative after runner failure.
            raise SystemExit("Release command still running; inspect SSM before retrying")
    finally:
        # Each artifact has a distinct run/attempt key; the host retains its rollback image.
        # A runner timeout can leave the object behind; a bucket lifecycle rule provides eventual cleanup.
        if Path("release/receipt.json").exists():
            aws("s3", "rm", key, "--only-show-errors")
        Path("release/command.json").unlink(missing_ok=True)
