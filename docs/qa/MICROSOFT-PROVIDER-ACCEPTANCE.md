# Microsoft provider acceptance — October 27, 2026

The existing production email provider is Office 365 / Microsoft Graph, with `info@kre8ivdesigns.com` as the branded sender. The user selected that address for testing; the initial controlled message was sent back to the same inbox.

The same `lib/email/providers.ts` implementation used by the portal and background jobs loaded the saved encrypted settings inside the production container, refreshed credentials as required, and sent the branded message plus `provider-verification.txt`. No OAuth secrets or refresh tokens were copied out of production. A standalone background-check bundle deliberately throws if request-scoped cookies are invoked; it uses the normal service-role configuration loader.

The provider accepted the send. A separate connected Outlook read confirmed receipt in the exact shared mailbox at 2026-10-27 14:29:02 UTC (09:29:02 America/Chicago), from and to `info@kre8ivdesigns.com`, with the expected named attachment. This proves recipient delivery for that controlled message, rather than only API acceptance. It does not prove delivery to every client domain.

Calendar OAuth configuration, consent, availability and revocation/refresh acceptance remain in progress. The existing Microsoft email app is being inspected for reuse before creating any additional registration.

## Calendar repairs

Calendar callbacks now require the initiating authenticated portal user, reject inaccessible provider calendars before storing a connection, and select only the user's own credential scope or platform environment credentials. Availability applies authenticated user visibility before loading provider tokens. Provider failure reports `unavailable`, not `free`. Microsoft requests `Calendars.ReadBasic`, does not request event subjects, and normalizes provider timestamps to UTC. The production app's canonical calendar callback has been added to the existing single-tenant email registration; user consent and live availability verification are pending deployment.

Regression coverage includes cross-session callback rejection for both providers, foreign credential-scope rejection, platform-only fallback, invisible-user denial before privileged reads, provider failure accuracy, and omission of event titles. The expanded suite passes 330 tests across 62 files, with TypeScript passing and existing lint warnings documented.

## Live calendar and refresh acceptance

The owner approved an individual-account grant for `j.castillo@castillocollective.com`; organization-wide consent remained unchecked. Microsoft returned to the authenticated portal owner and stored an active calendar connection with encrypted refresh material. A live availability lookup succeeded. After setting only this new connection's expiry metadata to the past, the same application endpoint refreshed the token, persisted a future expiry, and again returned availability successfully. Disconnect removed the connection, and reconnect restored the same previously approved account. No calendar events were created or edited, and no event titles were requested.

Email lifecycle testing found that rotated refresh tokens were previously discarded and the in-memory configuration remained stale after renewal. The repair persists optional rotated refresh tokens encrypted, updates the cached configuration, and surfaces persistence failures. Two new regression tests cover rotation/reuse and persistence failure. A forced-expiry real Microsoft email check used the updated provider implementation, received a rotated refresh token, saved it, and sent another controlled message to `info@kre8ivdesigns.com`. Final suite: 332 tests in 63 files; TypeScript and lint complete with existing lint warnings.

## Final production verification

Application source `61fa8d8` is live on OpenShip. The real Admin → Email → Send test flow was exercised under the owner's existing authenticated portal session after forcing email expiry metadata to the past. Microsoft accepted it; the encrypted refresh-token and future-expiry rows were updated at 2026-10-27 15:21:09 UTC. The independent Outlook read confirmed delivery to `info@kre8ivdesigns.com` at 15:21:13 UTC (10:21:13 America/Chicago). The calendar connection remained active. The container was healthy with zero restarts, and all 10 original active profiles remain present. The previous container and protected database/configuration snapshots are retained as rollback assets.
