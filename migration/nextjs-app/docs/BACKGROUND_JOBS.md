# Background Jobs & Automation

## Overview

The background jobs system uses **Inngest** for durable, event-driven workflows with automatic retries and step-based execution. This replaces Laravel queues with a more modern, serverless-friendly approach.

## Architecture

### Inngest Benefits

- **Durable Execution**: Jobs survive server restarts
- **Automatic Retries**: Failed steps retry with exponential backoff
- **Step Functions**: Complex workflows with checkpoints
- **Event-Driven**: Trigger jobs via events or cron schedules
- **Free Tier**: 10k steps/month

### Components

1. **Inngest Client** (`/lib/inngest/client.ts`)
   - Configured with app ID and event key
   - TypeScript event definitions for type safety

2. **Functions** (`/lib/inngest/functions/`)
   - Each file exports one or more Inngest functions
   - Functions can be triggered by cron schedules or events

3. **API Route** (`/app/api/inngest/route.ts`)
   - Serves all Inngest functions
   - Handles GET, POST, PUT requests from Inngest Cloud

4. **Email System** (`/lib/email/`)
   - Resend integration for transactional emails
   - Template rendering with database-stored templates

## Scheduled Jobs

### Invoice Reminders
**Schedule**: Daily at 9am PST  
**File**: `/lib/inngest/functions/invoice-reminders.ts`

**Steps**:
1. Find invoices due in 7 days (not yet reminded)
2. Send "due soon" email to each client
3. Mark invoices as reminded
4. Find overdue invoices
5. Send "overdue" email to each client
6. Update invoice status to "overdue"

**Database Updates**:
- Sets `reminded_due_7_at` timestamp
- Sets `reminded_overdue_at` timestamp
- Changes `status` from "sent" to "overdue"

### Recurring Invoice Generation
**Schedule**: Daily at 2am UTC  
**File**: `/lib/inngest/functions/recurring-invoices.ts`

**Steps**:
1. Find active recurring invoice templates due for generation
2. For each template:
   - Generate new invoice number
   - Calculate due date (based on payment terms)
   - Create new invoice record
   - Copy line items from template
   - Calculate next generation date
3. Send notification email to client

**Recurring Frequencies**:
- `monthly`: Generate every 30 days
- `quarterly`: Generate every 90 days
- `annually`: Generate every 365 days

### SLA Compliance Checks
**Schedule**: Every 5 minutes  
**File**: `/lib/inngest/functions/sla-checks.ts`

**Checks**:
1. **Response SLA**:
   - Warns at 80% of response time
   - Breaches when no first response received within SLA
2. **Resolution SLA**:
   - Warns at 80% of resolution time
   - Breaches when not resolved within SLA

**Actions**:
- Send warning email at 80% threshold
- Send breach notification when SLA exceeded
- Update request with `sla_breached`, `sla_breach_type`, `sla_breach_at`

### Contract Expiration Checks
**Schedule**: Daily at 10am UTC  
**File**: `/lib/inngest/functions/contract-checks.ts`

**Steps**:
1. Find contracts expiring in 30 days → Send expiration notice
2. Find contracts expiring in 7 days → Send urgent notice
3. Mark contracts as expired (if past end date and not auto-renew)
4. Auto-renew eligible contracts:
   - Calculate new start/end dates based on original duration
   - Update contract with new dates
   - Increment renewal count

## Email Templates

All emails use database-stored templates (`email_templates` table) with variable substitution.

### Available Email Types

1. **invoice_reminder**: 7-day due soon reminder
2. **invoice_overdue**: Overdue payment notice
3. **invoice_generated**: New recurring invoice created
4. **invoice_payment_received**: Payment confirmation
5. **sla_warning**: SLA threshold warning (80%)
6. **sla_breach**: SLA breach notification
7. **contract_expiring**: 30-day expiration notice
8. **contract_expiring_urgent**: 7-day expiration notice

### Template Variables

Templates support Handlebars-like syntax:
- `{{variable}}` - Simple variable substitution
- `{{#each array}}...{{/each}}` - Loop over arrays

See `/docs/TEMPLATE_SETUP.md` for complete variable reference.

## Webhook Handlers

### Stripe Webhooks
**Endpoint**: `/api/webhooks/stripe`  
**File**: `/app/api/webhooks/stripe/route.ts`

**Handled Events**:
- `payment_intent.succeeded`: Mark invoice as paid, send confirmation
- `payment_intent.payment_failed`: Log failure, send notification
- `charge.refunded`: Update invoice status to refunded
- `customer.subscription.created/updated/deleted`: Log subscription changes

**Security**:
- Verifies webhook signature using `STRIPE_WEBHOOK_SECRET`
- Rejects requests with invalid signatures

## Configuration

### Environment Variables

```bash
# Inngest
INNGEST_EVENT_KEY=your_event_key

# Resend (Email)
RESEND_API_KEY=your_resend_api_key
EMAIL_FROM=noreply@yourdomain.com
EMAIL_REPLY_TO=support@yourdomain.com

# Stripe
STRIPE_SECRET_KEY=sk_live_...
STRIPE_WEBHOOK_SECRET=whsec_...

# App URL (for email links)
NEXT_PUBLIC_APP_URL=https://yourdomain.com
```

### Inngest Dashboard

Access at: https://app.inngest.com

Features:
- View function executions
- Inspect step-by-step execution logs
- Retry failed functions
- Monitor performance metrics

## Testing Locally

### 1. Install Inngest Dev Server

```bash
npx inngest-cli@latest dev
```

### 2. Start Next.js

```bash
pnpm dev
```

### 3. Trigger Functions Manually

Via Inngest Dev UI at http://localhost:8288:
- Test individual functions
- Send custom events
- View execution logs

### 4. Test Webhooks

Use Stripe CLI for webhook testing:

```bash
stripe listen --forward-to localhost:3000/api/webhooks/stripe
stripe trigger payment_intent.succeeded
```

## Deployment

### Vercel

1. Add environment variables in Vercel dashboard
2. Deploy normally - Inngest will automatically discover functions
3. Register webhook URL in Stripe dashboard:
   - `https://yourdomain.com/api/webhooks/stripe`

### Inngest Cloud Setup

1. Create account at https://app.inngest.com
2. Create new app
3. Add signing key to environment variables
4. Functions auto-register on first deployment

## Monitoring

### Inngest Metrics

Track in Inngest dashboard:
- Function success/failure rates
- Execution duration
- Step retries
- Event throughput

### Supabase Logs

Check `activity_logs` table for:
- Payment events
- Invoice status changes
- SLA breaches
- Contract lifecycle events

## Adding New Jobs

1. Create function file in `/lib/inngest/functions/`
2. Export function using `inngest.createFunction()`
3. Add to `/lib/inngest/functions/index.ts`
4. Deploy - Inngest will auto-discover

Example:

```typescript
// /lib/inngest/functions/my-job.ts
import { inngest } from "../client";

export const myJob = inngest.createFunction(
  { id: "my-job", name: "My Job" },
  { cron: "0 * * * *" }, // Hourly
  async ({ step }) => {
    await step.run("do-something", async () => {
      // Your logic here
    });
    
    return { success: true };
  }
);
```

## Troubleshooting

### Functions Not Running

1. Check Inngest dashboard for errors
2. Verify environment variables are set
3. Ensure API route is accessible: `GET /api/inngest`
4. Check cron schedule syntax

### Emails Not Sending

1. Verify Resend API key is valid
2. Check sender email domain is verified in Resend
3. View email logs in Resend dashboard
4. Ensure email templates exist in database

### Webhook Failures

1. Verify webhook secret matches Stripe
2. Check signature verification in logs
3. Test with Stripe CLI locally
4. Ensure endpoint returns 200 status

## Best Practices

1. **Keep steps small**: Each step should be a single unit of work
2. **Use idempotency**: Steps may retry, ensure they're safe to re-run
3. **Log everything**: Use console.log for debugging in Inngest dashboard
4. **Handle errors**: Wrap risky operations in try/catch
5. **Test locally**: Always test with Inngest Dev before deploying

## Migration from Laravel

### Laravel Queue → Inngest

| Laravel | Inngest |
|---------|---------|
| `dispatch(new Job)` | `inngest.send({ name: "event" })` |
| `Job::dispatch()->delay(60)` | `inngest.send({ name: "event", ts: Date.now() + 60000 })` |
| `->onQueue('high')` | Use separate functions with priority |
| `retry(3)` | Automatic with exponential backoff |

### Schedule → Cron

| Laravel | Inngest |
|---------|---------|
| `->daily()` | `{ cron: "0 0 * * *" }` |
| `->hourly()` | `{ cron: "0 * * * *" }` |
| `->dailyAt('9:00')` | `{ cron: "0 9 * * *" }` |
| `->everyFiveMinutes()` | `{ cron: "*/5 * * * *" }` |

