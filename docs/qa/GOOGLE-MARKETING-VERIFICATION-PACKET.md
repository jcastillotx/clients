# Google marketing verification packet

The Clients project (clients-498402) has Analytics Admin, Analytics Data, Google Ads and Search Console APIs enabled. Ads Explorer production access is approved. Real owner-account GA4 and direct Ads acceptance passed, and all test tokens were removed from the isolated database through the client Disconnect controls.

The OAuth app is In production but Google still labels its sensitive scopes unverified. A scope verification request has not been submitted. This is a remaining public-client acceptance gate; do not describe the entire platform as100% accepted.

## October 27 verification preflight

Branding verification was run against the actual app. Google rejected the homepage because https://clients.kre8ivdesigns.com is not registered as owned by the app owner. The existing www URL-prefix property does not establish ownership of the portal subdomain. A separate portal URL-prefix property has been prepared under kre8ivdesigns@gmail.com and its HTML proof was downloaded to public/googled5c247da2c7824b1.html. Owner approved publication and the ownership claim. The verification file was published to the existing production container, which was restarted to refresh Next.js public-file discovery; the public HTTPS file returned 200 and matched the downloaded proof exactly. Search Console displayed Ownership verified using HTML file verification. The source file is retained in public/ so subsequent releases preserve ownership. The integration routes and credentials have not been deployed. Google instructs waiting 24 hours after successful ownership verification before retrying branding. Sensitive-scope submission is disabled until branding is verified and published.

Public homepage and login now have local changes adding the exact application purpose, operator, connected-account privacy, terms and support links. These changes are tested but not deployed.

## Scope explanation draft

- webmasters.readonly: lets a consenting client select an accessible Search Console property and read finalized aggregate web-search clicks, impressions, CTR and average position. No site-management, sitemap submission or URL mutation is exposed.
- analytics.readonly: lets an explicitly consenting client select a GA4 property and retrieve aggregate sessions, active users and key events. No Analytics changes are made.
- adwords: Google offers no narrower read-only Ads scope. The application lists authorized advertiser accounts (including verified manager-to-client relationships) and reads seven-day aggregate reporting. The application exposes no campaign mutation, publishing, billing setup or ad-spend operation.
- calendar.calendars.readonly and calendar.freebusy remain separate scheduling grants. Marketing consent is not inferred from calendar consent.

## Public references and security

Home/sign-in: https://clients.kre8ivdesigns.com/login
Privacy: https://clients.kre8ivdesigns.com/calendar-privacy
Terms: https://www.kre8ivdesigns.com/terms/

Tokens use AES256GCM encryption, user/client ownership, single-use hashed OAuth state, PKCE and expiry. Selected resource identifiers are checked against Google account membership. Active plan entitlements control use; disconnect remains available after expiry. A client must explicitly attach report data to a brief and select Create draft before it is submitted to AI. Acceptance did not submit live Google report values to AI.

## Submission preparation

Google's Data Access screen requests a demonstration video showing authorization and use of every scope. Prepare a recording with synthetic briefs and redacted/owned account data, confirm brand/domain requirements in Verification Center, and review the minimum-scope explanation above before submitting. This packet is a draft, not a submitted verification application or a Google approval. Any reviewer account access, external upload/publication and final submission require the owner's authorization at those steps.

Evidence: google-analytics-provider-results.json, google-ads-provider-results.json, google-marketing-cleanup-results.json, google-ads-explorer-approved.png and client-connected-report-tools.png. Manager-hierarchy behavior has contract tests; no live manager account was available for acceptance.

## October 28 evening recheck

After the ownership-propagation wait, Google branding re-verification succeeded. The verified branding was published; Verification Center explicitly confirms it is verified and being shown to users. The ownership HTML proof still returns the exact expected content publicly. No OAuth audience or scope was expanded.

Sensitive-scope verification is now available but remains unsubmitted. The preparation form requires scope justification and a demonstration video. A 905-character explanation covering the existing five scopes was drafted without changing scopes. Save remains disabled while the required YouTube demo URL is missing; the draft is not claimed as saved or submitted. A recording must show all assigned OAuth clients, the real consent flow and the expected unverified-app screen in an isolated review environment. No production integration release was performed.

Evidence: `google-branding-verified-20261028.png`.

## October 29 demo preparation

Restored the dedicated localhost provider acceptance environment with synthetic tenants and only Google provider configuration. Actual owner Analytics reauthorization, selection of Kre8ivDesigns Website (properties/561030184), aggregate reporting and disconnect passed. No report data was sent to AI. Partial captured material is private and is not a completed or uploaded demo. Ads authorization now requires the owner’s passkey identity check; the browser tab is preserved for that handoff. Search Console and Calendar still need their demo sections. The required YouTube URL is not supplied, and scope review is not submitted.
