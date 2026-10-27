# Client workflow follow-up — October 27, 2026

## Scope and evidence

All mutations ran against the dedicated localhost staging app/auth/database and synthetic alpha/beta/staff/admin accounts. Local mail capture, S3 and Stripe simulators were used; no real payment or third-party message was sent. Core matrix: 48 passing checks. Extended matrix: 12 passing checks with actual HTTP requests and persistence assertions, including concurrent requests, direct page fetches and cross-tenant denial. Unit suite: 432 tests across 80 files passed. Type-check passes and lint completes with existing warnings.

Browser navigation covered all 26 initial client menu destinations and six project actions. The password-recovery test rotated the synthetic password and invalidated the old browser session as expected; the browser signed back in with the updated protected fixture. Navigation transport timeouts and one transient failed fetch were rechecked; they were not treated as verified product defects. Design-review comments were saved through the client UI, reloaded and reopened successfully. Design-review website pins, validation, image upload/download byte integrity and foreign-tenant denial passed HTTP acceptance.

## Reproduced defects and fixes

- Foreign proposal cancellation disclosed accepted status before authorization. Authorization now precedes state disclosure.
- Concurrent read-receipt creation hit the message/user unique constraint and returned 500. The write is now an idempotent upsert. Mark-all reads also tolerate concurrent requests.
- Notification GET used a correlated EXISTS projection whose generated query failed to recognize existing read receipts. It now joins the signed-in user's receipt explicitly; marking read and reloading yields zero unread.
- Concurrent proposal cancellation and acceptance could return 500, and cancellation lacked a write-time accepted-state guard. Both transitions now fail safely with a conflict when another transition wins; accepted signatures are preserved.
- Nested budget, timeline, feedback and redirect pages queried projects without the main page's tenant authorization. They now resolve authoritative account membership before returning project data or metadata redirects.
- Staff task-board server pages and the all-project budget view lacked staff checks. Staff page guards now run before data queries. Clients cannot open staff tasks/time-tracking routes, and the client project overview omits those staff-only actions.
- Team-management controls were shown to users whom the mutation API rejects. They now require the same admin authority as the API; ordinary clients retain read-only team visibility.
- Request-less project Messages links looped back to the overview. They now open the client's existing Messages inbox; projects linked to a request retain that request's messaging destination.
- Added a screen-reader name to the design-review comment field. Root theme hydration handling acknowledges next-themes' expected html attribute mutation without suppressing descendant errors.

## Verification limits

This is not full accessibility or physical-device certification. No committed visual baseline exists for a three-breakpoint comparison, so that portion remains INCONCLUSIVE. No production background-provider credentials, new payment-provider acceptance or Google/Meta approval is claimed by this run. Existing provider acceptance is documented separately. The Google/Meta integration PR remains gated on provider verification; these repairs are being isolated for an independent release.
