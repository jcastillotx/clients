# Client Portal Wiki (Client User Guide)

This guide covers how to use the client portal day-to-day: logging in, submitting requests, working with documents, viewing/signing contracts, paying invoices, and using optional features like messaging, analytics, and the AI assistant.

## Quick links (in-portal)
- Dashboard: `/dashboard`
- Requests: `/requests`
- Documents: `/documents`
- Contracts: `/contracts`
- Invoices: `/invoices`
- Storage: `/storage`
- Projects: `/projects`
- Messages: `/messages`
- Knowledge Base: `/knowledge-base`
- Notifications: `/notifications`
- Analytics: `/analytics`
- Assistant: `/assistant`
- Profile: `/profile`

## Table of contents
- Login and account access
- Dashboard
- Requests (service tickets)
- Documents (library, viewing, sharing)
- Contracts (view, download, sign)
- Invoices and payments (Stripe)
- Storage (connected files)
- Messages
- Knowledge Base
- Notifications
- Analytics
- Assistant
- Profile and password
- Common issues and FAQ

## Login and account access

### Logging in
- Open the portal URL your account manager provided.
- Go to `/login`.
- Enter your email + password.

### Email verification
Some areas require a verified email. If prompted:
- Open the verification email and confirm your address.
- If you didn’t receive it, look for a “resend verification” option on-screen.

### Forgot password
- Go to `/forgot-password`.
- Enter your email to receive a reset link.

### Logging out
- Use the logout option in the user menu (typically top navigation).

## Dashboard
The dashboard (`/dashboard`) is your starting point. It typically highlights:
- Recent request activity (new replies, status changes)
- Key documents/links
- Invoice reminders (if applicable)

Tip: if you’re looking for something specific, go directly to the feature page using the quick links above.

## Requests (service tickets)
Requests are used to ask for work, report issues, or request changes.

### View requests
- Go to `/requests` to see your requests list.
- Click a request to open details, history, and any related files.

### Create a request
- Go to `/requests`.
- Choose **Create** / **New request**.
- Provide:
  - A clear title
  - A detailed description (what you want done, what “done” looks like, deadlines)
  - Priority (if shown)
- Attach any relevant files (screenshots, PDFs, docs).

### Estimate approval (if enabled)
Some requests include a formal estimate for approval:
- Open the request and follow the **Estimate** link.
- Review the scope and cost.
- Approve (or request changes) based on your organization’s process.

Best practice: include acceptance criteria and examples to prevent back-and-forth.

## Documents (library, viewing, sharing)
Documents are your shared library with the team.

### Browse and open documents
- Go to `/documents`.
- Open a document to view metadata and available actions.
- You may be able to view documents directly in the browser (viewer options depend on file type and configuration).

### Download documents
- Use **Download** from the document page (or list actions).

### AI analysis and chat (if enabled)
Some documents support AI tools:
- **AI analysis**: review summaries/insights for a single document.
- **Chat**: ask questions about a document or chat across all documents (if available).

### Share links (public download)
If your team shares a document with a public link, it may look like:
- `/share/{token}`

Only forward share links to people who are permitted to access that content.

## Contracts (view, download, sign)
Contracts are managed under `/contracts`.

### View and download
- Go to `/contracts`.
- Open a contract to view details.
- Use **Download** to save a PDF.

### Sign (if enabled)
- Open the contract.
- Review the content carefully.
- Use the **Sign** action when you are authorized to execute the agreement.

If you don’t see a Sign option, signing may be disabled or restricted for your account.

## Invoices and payments (Stripe)
Invoices live under `/invoices`.

### View invoices
- Go to `/invoices` to see invoice status and details.
- Open an invoice to review line items and totals.
- Download a PDF if needed.

### Pay an invoice
When online payment is enabled, invoices can be paid via Stripe:
- Open the invoice.
- Choose **Pay** to go to the payment page.
- Complete payment using the supported methods.

If you can’t pay online, your invoice may not be marked as payable yet (or online payment may be disabled for your organization).

## Storage (connected files)
Storage (`/storage`) is a unified interface for connected storage providers (e.g., cloud drives).

Common actions:
- Browse synced folders/files
- Download files
- Review conflicts (if shown)
- Adjust storage settings (if your account has access)

If you see missing files, it may indicate a sync delay or permission limitation on the upstream provider.

## Messages
Messages (`/messages`) is for client ↔ team communication inside the portal.

Best practice:
- Keep technical details together (links, screenshots, steps to reproduce).
- For work requests, consider creating/updating a Request as the system of record and use Messages for follow-ups.

## Knowledge Base
The Knowledge Base (`/knowledge-base`) contains how-tos, FAQs, and process docs.

If you can’t find an answer:
- Create a Request or send a Message with the question and context.

## Notifications
Notifications (`/notifications`) centralize portal alerts (and may also send email depending on configuration).

Review this page if you think you missed an update on a request, invoice, document, or contract.

## Analytics
Analytics (`/analytics`) provides reporting views if enabled for your account.

If your analytics looks empty:
- It may be permission-based, or data collection may not be configured for your organization.

## Assistant (AI)
The assistant (`/assistant`) is an optional AI chat experience.

Suggested uses:
- Drafting clearer request descriptions
- Summarizing long documents (when available)
- Turning meeting notes into action items

Do not paste secrets (passwords, API keys) into the assistant chat.

## Profile and password
Profile settings live under `/profile`.

From there you can typically:
- Update your name/contact info
- Change your password

## Common issues and FAQ

### “I can’t log in”
- Confirm you’re using the correct portal URL.
- Try `/forgot-password`.
- If your account is newly created, you may need to verify your email first.

### “I don’t see a page (Documents / Analytics / Payments)”
Access is permission-based. Contact your account manager if you believe you should have access.

### “A payment failed”
Try again and confirm card/billing details. If it continues:
- Contact your account manager and include the invoice number and approximate time of attempt.

### “A shared link doesn’t work”
Share links can expire or be revoked. Ask your account manager to re-share the document.

