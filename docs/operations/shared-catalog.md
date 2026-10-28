# Website and portal synchronization

The portal service templates own published public package scope. Admins manage the public package fields on each service template. Only active services and valid packages marked published are available through GET /api/public/service-catalog. Price amounts, terms, notes and tenant records are excluded by an explicit projection. Unpublishing a package removes it from new website catalog responses (edge cache at most 60 seconds).

The website reads this fixed HTTPS endpoint on its server with a five-second timeout, no redirects, a 128 KiB maximum and response validation. Failures show a clear unavailable state and the general portal request link. No privileged API key, write token or submitted contact data crosses this catalog connection.

Website quote calls to action lead to the portal /service-request form. Only a package identifier appears in the URL. The portal resolves it from published catalog data, prefills the request title, and validates it again before writing. Existing CAPTCHA, intake limits, notifications and tenant boundaries apply. The website legacy POST /api/public/quote returns 410 with the new request URL; historical website records remain available. Other website forms retain their existing workflows.

Initial catalog import: python3 scripts/catalog/seed-public-packages.py (dry run), then --apply. This adds only missing approved package identifiers while preserving existing metadata, edits, prices and tenant records. Production credentials remain in the portal container. The recovery script is separate and is never part of deployment.

## Deployment controls verified 2026-10-28

Both GitHub main branches require pull requests and resolved conversations, block force pushes and deletion, and enforce controls for administrators. Portal merges require quality and e2e checks. Independent reviewer approval count is zero so the sole owner can merge their own checked changes.

Portal CI and release actions are pinned to immutable revisions. CI has read-only permissions and does not retain checkout credentials. The release job uses temporary AWS OIDC credentials restricted to this repository main branch, one release S3 prefix, and the pinned fixed-purpose SSM document/instance. Builds and browser checks complete before promotion; artifact hashes, private readiness, exact public release identity and rollback checks remain mandatory.

The website builds in AWS CodeBuild from the exact main commit with no production secret or deployment permissions. Its build role is limited to logs, source retrieval and its image repository. The source-connection branch restriction was corrected to main. The root-owned watcher requires passing typecheck/tests/build, pinned base images, a completed ECR scan with no HIGH/CRITICAL findings, exact image revision, a private candidate and direct canonical HTTPS readiness before marking a deployment healthy. Failed revisions stop and alert; rollback is retained. Root-owned deployment control cannot be replaced by an application-source push.

This is a scoped pipeline review, not certification that every dependency or integration is secure. Replacement owner MFA enrollment and the protected admin settings audit remain separate checks.
