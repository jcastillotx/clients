# Production release

Run **Release production (off-host)** from GitHub Actions on `main`:

https://github.com/jcastillotx/clients/actions/workflows/deploy.yml

The workflow checks TypeScript, lint, application tests, and release safety tests;
builds Linux amd64 on the GitHub runner; and runs the browser smoke suite against
that image. Only the release job can request short-lived AWS credentials. It
uploads a per-run artifact and invokes `Kre8ivClientsRelease` on the existing host.

The host checks the artifact checksum, image commit and architecture, public build
configuration, current container identity, port binding, and saved/live upstreams.
It copies private runtime configuration locally into a root-only release folder,
tests a candidate on loopback port 20014, then replaces the active container on
loopback port 20004. The previous container is retained stopped for rollback.

Private and public health checks must report the exact source commit. Both public
intake URLs and their static assets must return 200 directly. A readiness or public
check failure during promotion restores the previous container. Source changes
after the build abort the release rather than deploying a superseded main commit.

GitHub retains the JSON release receipt for 90 days. Root-only runtime snapshots,
receipts, and retained rollback containers are under
`/opt/clients-openship/releases/<commit>-<run>-<attempt>/`. Successful transfers are
deleted; abandoned S3 transfers expire after three days. Older stopped rollback
containers are retained for an operator to prune after reviewing disk usage.

## Operator setup or deployer updates

After reviewing and testing the release code, run from the repository:

```sh
python3 scripts/release/bootstrap.py --profile kre8iv-whm
```

This installs the root-owned deployer, creates/updates a checksum-pinned SSM
document, and configures the repository variables. It requires operator AWS access
and `gh` access to repository variables. CI has no permission to modify the
deployer, IAM, SSM documents, DNS, upstreams, or database schema.

The AWS role trusts only GitHub OIDC for `jcastillotx/clients` on `main`, and can
access only the clients artifact prefix and this host/document pair. Repository
variables contain public build settings, role ARN, and pinned document hash/version.
No long-lived AWS keys or private app credentials are stored in GitHub.

Changing `deploy.py` requires rerunning operator setup before releasing it; the
document deliberately fails closed if its installed-code checksum differs.
The workflow never applies database migrations. Backward-compatible schema changes
must be handled separately before releasing code that needs them.

## Recovery

The workflow receipt names the retained rollback container. If an automatic
rollback itself fails, inspect the root-only previous-inspect file and both
containers through SSM. Restore the previous container to the active name and port
20004; do not modify DNS or remove other applications. A runner cancellation does
not cancel an accepted SSM command: inspect its command ID before retrying.

Tests simulate public-health failure after private readiness, and verify rollback,
stale-container rejection, release identity, artifact scope, and document safety:

```sh
python3 -m unittest discover -s tests/release -v
```
