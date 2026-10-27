# Public marketing purchase path

The owner approved the existing monthly USD packages on October 27, 2026:

| Package | Monthly | Included tool groups |
| --- | ---: | --- |
| Marketing Essentials | $49 | Content & Social, Strategy & Research, Email & Lifecycle |
| Marketing Growth | $149 | Essentials plus SEO & AI Search, Growth & Conversion, Analytics, Reports & Deliverables |
| Marketing Complete | $299 | All nine groups, including Paid Media and Creative & Design |

These are software and AI draft tools. Agency services, media spend and external subscriptions are separate. Plans remain controlled by the administrator-published catalog; changing a plan's price affects new subscriptions only.

`/marketing-tools` exposes only active, published, positive-price offers. Returning clients sign in and purchase through the existing `/marketing/plans` Stripe checkout. New purchasers request a CAPTCHA-protected, rate-limited email invitation. The purchase-only provisioning route creates an empty tenant and assigns only the client role. It never attaches an existing email/account to a different client, trusts client/role metadata, confirms an email itself, creates a password, or assigns a paid plan. Existing accounts use ordinary sign-in/recovery. General registration and Supabase public signup remain disabled.

The invitation verifies email through the existing auth-confirm handler and continues to the plan catalog. Stripe's signed platform subscription webhook remains the only purchase activation path. Unpaid accounts receive HTTP403 from paid tools. Platform subscription credentials must come from explicit platform environment settings; another tenant's saved Stripe keys are never used.

Production requires the existing public Turnstile site key and server secret, service-role credentials for narrowly scoped invitation provisioning, configured email delivery, explicit platform Stripe credentials, and a working signed webhook. No schema migration is required beyond the existing marketing-plan tables. Disable a published plan to stop new sales; existing subscriptions retain their agreed price and billing lifecycle.

Acceptance uses synthetic local tenants and captured invitation email, with Stripe test credentials for provider checkout. Never use a live card or real charge for this test. Public production checks stop before submitting a paid subscription.
