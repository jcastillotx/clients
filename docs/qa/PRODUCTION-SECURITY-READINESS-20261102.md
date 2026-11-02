# Clients production readiness — November 2, 2026

Verdict: operational core, incomplete full-platform acceptance. This is a bounded assessment, not a guarantee of absolute security.

## Current production evidence

- Public health reports database/auth operational at release b4863bbc63fc34d6f9b91a30f2c4f5f82129adeb, matching current main before this security patch.
- Supabase runtime disables signup and does not auto-confirm email. Anonymous registration HTML contains the invitation-only message and no password input.
- All 130 public application database tables have RLS enabled. This structural check does not establish the correctness of every policy; authorization/tenant regression tests remain separately required. Marketing credential tables intentionally have no browser policies and are served through authorized server handlers.
- All observed Clients application, Supabase and Inngest containers are healthy. Background schedules are enabled. The backup timer is active and its latest service invocation reports success; no new restore drill was performed in this audit.
- Shared host coverage reports seven absent Kre8ivLabs release containers, separate from the Clients services; no unrelated workloads or monitoring expectations were altered.
- Upstash distributed limiting is not configured: existing application limits are per-process memory limits. Restart and multi-instance limits require a shared store before scaling.
- HTTPS, HSTS, frame denial, MIME protection and CSP are present. Existing production CSP permits inline script and eval; this patch removes eval in production while retaining it for the development compiler. Inline-script allowance remains technical debt and requires a nonce migration with browser coverage.

## Security changes in this patch

Nodemailer 8.0.11 is replaced by 10.3.1. The SMTP transport disallows local-file and URL access. CI and the off-host production release now reject high as well as critical production dependency advisories. Zod 4.6.6 already exists on current main; tests now verify invalid form input and early rejection of oversized collections against that patched validator. An earlier installed dependency tree belonged to the older work branch; it must not be represented as a scan of the deployed image.

Validation: production build, TypeScript, lint (existing warnings), 488 unit tests and 17 release-safety tests pass locally. A real localhost SMTP exchange verifies message/attachment delivery through the patched client. Dependency audit after installation reports zero high/critical, 55 moderate and 9 low findings; these remaining dependency paths still need reachability/upgrade review. A clean high-severity audit is not an all-advisory clean audit.

## Outstanding acceptance gates

PR236 provider integrations remain a separate release. Last observed Meta access verification is In review; Meta App Review and Google sensitive-scope review remain incomplete. Owner passkey verification, factual Meta data-handling answers, complete demo/reviewer materials and final provider approvals remain required. Draft plan prices must stay unpublished until owner approval. No new payment, provider grant or client-data disclosure is authorized by this assessment.

This source patch must pass required PR checks and the existing off-host production release before it is described as deployed. Preserve runtime secrets, schedules and rollback state. Do not claim every client workflow was newly tested against real providers in this audit.
