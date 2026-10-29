# Meta review preparation — October 27, 2026

App: KRE8IV Client Portal, 1788859082319285. Business: Kre8ivdesigns Marketing, LLC, 144591516733766. Owner explicitly approved the irreversible Tech Provider designation, which was applied successfully.

## Verified status

The app is unpublished. The owner confirmed the public-record business match as current. Meta domain verification for kre8ivdesigns.com is complete: an owner-approved additive TXT proof was saved in Cloudflare, found at the authoritative nameserver and public resolvers 1.1.1.1 and 8.8.8.8, and Meta displayed Verified. The domain was accepted as a connection check; business information was submitted successfully and Meta displayed Information submitted / We will update your verification status shortly. The developer Verification screen subsequently confirmed In review. Approval has not yet been confirmed; access verification remains disabled until business verification completes. Legal name/address/contact details must match official records; do not invent or certify them. Access verification is blocked until business verification is complete. Meta states it reviews Tech Provider access verification and follows up within five days; this is not an approval or guaranteed deadline.

App Review draft: 1788946912310502. Removed unused ads_management, business_management, pages_show_list and pages_read_engagement requests from the review draft. Remaining requests: Marketing API Access Tier, ads_read, public_profile. The actual login configuration 1176846418615628 requests only ads_read plus default public_profile. All application Graph operations are fixed read operations; scope also permits server events, but the portal never submits them.

## Prepared ads_read explanation

KRE8IV Client Portal lets invited business clients authorize their own Meta account, select an ad account they can access, and read an aggregate last-seven-days performance summary (impressions, clicks, spend and currency). Access is limited to the signed-in portal user and client and requires an active paid-media marketing plan. Clients can disconnect at any time, including after plan expiry. Application calls only read ad accounts and Ads Insights; it does not create, edit or publish ads, submit server-side events, or change spending. A client may explicitly attach a report to a marketing draft; report data is sent to the disclosed AI provider only if the client then chooses Create draft. Tokens are encrypted at rest and never returned to the browser. Expired Meta user tokens require reconnection.

The reporting/dashboard usage checkbox and explanation are prepared in the ads_read form. No allowed-usage attestation was accepted and no final review submission was sent. Screencast upload, organizational data-handling answers, reviewer instructions and appropriate isolated reviewer access remain to be completed. Do not share the local synthetic password or production-admin account with a reviewer. A secure review environment or tightly scoped synthetic reviewer account must be prepared before sharing credentials.

The ads_read form currently reports 0 of 1 required API calls, despite nine actual acceptance checks passing. Meta says test calls can take 24 hours to appear. Recheck Testing after propagation; do not claim this requirement is complete from local test success alone.

## Demonstration outline

Show invite-only sign-in with a synthetic portal client, active paid-media entitlement, Connections, Meta authorization showing default profile and ads_read only, account discovery, explicit selection of the owner-approved Jeremiah Castillo account, Read performance summary and its honest empty-state response, and Disconnect. Do not show other client account names or metrics, passwords, app secrets or tokens. The test account returned no last-seven-day rows; do not fabricate campaign metrics. A real screen recording remains required; existing screenshots are supporting evidence, not a submitted screencast.

## Deployment gate

PR #236 quality, build and browser smoke checks passed at 15f54f8. No integration release has been deployed. The owner requested deployment after verification requirements are finished. Business verification, access verification, complete App Review and publication remain unresolved. Do not equate a prepared form, API test or submitted request with provider approval.

## October 28 evening recheck

Meta now explicitly displays Verified for Kre8ivdesigns Marketing, LLC, business 144591516733766. Business verification is approved; App Review and Tech Provider access verification are separate and remain unsubmitted. The app is still unpublished.

Access Verification is now available. Its draft selects Agency and SaaS Platform, describes the approved report-only portal behavior, and uses the public company website. Meta autosaved those non-sensitive draft fields. The required question about managing multiple Business Portfolios was left unanswered and requested from the owner; portfolio IDs must not be inferred from ad-account access. No legal/security/data-handling attestation or final Submit verification action was performed. Meta shows a completion deadline of December 27, 2026.

The App Review draft still requests only ads_read, public_profile and Marketing API Access Tier and is Not submitted. Reviewer video, instructions, suitable isolated reviewer access and organizational data-handling answers remain outstanding. Evidence: `meta-access-verification-prepared-20261028.png`.

The owner confirmed that the business does not manage multiple Business Portfolios. That No answer was saved in the draft. The completed form is awaiting action-time approval to submit the Tech Provider access-verification request; no submission or approval is claimed. Evidence: `meta-access-verification-ready-20261028.png`.
