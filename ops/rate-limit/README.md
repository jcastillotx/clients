# Private OpenShip rate limiting

The portal uses one authenticated counter service shared by all application instances. SQLite WAL transactions on the attached site-data drive enforce atomic fixed windows and retain counters across process/container restarts. Client identifiers are SHA-256 hashed with limiter namespace and configuration before storage. This is a single-host service; it is not a multi-host replicated database.

`server.py` uses Python's standard library. The pinned official container runs without root, capabilities, a writable root filesystem, or published ports. Only the portal's private Docker network can reach it. Its data directory and bearer token are private to the host/service. Counter expiry is indexed and enforced on every request.

After reviewing/testing these files, copy them to a root-private directory on OpenShip and run `python3 provision.py` as root. Provisioning checks that Docker and `/opt` are on the expected attached drive, preserves an existing token, starts the service, and prepares `/opt/clients-openship/security-env.json` (root-owned 0600). It does not restart the active portal. The official release deployer validates and merges only the three allowlisted shared-limit variables into a candidate before health checks and promotion. Run `scripts/release/bootstrap.py` when changing the pinned deployer; never bypass its checksum gate.

Set `SHARED_RATE_LIMIT_REQUIRED=1` in production. Once configured, timeouts, invalid replies, missing credentials, and service failures deny protected requests; the portal health endpoint returns 503. Local development may leave this unset and use process-local counters. The old Upstash configuration remains compatible for other deployments, but OpenShip needs no hosted service.

Check `docker inspect clients-rate-limit --format '{{.State.Health.Status}}'` and the portal health endpoint after release. Do not print Docker environment values or the security override file. The release keeps the previous portal container for rollback; preserve the counter service and secret file during rollback. Before stopping the service for maintenance, account for protected routes failing closed. Test outages against an isolated service, not production.

Tests: `python3 -m unittest discover -s ops/rate-limit -v`; application tests include hashed keys, namespace separation, malformed responses, required credentials, and fail-closed outages. CI rejects every dependency advisory severity. CSP browser tests confirm response-specific nonces and rejection of an injected untrusted inline script.

Client IP limits rely on OpenShip's trusted ingress: Nginx accepts `CF-Connecting-IP` only from Cloudflare's configured address ranges and overwrites `X-Real-IP` with the resulting peer address. The application prioritizes that sanitized value over a caller-prepended `X-Forwarded-For`. Keep the application port loopback/private and preserve the ingress real-IP configuration.
