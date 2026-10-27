# Stripe provider acceptance — October 27, 2026

The KRE8IV-owned account is Kre8ivDesigns Marketing, LLC (`acct_19EQ4jJboZZQxmLk`). Existing sandbox credentials authenticated to that account. Fourteen real Stripe API/webhook checks passed against the synthetic local database. Embedded payment and hosted Checkout were also completed in Chrome using Stripe test cards; the embedded flow included completion of Stripe's 3D Secure 2 test challenge. Both invoices persisted as paid. No real money moved, and no production client records were used.

The checks cover intent creation, account-specific publishable keys, repeated initialization, foreign-tenant denial, success, decline/retry, authentication-required state, full refund, replay protection, subscription creation/update/cancellation, and hosted-session persistence. Stripe CLI forwarded actual signed sandbox events to the provider-test runtime. Controlled replay used a real provider event with the listener signing secret.

Provider testing found and repaired payment-dialog initialization on controlled opening, selection of the correct account publishable key, preservation of the dialog during authentication, visible initialization errors, duplicate intent prevention, scrollable payment controls, and secure hosted Checkout-to-invoice binding. Migration 038 adds the checkout-session field. Checkout is expired if its binding cannot be saved. Reconciliation retrieves the saved session in the verified account and checks intent, invoice, amount, and currency before settlement.

Evidence: `stripe-provider-results.json`, `stripe-3ds-sandbox.png`, `stripe-invoice-paid.png`, `stripe-checkout-success.png`. Test credentials and listener secrets remain outside the repository in protected local files. The original simulator environment file is preserved separately from the provider-test configuration.

Google, Microsoft, marketing integrations, and real recipient email delivery remain outside this Stripe-only acceptance phase. Live account configuration and deployment details are recorded below when completed.

## Production release

Source `6494970` is live as `clients:stripe-6494970`. Migration 038 was applied after a protected full database backup. Existing KRE8IV production API credentials and the existing live webhook (`we_1TmSYWJboZZQxmLkf4fy7xHW`) were installed in protected runtime configuration. No duplicate endpoint was created and no key was rotated. Client-specific Stripe overrides remain intact. The webhook listens for payment success/failure, refunds, and subscription creation/update/deletion. Its existing `2016-07-06` snapshot fields were checked against real sandbox objects and retained.

The production container is healthy with zero restarts. A read-only live API request confirms the correct KRE8IV account with charges and payouts enabled. A signed invoice-free configuration probe passed through the public webhook (200); unauthenticated payment-intent creation returned 401. No live transaction was created. A real live-sale settlement/delivery is not claimed by these sandbox checks.

The protected runtime environment and prior container/database snapshots are retained for rollback (`clients-before-stripe-20261027`). TypeScript passed; 319 tests passed in 58 files; lint completed with existing repository warnings; the Linux amd64 production build passed. Details are in `stripe-production-release.json`.
