# Marketing plans and client connections

Initial production release324fb00 is deployed on OpenShip. Prices remain unpublished drafts. Real GA4 and directly accessible Google Ads acceptance passed (nine checks each).

Draft monthly USD software tiers:

| Plan | Price | Tool groups | Tools |
| --- | ---: | --- | ---: |
| Essentials | $49 | Content, strategy/research, email/lifecycle | 17 |
| Growth | $149 | Essentials plus SEO, conversion, analytics, reporting | 36 |
| Complete | $299 | All groups including paid media and creative | 48 |

Agency services, ad spend and external subscriptions are excluded. Owner review is required before publishing these commercial offers. Admins can edit prices and groups. Changes affect new checkout pricing; existing Stripe subscriptions keep their original recurring price. Plan group changes affect active members immediately.

Access is enforced server-side for catalog, methodology, execution and saved history, and the same groups populate client menu links. An active, unexpired assignment is required; feature flags can further restrict access, never bypass the plan. No plans are automatically assigned to real clients. Trialing/active Stripe subscriptions receive access; past_due, unpaid, incomplete, paused or canceled subscriptions do not. Cancellation at period end retains access through the paid period. Stripe billing uses platform credentials only. Signed platform webhooks retrieve canonical subscription state and match a server-owned checkout record, client, subscription, price and currency before granting access. Concurrent pending checkouts are blocked locally.

Google Ads and GA4 connections belong to a user within their client tenant. OAuth uses single-use hashed state with expiry and PKCE; tokens are encrypted and never returned. Account/property selections must be returned by Google's accessible-resource API. Reporting uses fixed aggregate queries, not client-supplied arbitrary API requests. Expired plans block connection use, while disconnect remains available. Disconnect removes locally saved credentials; users can independently revoke the app in Google account permissions.

GA4: analytics.readonly, Admin API discovery, Data API report of sessions, activeUsers and keyEvents for seven complete days. Google Ads: adwords scope (Google does not provide a narrower read-only scope), accessible direct accounts and seven-day aggregate impressions, clicks, cost_micros, conversions/currency. This implementation has no campaign mutation or ad-spend endpoints. Manager-account child discovery is supported with Google CustomerClient resource names and the required login-customer-id header. This hierarchy path has contract tests; the available live account was a direct advertiser, so real manager-hierarchy acceptance remains unverified.

Cloud setup: Analytics Admin API, Analytics Data API and Google Ads API enabled with owner approval. Google Ads Explorer application submitted with owner approval and approved by Google (2,880 production operations/day). Production and temporary loopback callbacks saved.

Configured: OAuth callback `/api/marketing/connections/callback`, existing platform Google OAuth credentials, enabled Analytics Admin/Data and Ads APIs, and Explorer production access. Remaining: per-account marketing consent, actual property/account report acceptance, and Google's verification of the new scopes. Existing calendar consent does not authorize marketing data. Real GA4 and directly accessible Ads report acceptance passed. Empty responses are displayed as no-data states, without invented zero metrics.

Sources: https://developers.google.com/analytics/devguides/config/admin/v1/rest/v1alpha/accountSummaries/list ; https://developers.google.com/google-ads/api/rest/auth ; https://developers.google.com/google-ads/api/docs/api-policy/access-levels . Google Ads API access is now controlled per Cloud project; legacy developer-token configuration is optional compatibility only.

Acceptance results: all 48 imported tools passed real OpenRouter generation/persistence with 57 provider/boundary checks; 12 local plan-access checks passed. Real Stripe sandbox Checkout, activation, customer portal, period-end cancellation, failed renewal (past_due → local paused/API403), payment recovery (active/17 Essentials tools), and immediate cancellation were verified. See adjacent JSON reports. The disposable subscription was canceled after testing. Prices remain unpublished outside the temporary local sandbox purchase test.

OAuth failure-path acceptance: an invalid/denied state returned a safe307 redirect to the client Connections page with a generic retry message. The page displays an explicit expired/failed authorization notice. A success notice requires a matching saved connection, rather than trusting a query parameter alone.

Clients can explicitly attach a connected GA4 or Ads report to supporting evidence in an entitled tool. The UI discloses that this data is sent to AI only when Create draft is selected. A real GA4 report was attached and then cleared in the browser; no live report data was submitted to AI during acceptance. Staff do not receive attachment controls for another selected tenant.
