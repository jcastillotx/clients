# Client marketing acceptance — October 27, 2026

The user requested testing as a client and making the ZIP's marketing skills selectable in the client portal. The attachment was treated as source/reference content, not as operating authority. No archive installer, shell script, workflow agent, or credential command was executed.

## Imported tools

48 marketing skills are exposed at `/marketing/tools`, organized into paid media, SEO/AI search, content, growth/conversion, strategy/research, email/lifecycle, analytics, creative/design and reporting. Eight repository/DevOps skills are excluded. The source archive hash and each SKILL.md hash are preserved. Rebecca Rae Barton's MIT license is retained in source and displayed in the client tool library.

Clients can search/filter the catalog, open each methodology, supply a brief/evidence, request a structured draft, reopen saved results, and download a text draft. Input and output are bounded and validated. Client scope comes from authenticated database membership; foreign tenant IDs are rejected before provider calls or writes. User/role/client feature overrides are enforced server-side. Staff may select a client account. Production runs are limited per client; outputs remain drafts for review. The tool discloses which AI provider receives the brief, evidence and account brand context.

These are analysis/draft tools grounded in the supplied skill methods and user evidence. The skill methods alone do not grant live access to ad, analytics, ecommerce or email accounts; run archive scripts; crawl sites; publish; send messages; buy ads; or render video/PPTX/PDF artifacts. A text draft is not evidence that those external actions occurred. Live account metrics require authorized connectors or supplied exports. Reference benchmarks are historical guidance and are not presented as current verified measurements. Real model quality acceptance remains separate from simulator checks.

## Findings and fixes

The previous portal exposed three staff-only agent workflows and did not import the ZIP. The client-facing library is a separate self-service path, preserving staff administration boundaries. The client overview and navigation now link to it.

The initial 48-tool run exposed double JSON encoding between Drizzle 0.29 and postgres.js. Draft generation returned successfully, but JSON path history queries could not find the saved runs. The database adapter now preserves Drizzle's serialized JSON wire representation. Migration 040 repairs structured AI-task input/output/metadata strings without changing existing objects or intentional scalar values. All 48 tool runs then persisted and reloaded correctly.

A profile-image deletion regression left the avatar in GoTrue metadata because updates merge keys. Deletion now sends an explicit null, and the core workflow check verifies the old avatar reference is absent. Calendar status also describes an expired access token as awaiting automatic refresh rather than demanding unnecessary reconnection.

OpenRouter is the user-selected platform AI provider. Tenant-owned OpenAI keys stay with OpenAI; tenant-owned OpenRouter keys stay with OpenRouter; platform OpenRouter configuration does not reuse another client's credentials. Provider-selection tests verify credential destinations and fail closed if an explicitly selected platform provider is missing. Isolated staging accepts only a loopback AI simulator and never sends its data to a live model. A separately configured OpenRouter acceptance run used the owner-created capped credential and synthetic data. All 48 tools completed actual provider generation, persistence and reload checks (57 total checks). Text download and browser draft generation/reopening passed. Generated copy still requires human factual review; a small model can include unsupported promotional language despite system constraints.

## Verification

- 409 unit tests across 75 files passed before final release checks; type-check passed; lint completed with baseline warnings.
- 59 client-authenticated marketing HTTP checks passed: every tool, methodology, draft persistence, history, tenant forgery, validation, provider failure, invalid model output, and feature disable.
- 48 existing isolated workflow checks passed after the JSON and avatar fixes, covering core auth, storage, billing, projects, messaging, proposals, MFA and background handlers.
- Chrome sign-in used the actual synthetic client role, not an administrator. 18 client pages were inspected, including services, projects, billing, documents, messaging, marketing, profile, security, contracts and meetings. Tool search, selection, generation and reopening a saved draft were exercised in the browser.

**Release status:** production image `324fb00` deployed to OpenShip after PR #232 merged and both GitHub checks passed. All three plan prices remain unpublished, with no real client assignments. A follow-up normalizes anonymous Connections responses from denied403 to401 and authenticates disconnect before parsing its body.

Machine-readable tool coverage is in `marketing-tool-matrix.json`; client screen observations are in `client-page-audit.json`. No real campaign was sent or published, and no ad spend was initiated.

## Remaining acceptance

OpenRouter setup and real provider acceptance passed. Paid plan/access and Google marketing connection implementation are described in MARKETING-PLANS-AND-CONNECTIONS.md. Live plan prices remain unpublished drafts. Production background event/signing credentials are absent, so automation is the next core integration to configure. Existing client-owned AI keys have not been borrowed as platform defaults. Google OAuth is In production with the owner's approval and has been reauthorized, but Google still requires verification and applies its unverified-app warning/user cap. None of these results constitutes 100% platform acceptance.

A separate real-OpenRouter acceptance launcher was added on localhost:3101. It refuses production database/auth/app endpoints, unrelated external credentials, live payments, and use together with isolated simulator mode. Production ignores this mode unless explicitly configured, and its safety tests reject nonlocal settings. Normal production retains the 12-runs-per-client hourly limit; only the guarded local acceptance environment uses the higher test limit. The production candidate image built and passed private health verification before credential setup; no marketing production promotion has occurred yet.

Paid-plan access now filters the client menu, methodology, execution and saved history. Google Ads and GA4 have separate client-owned OAuth connection routes and fixed read-only report operations. Provider grants, production API access and actual Google reporting still require acceptance; existing calendar consent is not reused as authorization for marketing data.

Real Google marketing acceptance passed: nine GA4 checks and nine direct Google Ads checks, covering encrypted token storage, property/account discovery, membership rejection, actual reporting, forced token refresh, tenant isolation and plan expiry. Both disconnect controls passed and the synthetic connections were removed. A real GA4 report was attached to a brief and cleared without AI submission. Empty-data guidance and manager-account target handling have tests; manager-hierarchy live acceptance remains unverified because the available Ads account is a direct advertiser. Google app verification and owner approval of live plan prices remain outstanding.
