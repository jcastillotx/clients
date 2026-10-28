#!/usr/bin/env python3
"""Operator-only setup. CI cannot alter IAM, the SSM document, or the host deployer."""
import argparse
import base64
import hashlib
import importlib.util
import json
from pathlib import Path
import subprocess
import time

REPO = "jcastillotx/clients"
ACCOUNT = "556348027172"
REGION = "us-west-2"
INSTANCE = "i-03631eb1405b01b8e"
BUCKET = "kre8ivtech-builds-556348027172-us-west-2"
DOCUMENT = "Kre8ivClientsRelease"
ROLE = "clients-github-release"


def ssm_document(deployer_digest):
    patterns = {"releaseId": "^[a-f0-9]{40}-[0-9]+-[0-9]+$", "commitSha": "^[a-f0-9]{40}$",
                "archiveSha256": "^[a-f0-9]{64}$", "downloadUrl": "^https://[a-zA-Z0-9./?&=%_+:-]+$"}
    return {"schemaVersion": "2.2", "description": "Release the clients portal using the installed, pinned deployer",
            "parameters": {key: {"type": "String", "interpolationType": "ENV_VAR", "allowedPattern": value}
                           for key, value in patterns.items()},
            "mainSteps": [{"action": "aws:runShellScript", "name": "releaseClients",
                           "precondition": {"StringEquals": ["platformType", "Linux"]},
                           "inputs": {"timeoutSeconds": "900", "runCommand": [
                               "set -eu",
                               "echo '" + deployer_digest + "  /opt/clients-openship/release/deploy.py' | sha256sum -c - >/dev/null",
                               '/usr/bin/python3 /opt/clients-openship/release/deploy.py --release-id "$SSM_releaseId" --commit "$SSM_commitSha" --digest "$SSM_archiveSha256" --download-url "$SSM_downloadUrl"',
                           ]}}]}


def release_policy():
    return {"Version": "2012-10-17", "Statement": [
        {"Effect": "Allow", "Action": ["s3:PutObject", "s3:GetObject", "s3:DeleteObject", "s3:AbortMultipartUpload"],
         "Resource": f"arn:aws:s3:::{BUCKET}/clients/releases/*"},
        {"Effect": "Allow", "Action": "s3:GetBucketLocation", "Resource": f"arn:aws:s3:::{BUCKET}"},
        {"Effect": "Allow", "Action": "ssm:SendCommand", "Resource": [
            f"arn:aws:ssm:{REGION}:{ACCOUNT}:document/{DOCUMENT}",
            f"arn:aws:ec2:{REGION}:{ACCOUNT}:instance/{INSTANCE}"]},
        # GetCommandInvocation has no resource-level IAM support.
        {"Effect": "Allow", "Action": "ssm:GetCommandInvocation", "Resource": "*"},
    ]}


def main(profile):
    def aws(*args, optional=False):
        result = subprocess.run(["aws", *args, "--profile", profile, "--region", REGION, "--output", "json"],
                                capture_output=True, text=True)
        if result.returncode:
            if optional and any(code in result.stderr for code in [
                "NoSuchEntity", "NoSuchLifecycleConfiguration", "InvalidDocument", "InvocationDoesNotExist",
            ]):
                return None
            raise RuntimeError("AWS setup failed: " + result.stderr)
        return json.loads(result.stdout) if result.stdout.strip() else {}

    def run_ssm(commands):
        r = aws("ssm", "send-command", "--instance-ids", INSTANCE, "--document-name", "AWS-RunShellScript",
                "--parameters", json.dumps({"commands": commands}))
        command_id = r["Command"]["CommandId"]
        for _ in range(60):
            r = aws("ssm", "get-command-invocation", "--command-id", command_id, "--instance-id", INSTANCE, optional=True)
            if r and r["Status"] not in ["Pending", "InProgress", "Delayed"]:
                if r["Status"] != "Success":
                    raise RuntimeError("Host setup failed: " + r.get("StandardErrorContent", ""))
                return r["StandardOutputContent"]
            time.sleep(2)
        raise RuntimeError("Host setup still pending; inspect SSM before retrying")

    assert aws("sts", "get-caller-identity")["Account"] == ACCOUNT
    deployer = Path(__file__).with_name("deploy.py").read_bytes()
    digest = hashlib.sha256(deployer).hexdigest()
    encoded = base64.b64encode(deployer).decode()
    install = """import base64,os
from pathlib import Path
os.umask(0o077)
p=Path('/opt/clients-openship/release');p.mkdir(parents=True,mode=0o700,exist_ok=True)
f=p/'deploy.py';f.write_bytes(base64.b64decode('%s'));f.chmod(0o700)
""" % encoded
    run_ssm(["python3 - <<'PY'\n" + install + "PY"])
    content = json.dumps(ssm_document(digest))
    current = aws("ssm", "get-document", "--name", DOCUMENT, "--document-version", "$LATEST", optional=True)
    if current is None:
        result = aws("ssm", "create-document", "--name", DOCUMENT, "--document-type", "Command",
                     "--document-format", "JSON", "--content", content)
        version = result["DocumentDescription"]["DocumentVersion"]
    elif json.loads(current["Content"]) != json.loads(content):
        result = aws("ssm", "update-document", "--name", DOCUMENT, "--document-version", "$LATEST",
                     "--document-format", "JSON", "--content", content)
        version = result["DocumentDescription"]["DocumentVersion"]
    else:
        version = current["DocumentVersion"]
    document = aws("ssm", "describe-document", "--name", DOCUMENT, "--document-version", version)["Document"]
    provider = f"arn:aws:iam::{ACCOUNT}:oidc-provider/token.actions.githubusercontent.com"
    if aws("iam", "get-open-id-connect-provider", "--open-id-connect-provider-arn", provider, optional=True) is None:
        aws("iam", "create-open-id-connect-provider", "--url", "https://token.actions.githubusercontent.com",
            "--client-id-list", "sts.amazonaws.com")
    trust = {"Version": "2012-10-17", "Statement": [{"Effect": "Allow", "Principal": {"Federated": provider},
             "Action": "sts:AssumeRoleWithWebIdentity", "Condition": {"StringEquals": {
                 "token.actions.githubusercontent.com:aud": "sts.amazonaws.com",
                 "token.actions.githubusercontent.com:sub": "repo:" + REPO + ":ref:refs/heads/main"}}}]}
    role = aws("iam", "get-role", "--role-name", ROLE, optional=True)
    if role is None:
        role = aws("iam", "create-role", "--role-name", ROLE, "--assume-role-policy-document", json.dumps(trust),
                   "--description", "Main-branch clients release via restricted SSM document", "--max-session-duration", "3600")
    else:
        aws("iam", "update-assume-role-policy", "--role-name", ROLE, "--policy-document", json.dumps(trust))
    aws("iam", "put-role-policy", "--role-name", ROLE, "--policy-name", "ClientsReleaseOnly",
        "--policy-document", json.dumps(release_policy()))
    lifecycle = aws("s3api", "get-bucket-lifecycle-configuration", "--bucket", BUCKET, optional=True) or {"Rules": []}
    lifecycle["Rules"] = [rule for rule in lifecycle["Rules"] if rule["ID"] != "clients-release-transfer-cleanup"]
    lifecycle["Rules"].append({"ID": "clients-release-transfer-cleanup", "Status": "Enabled",
                              "Filter": {"Prefix": "clients/releases/"}, "Expiration": {"Days": 3},
                              "AbortIncompleteMultipartUpload": {"DaysAfterInitiation": 1}})
    extra = []
    if lifecycle.get("TransitionDefaultMinimumObjectSize"):
        extra = ["--transition-default-minimum-object-size", lifecycle["TransitionDefaultMinimumObjectSize"]]
    aws("s3api", "put-bucket-lifecycle-configuration", "--bucket", BUCKET,
        "--lifecycle-configuration", json.dumps({"Rules": lifecycle["Rules"]}), *extra)
    spec = importlib.util.spec_from_file_location("deployer", Path(__file__).with_name("deploy.py"))
    module = importlib.util.module_from_spec(spec)
    spec.loader.exec_module(module)
    # Only the explicit public build allowlist can be exported to repository variables.
    public_script = """import json,subprocess
v=json.loads(subprocess.check_output(['docker','inspect','%s']))[0]
allowed=%s
print(json.dumps({k:value for k,value in (e.split('=',1) for e in v['Config']['Env']) if k in allowed}))
""" % (module.ACTIVE, repr(sorted(module.PUBLIC_KEYS)))
    public = json.loads(run_ssm(["python3 - <<'PY'\n" + public_script + "PY"]))
    variables = {"CLIENTS_PUBLIC_BUILD_ARGS_JSON": json.dumps(public), "CLIENTS_RELEASE_ROLE_ARN": role["Role"]["Arn"],
                 "CLIENTS_RELEASE_DOCUMENT_HASH": document["Hash"], "CLIENTS_RELEASE_DOCUMENT_VERSION": version}
    for key, value in variables.items():
        subprocess.run(["gh", "variable", "set", key, "--repo", REPO, "--body", value], check=True)
    print(json.dumps({"role": role["Role"]["Arn"], "document": DOCUMENT, "documentVersion": version,
                      "deployerSha256": digest, "publicKeysConfigured": sorted(public)}))


if __name__ == "__main__":
    parser = argparse.ArgumentParser()
    parser.add_argument("--profile", required=True)
    main(parser.parse_args().profile)
