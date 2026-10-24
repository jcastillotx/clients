# Form data validation audit — October 23, 2026

Scope: 51 portal source files contain forms. The table below inventories their data boundaries. Marketing website forms are audited in the separate website repository.

The shared schema family now trims required text before length checks, rejects control characters, bounds optional text and uses finite numeric types. Web URLs accept HTTP(S), not script/file/FTP schemes or embedded login credentials. Phone checks support international dialing and extensions. Public project intake rejects unknown countries, invalid US ZIP codes, impossible/past dates and reversed dates. File metadata is validated before upload; existing size and storage controls remain.

New server contracts cover previously unvalidated client updates, projects, tasks/boards, maintenance usage, messages, contracts, admin user updates and document metadata. Browser API preflight uses the corresponding server schemas on form resource endpoints. Existing auth/provider validations, upload authorization, permission checks and tenant boundaries remain in place.

Date-only project/contract inputs are supported. Meetings and time entries convert browser-local date/time input to ISO instants. Partial project/request/time-entry edits compare new dates with the saved companion dates. A user edit does not require a password change, and passwords are never trimmed.

This audit and automated validation do not prove identity or truthfulness of prose. No emails, payments, signatures or provider invitations were executed during verification, and existing records were not rewritten.

## Form inventory

| Form source | Data boundary |
| --- | --- |
| `app/(dashboard)/ads/new/page.tsx` | `/api/ads/campaigns` |
| `app/(dashboard)/marketing/campaigns/new/page.tsx` | `/api/marketing/campaigns` |
| `app/(dashboard)/marketing/content-calendar/new/page.tsx` | `/api/marketing/content-calendar` |
| `app/(dashboard)/marketing/leads/new/page.tsx` | `/api/marketing/leads` |
| `app/(dashboard)/projects/[id]/edit/page.tsx` | `/api/projects/${id}` |
| `app/(dashboard)/projects/new/page.tsx` | `/api/projects` |
| `app/(dashboard)/tasks/new/page.tsx` | `/api/tasks/boards` |
| `components/admin/roles/role-dialog.tsx` | `/api/rbac/roles`, `/api/rbac/roles/${role.id}` |
| `components/admin/templates/email-template-dialog.tsx` | `/api/admin/templates/email/preview` |
| `components/admin/templates/invoice-template-dialog.tsx` | Shared browser schema; Supabase/provider or server action |
| `components/admin/users/user-dialog.tsx` | `/api/admin/users`, `/api/admin/users/${user.id}` |
| `components/ai/chat-interface.tsx` | `/api/ai/chat`, `/api/ai/feedback` |
| `components/ai/kre8iv-help-bubble.tsx` | `/api/ai/help` |
| `components/auth/forgot-password-form.tsx` | Shared browser schema; Supabase/provider or server action |
| `components/auth/login-form.tsx` | Shared browser schema; Supabase/provider or server action |
| `components/auth/register-form.tsx` | Shared browser schema; Supabase/provider or server action |
| `components/auth/reset-password-form.tsx` | Shared browser schema; Supabase/provider or server action |
| `components/brand/add-competitor-dialog.tsx` | `/api/brand/competitors` |
| `components/brand/brand-guide-builder.tsx` | `/api/brand/guides` |
| `components/clients/client-form.tsx` | `/api/clients`, `/api/clients/${initialData.id}` |
| `components/contracts/create-contract-dialog.tsx` | `/api/contracts` |
| `components/documents/upload-dialog.tsx` | `/api/documents/upload` |
| `components/invoices/invoice-form.tsx` | Shared browser schema; Supabase/provider or server action |
| `components/invoices/payment-modal.tsx` | `/api/payments/create-intent` |
| `components/knowledge-base/kb-article-form.tsx` | `/api/knowledge-base/articles`, `/api/knowledge-base/categories` |
| `components/knowledge-base/kb-category-manager.tsx` | `/api/knowledge-base/categories`, `/api/knowledge-base/categories/${id}` |
| `components/maintenance/log-hours-dialog.tsx` | `/api/maintenance-plans/${planId}/usage` |
| `components/meetings/meeting-form.tsx` | `/api/meetings` |
| `components/messages/message-composer.tsx` | `/api/messages` |
| `components/partners/partner-form.tsx` | `/api/partners` |
| `components/payments/public-invoice-payment-form.tsx` | `/api/public/invoices/create-checkout-session`, `/api/public/invoices/lookup?invoice=${encodeURIComponent(invoiceNumber)}` |
| `components/projects/create-project-from-request-button.tsx` | `/api/projects`, `/api/tasks/boards` |
| `components/projects/project-request-feedback.tsx` | `/api/projects/requests/${requestId}/feedback` |
| `components/projects/project-request-form.tsx` | `/api/documents/upload`, `/api/projects/requests` |
| `components/projects/project-review-panel.tsx` | `/api/projects/${projectId}/reviews`, `/api/projects/${projectId}/reviews/${activeReview.id}/comments`, `/api/projects/${projectId}/reviews/upload` |
| `components/projects/public-project-request-form.tsx` | `/api/public/project-requests` |
| `components/proposals/proposal-signature.tsx` | `/api/proposals/${proposalId}/sign` |
| `components/proposals/proposal-wizard.tsx` | `/api/ai/generate-proposal` |
| `components/referrals/referral-form.tsx` | `/api/partners`, `/api/referrals` |
| `components/requests/request-comments.tsx` | Shared browser schema; Supabase/provider or server action |
| `components/requests/request-form.tsx` | `/api/requests` |
| `components/settings/security-settings.tsx` | Shared browser schema; Supabase/provider or server action |
| `components/settings/user-settings.tsx` | `/api/settings/profile-image` |
| `components/staff-guides/guide-category-manager.tsx` | `/api/staff-guides/categories`, `/api/staff-guides/categories/${id}` |
| `components/staff-guides/guide-form.tsx` | `/api/staff-guides`, `/api/staff-guides/categories` |
| `components/support/ticket-comments.tsx` | `/api/support/${ticketId}/comments` |
| `components/support/ticket-detail.tsx` | `/api/support/${ticket.id}` |
| `components/support/ticket-form.tsx` | `/api/support` |
| `components/surveys/survey-create-form.tsx` | `/api/surveys` |
| `components/tasks/create-task-dialog.tsx` | `/api/tasks` |
| `components/time-tracking/time-entry-form.tsx` | `/api/time-tracking` |
