# Search Console and Meta Ads launch work

Required by owner: Search Console and Meta Ads. Yelp and LinkedIn Ads remain optional.

## Search Console

Local implementation uses the existing per-user/per-client encrypted Google OAuth connection and PKCE state handling. Scope: webmasters.readonly. Plan group: seo (draft Growth and Complete). Resource selection is checked against the authenticated provider property list; unverified properties are excluded. Report requests go only to the fixed Google API origin with encoded property IDs. A single aggregate row reports finalized web search for seven Pacific calendar days ending three days before today. Missing rows remain empty, never fabricated zeros. Attaching a report to an SEO brief requires an explicit user action; sending it to AI requires Create draft.

Migration 043 expands the two existing provider CHECK constraints without changing RLS. Apply before exposing the new connection.

Search Console API is enabled in the Clients project and webmasters.readonly is declared in OAuth Data Access. Owner approved read-only consent for the synthetic alpha client. All 9 real Search Console provider checks passed, including actual report, encrypted storage, refresh, resource membership, cross-client isolation and plan expiry. No data was sent to AI. Release remains pending. Local unit and regression tests do not prove live authorization or data access.

## Meta Ads

Dedicated app created in Meta under Kre8ivdesigns Marketing, LLC as KRE8IV Client Portal, Marketing API use case. Existing other-product apps will not be reused. Owner approved provider terms and completed password verification; app ID 1788859082319285 was created successfully. Owner completed the separate password confirmation and the existing app credential is stored only in protected local acceptance configuration. Business portfolio currently shows unverified. The local Meta connector is implemented with ads_read, per-user/tenant encrypted tokens, single-use hashed OAuth state, fixed callback, bounded cursor pagination at a fixed Graph origin, appsecret_proof, exact resource membership, and paid-media plan checks. User token expiry requires reconnect; there is no agency/system-token fallback. Owner approved and created Client Ads Reporting configuration 1176846418615628 with only ads_read, then approved the actual reporting test grant. Meta accepts localhost in development; the literal 127.0.0.1 callback was rejected by HTTPS enforcement. Strict redirects and HTTPS enforcement remain enabled. Production callback is HTTPS. The approved Jeremiah Castillo account passed all 9 live provider checks, including actual account discovery, account-specific reporting, expired token rejection, forged resource rejection, cross-client isolation and plan gating. The actual last-seven-days response contained no rows, which rendered as an empty report; this does not prove populated-campaign metrics. No report data was sent to AI. The Meta app is unpublished and the connector is not deployed. Meta public-client review/Tech Provider and business verification remain unresolved.

## Optional providers

LinkedIn Advertising API requires approval: https://learn.microsoft.com/en-gb/linkedin/shared/authentication/getting-access
Yelp reporting requires partner access and separate Reporting API enablement: https://docs.developer.yelp.com/docs/reporting-api
Neither is a launch requirement at this time. Confirm client demand and existing partner access before implementing.

## Validation and cleanup

424 unit tests across 78 files passed, including OAuth state/user/tenant/plan binding and scope rejection tests. Type-check passed and lint completed with existing warnings. Real Search Console and Meta provider acceptance each passed 9 checks. Search Console temporary connection was removed after testing. Meta temporary connection was removed after testing; local database verification found zero remaining Search Console or Meta connections. Dedicated Meta credentials are held in protected server configuration for a future release, separate from the running production environment. Production remains unchanged pending release and provider eligibility review.

## Deployment baseline after background-job repairs

PR #239 is deployed as `clients:background-70b0e91`; migration 045 is applied. The four scheduled jobs remain paused with `BACKGROUND_SCHEDULES_ENABLED=false` pending owner Inngest sign-in, keys, production acceptance and explicit schedule activation. Preserve this setting during provider deployment. The production OpenShip active release is `dep_clients_background_70b0e91`; off-host promotions must also record the actual new container/image/source in OpenShip's deployment and service-deployment records and update its active-release pointer, retaining prior releases and rollback containers. Verify the dashboard resolves the running container rather than a stopped historical one.

The current container also carries `openship.project=proj_AritosaAhSrWyqXb`, `openship.service=clients-designs-candidate`, and `openship.deployment=dep_clients_background_70b0e91`. Preserve project/service ownership labels and set the deployment label to the new recorded release on future replacement containers. Without ownership labels, runtime discovery can prefer a stopped historical container even when public traffic is healthy. After promotion, verify OpenShip shows Running and its active deployment/service records reference the actual container ID.
