# Clients OpenShip migration — October 2, 2026

Status: deployed on self-hosted OpenShip; public DNS and HTTPS cutover verified.

Public app: https://clients.kre8ivdesigns.com/

OpenShip project: https://app.kre8ivhosting.io/projects/proj_AritosaAhSrWyqXb

## Release and infrastructure

| Item | Value |
| --- | --- |
| Source repository | `jcastillotx/clients` |
| Verified Vercel production commit | `84dd390f5eeebcc94bc1e2ec2313c591193d76cd` |
| Retained Vercel deployment | `dpl_4K3752ewt7UncPETFHafQHhzV38e` |
| Vercel fallback URL | `https://clients-8mk0pnmv1-kre8ivdesigns.vercel.app/` |
| OpenShip project | `proj_AritosaAhSrWyqXb` / `kre8ivdesigns-clients` |
| AWS account / CLI profile | `556348027172` / `kre8iv-whm` |
| EC2 / region | `i-03631eb1405b01b8e` / `us-west-2` |
| Origin IP | `44.238.21.249` |
| OpenShip version | `0.8.0`, self-hosted |
| Container | `clients-designs-candidate` |
| Image tag | `clients:vercel-84dd390` |
| Image ID | `sha256:df8b0890000a79c616f6da639f77dfa0067e7b74bd7d7c16194982c922a8ccfa` |
| Binding | `127.0.0.1:20004` → container `3000` |
| Limits | 768 MiB RAM, no container swap, one CPU, 512 MiB Node heap |
| Restart policy | `unless-stopped` |

The candidate uses the verified production commit plus hosting configuration:
Next.js standalone output, the existing `vercel.json` response headers applied
through Next.js, and a container build. Linux compilation used Node 22 and pnpm
10.30.3, with two build workers and a 3 GiB memory limit. Existing application
behavior and unrelated uncommitted local changes were excluded.

The destination Docker CLI initially had no BuildKit/buildx plugin. For this initial
release, a resource-limited Node build container mounted the production settings
read-only, then a separate runtime image was assembled from the standalone
output, static assets, and public files. No `.env` files or private-secret
matches were found in the assembled application files. The checked-in Dockerfile
declares only public build arguments; private credentials are supplied to the running service.
Docker buildx 0.30.1 was installed from Ubuntu packages for native source builds.

OpenShip's native same-server migration completed using `attach-live`, importing
only this app and preserving its running container and limits. The custom domain
is service-scoped on port 3000. The dashboard shows Verified, SSL Active, and
Edge ready. The project is now linked to `jcastillotx/clients`, production branch
`main`, with Auto Deploy enabled. The service builds `Dockerfile` from repository
root instead of pulling the original fixed image. `compose.yaml` declares the
existing service name and port so OpenShip can refresh its source definition
without dropping its saved environment or domain.

Public `NEXT_PUBLIC_*` settings are saved in the project build environment.
Existing private credentials remain in service runtime overrides, and `.env*`
files are excluded from the build context. Deployment readiness waits for
`/api/health`, watches for restart loops, and keeps the previous version serving
if readiness fails. Build workers are limited to two and the build Node heap to
2 GiB. To release, push reviewed changes to `main`; OpenShip receives the GitHub
push and builds the new image automatically.

## Data and production settings

Supabase database, auth, storage, encryption key, Microsoft email configuration,
and application URLs remain the existing production settings. No database
migration or production data modification was performed.

Runtime environment: `/opt/clients-openship/runtime.env` on the destination,
root-owned, mode `0600`, inside a mode `0700` directory. OpenShip also imports the
container environment into its protected service configuration. The public app
and site URL both remain `https://clients.kre8ivdesigns.com`.

Temporary local production exports and private S3 transfer objects were removed
after validation. No secrets were committed or recorded in these documents.

## DNS and TLS

The clients hostname changed from a Vercel CNAME to an A record pointing to
`44.238.21.249`. DNS-only proxy status and the 600-second TTL were preserved.
The Cloudflare zone is `96743c2e5a88c48e679d506ddc42a1c7` in account
`7a389a75dc360c00f9cb1480d24b63e5`.

The initial Let's Encrypt certificate was provisioned with DNS validation while
public traffic remained on Vercel. It is valid through December 31, 2026. The
certificate and key are at `/etc/letsencrypt/live/clients.kre8ivdesigns.com/`.

After cutover, Certbot successfully simulated renewal and saved standalone
HTTP-01 renewal settings on port 49180, reached through the existing OpenShip
edge challenge proxy. The dedicated `clients-openship-cert-renew.timer` is
active and enabled, checking daily with a randomized delay. Its service invokes
Certbot for this hostname only; its deploy hook validates and reloads OpenResty.
The service completed successfully. The initial ACME TXT record remains; future
renewals use HTTP validation.

No unrelated mail, verification, or other hostname records were changed.

## Verification

- Authoritative Cloudflare DNS, resolver `1.1.1.1`, and resolver `8.8.8.8` all
  return the destination IP.
- Public HTTPS requests independently connect to `44.238.21.249`.
- `/`, `/login`, `/request-project`, and `/pay-invoice`: HTTP 200.
- Sampled CSS and JavaScript assets: HTTP 200.
- `/api/health`: HTTP 200, operational, database and auth marked OK. This endpoint
  verifies database connectivity; it is not an authenticated login test.
- Unauthenticated `/dashboard` and `/admin`: HTTP 307 to `/`.
- Unauthenticated `/api/clients` and `/api/inngest`: HTTP 401, matching the
  expected protected-route behavior for this release.
- Existing security headers are present, including CSP, HSTS, frame and
  content-type protections.
- Chrome loads the public sign-in form through the existing hostname.
- Docker health is healthy, with zero restarts.
- OpenResty configuration validation passed before and after route changes.
- TypeScript check passed. Full lint completed with pre-existing warnings;
  the changed Next.js config passes its targeted lint check.
- Unit suite: 199 passed, one failed, across 48 test files. The unchanged baseline
  failure is `tests/api/webhooks.route.test.ts`, “GET returns 400 when clientId
  is missing”: expected 400, received 500 because the mocked database client is
  undefined. No application changes were made to address that existing issue.
- Both the local production build and Linux candidate build passed.

Evidence: `openship-origin-checks.json`, `openship-public-checks.json`, and
`openship-clients-domain.png` in this directory.

Authenticated client workflows, email delivery, paid transactions, Microsoft
OAuth completion, and background-job execution were not exercised. Existing
production settings were preserved, and no new job registration was performed.

## Rollback

Vercel remains available. Restore the clients DNS record to:

- Type: `CNAME`.
- Name: `clients`.
- Content: `1941e66bb86e2d1c.vercel-dns-017.com`.
- Proxy: DNS only.
- TTL: 600 seconds.

The old record ID was `6e6678f0660c06b3d8dbcce47b825595`; changing record type in
Cloudflare replaces the record, so resolve its current ID before API edits.
The Vercel custom-domain association and verification TXT were retained.

Because both hosts use the same existing Supabase backend, this hosting-only
rollback does not require a database restore. Vercel was not deleted or disabled;
retirement is a separate follow-up decision. Native OpenShip push deployment is
configured as described above.
