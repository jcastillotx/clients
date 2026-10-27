# Stripe provider acceptance — October 27, 2026

The KRE8IV-owned account is Kre8ivDesigns Marketing, LLC (`acct_19EQ4jJboZZQxmLk`). Existing sandbox credentials authenticated to that account. Fourteen real Stripe API/webhook checks passed against the synthetic local database. Embedded payment and hosted Checkout were also completed in Chrome using Stripe test cards; the embedded flow included completion of Stripe's 3D Secure 2 test challenge. Both invoices persisted as paid. No real money moved, and no production client records were used.

The checks cover intent creation, account-specific publishable keys, repeated initialization, foreign-tenant denial, success, decline/retry, authentication-required state, full refund, replay protection, subscription creation/update/cancellation, and hosted-session persistence. Stripe CLI forwarded actual signed sandbox events to the provider-test runtime. Controlled replay used a real provider event with the listener signing secret.

Provider testing found and repaired payment-dialog initialization on controlled opening, selection of the correct account publishable key, preservation of the dialog during authentication, visible initialization errors, duplicate intent prevention, scrollable payment controls, and secure hosted Checkout-to-invoice binding. Migration 038 adds the checkout-session field. Checkout is expired if its binding cannot be saved. Reconciliation retrieves the saved session in the verified account and checks intent, invoice, amount, and currency before settlement.

Evidence: `stripe-provider-results.json`, `stripe-3ds-sandbox.png`, `stripe-invoice-paid.png`, `stripe-checkout-success.png`. Test credentials and listener secrets remain outside the repository in protected local files. The original simulator environment file is preserved separately from the provider-test configuration.

Google, Microsoft, marketing integrations, and real recipient email delivery remain outside this Stripe-only acceptance phase. Live account configuration and deployment details are recorded below when completed.
