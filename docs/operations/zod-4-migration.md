# Zod 4 security migration

Owner approval: Zod 3 to 4 upgrade approved in this chat on 2026-10-28.

The application and transitive Zod copies resolve to 4.6.6. React Hook Form uses its compatible resolver and separates raw input types from validated output types. FormField forwards the transformed-values generic. Record schemas specify string keys explicitly, API validation errors use issues, and error-path labels handle all PropertyKey types safely.

Database identifier validation uses Zod 4 guid instead of its newly strict RFC-only uuid validator. This preserves the prior PostgreSQL identifier contract, including legacy GUID-shaped identifiers, while rejecting malformed IDs. No tenant or authorization checks are relaxed.

Compatibility coverage includes invalid form errors, default values, numeric coercion, legacy database identifiers, and the existing AI chat streaming protocol. Validation: TypeScript, production build, lint (pre-existing warnings), 475 tests, and 17 release safety tests passed locally. The portal production dependency audit drops to three high findings, all in Nodemailer; no critical findings remain. Nodemailer’s major upgrade is separately pending owner approval. Existing moderate and low findings remain for further triage.

The Dockerfile frontend and both Node build/runtime stages are pinned to the exact digests resolved by the preceding successful production release, so the image sources no longer depend on mutable tags. Release tests prevent removing those pins.
