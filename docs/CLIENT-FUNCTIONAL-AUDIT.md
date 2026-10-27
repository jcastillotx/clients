# Client functionality audit

Audited the live portal using a temporary client-role user linked to a separate synthetic client company. No real client credentials were used. Business actions that send email, charge money, accept agreements, create requests, or modify real client data were not submitted.

## Confirmed failures and repairs

- Projects list and project-detail reads exposed a different tenant's project. Added tenant checks to page and API reads, reject foreign tenant filters and unassigned users, preserve staff reads, and exclude deleted projects for client lists.
- Project management controls were visible to clients. Hide management links and guard new/edit/template pages with server-side admin checks.
- Shared route-access helpers trusted client-controlled user metadata. Use persisted database roles and flags only.
- Proposals and Maintenance Plans called admin-only catalogs and failed with HTTP 403. Added authenticated active-template catalog endpoints, excluding creator/internal-note fields, and connected client pages to them. Admin catalog endpoints remain restricted.
- Document and contract embedded queries failed with PGRST200 due to missing foreign keys. Added migration 029 for documents.client_id, documents.uploaded_by, and contracts.client_id, then reloaded the PostgREST schema cache.

## Validation

- Type checking passed.
- Lint completed with existing warnings.
- Full unit suite: 301 tests passed across 54 files.
- Browser audited dashboard and every client sidebar destination, plus service request, support ticket, and project request forms without submitting them.
- User administration redirects a client to the dashboard.
- Requests, support, proposals, maintenance plans, and messaging list APIs returned tenant-scoped empty states for the synthetic client.

## Coverage limits

Empty-state and authorization tests do not establish successful end-to-end record creation, file uploads/downloads/versioning, message sending/receipt, notifications/email delivery, payment processing, agreement signing, MFA enrollment/recovery, enabled marketing integrations, or complete responsive/accessibility behavior. These require representative fixtures and an isolated environment, with external actions explicitly controlled. No visual baseline was available for regression comparison.

Source review also found legacy metadata-based authorization fallbacks outside the shared route-access helper. Those call sites need a dedicated review; this audit does not certify every authorization path.
