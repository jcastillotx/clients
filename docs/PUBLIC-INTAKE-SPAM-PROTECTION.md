# Public project intake protection

Production requires Cloudflare Turnstile keys at build and runtime:
`NEXT_PUBLIC_TURNSTILE_SITE_KEY` at build and runtime, and
`TURNSTILE_SECRET_KEY` at runtime only. Configure the widget for the exact
portal hostname. Missing production configuration returns 503; missing or
invalid tokens return 400 before creating clients, requests, or email.
Development without keys continues to support local form testing.

The project form also includes an offscreen `contactFax` honeypot. Nonempty
values are rejected before persistence or notifications. Existing limits allow
five public intake attempts per IP per 15 minutes; without Upstash, that limit
is per running process and resets on restart.

Project notifications now supply the snake_case variables used by editable
email templates, including the description (or executive summary fallback)
and the canonical request URL.

On October 23, 2026, the 22:53:48 UTC public submission was inspected in the
production database. Random text across its fields and requested dates in
1970 establish automated spam. The record was retained for evidence; its
provided contact email does not establish who submitted it.

Regression checks cover CAPTCHA failure before writes/mail, honeypot failure
before writes/mail, fail-closed production configuration, provider token
acceptance/rejection, and populated notification variables.
