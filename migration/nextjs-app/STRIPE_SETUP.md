# Stripe Payment Integration Setup

## Overview

The Next.js application now includes full Stripe payment integration for invoices. This document explains how to set up and use the payment system.

## Prerequisites

1. **Stripe Account**: Sign up at https://stripe.com
2. **Stripe API Keys**: Get your test keys from the Stripe Dashboard

## Environment Variables

Add the following to your `.env.local` file:

```bash
# Stripe Configuration
NEXT_PUBLIC_STRIPE_PUBLISHABLE_KEY=pk_test_your_key_here
STRIPE_SECRET_KEY=sk_test_your_key_here
STRIPE_WEBHOOK_SECRET=whsec_your_webhook_secret_here
```

## Database Schema Updates

Ensure your `invoices` table has these columns:

```sql
ALTER TABLE invoices ADD COLUMN IF NOT EXISTS stripe_payment_intent_id TEXT;
ALTER TABLE clients ADD COLUMN IF NOT EXISTS stripe_customer_id TEXT;
```

## Webhook Setup

### 1. Local Development (Stripe CLI)

Install the Stripe CLI:

```bash
brew install stripe/stripe-cli/stripe
```

Login to Stripe:

```bash
stripe login
```

Forward webhooks to your local server:

```bash
stripe listen --forward-to localhost:3000/api/webhooks/stripe
```

Copy the webhook signing secret (starts with `whsec_`) to your `.env.local` file.

### 2. Production Deployment

1. Go to https://dashboard.stripe.com/webhooks
2. Click "Add endpoint"
3. Set the endpoint URL to: `https://yourdomain.com/api/webhooks/stripe`
4. Select these events to listen for:
   - `payment_intent.succeeded`
   - `payment_intent.payment_failed`
   - `payment_intent.canceled`
   - `customer.created`
   - `customer.updated`
5. Copy the webhook signing secret to your production environment variables

## Payment Flow

### 1. Invoice Creation

- Create invoice through the dashboard
- Invoice starts in `draft` status

### 2. Send Invoice

- Click "Send Invoice" button
- Status changes to `sent`
- "Pay Invoice" button appears

### 3. Payment Processing

- Click "Pay Invoice" button
- Payment modal opens with Stripe Elements
- Customer enters card details
- Payment is processed via Stripe Payment Intent

### 4. Payment Confirmation

- On success: Webhook updates invoice status to `paid`
- On failure: Error message shown, invoice remains `sent`
- Customer can retry payment

## Testing

### Test Cards

Use Stripe's test cards for development:

```
Success: 4242 4242 4242 4242
Decline: 4000 0000 0000 0002
3D Secure: 4000 0027 6000 3184
```

Any future expiry date (e.g., 12/34) and any 3-digit CVC work.

### Test Flow

1. Create a test invoice
2. Set amount to $10.00
3. Send the invoice
4. Click "Pay Invoice"
5. Use test card 4242 4242 4242 4242
6. Complete payment
7. Verify webhook received and invoice marked as paid

## Security Features

### ✅ Payment Intent API

- Prevents overcharging
- Server-side amount validation
- Idempotent payment processing

### ✅ Webhook Signature Verification

- Validates webhook authenticity
- Prevents replay attacks
- Ensures data integrity

### ✅ Customer Association

- Automatic Stripe customer creation
- Links payments to client records
- Enables saved payment methods (future enhancement)

## Architecture

### API Routes

**POST `/api/payments/create-intent`**

- Creates Stripe Payment Intent
- Associates with invoice
- Creates Stripe customer if needed
- Returns client secret for frontend

**POST `/api/webhooks/stripe`**

- Receives Stripe webhook events
- Verifies webhook signature
- Updates invoice status based on payment events

### Components

**`PaymentModal`** - Dialog component with Stripe Elements

- Loads payment intent client secret
- Displays payment form
- Handles payment submission
- Shows loading and error states

**`InvoiceActions`** - Updated with "Pay Invoice" button

- Triggers payment modal
- Shows appropriate actions based on invoice status

### Database Updates

**`invoices` table**

- `stripe_payment_intent_id`: Links invoice to Stripe payment
- `paid_at`: Timestamp when payment succeeded
- `status`: Updated via webhook

**`clients` table**

- `stripe_customer_id`: Links client to Stripe customer
- Enables future features (saved cards, subscriptions)

## Supported Payment Methods

Currently configured for:

- Credit cards (Visa, Mastercard, Amex, etc.)
- Debit cards

Can be extended to support:

- ACH Direct Debit
- Google Pay / Apple Pay
- SEPA Direct Debit
- And more (see Stripe documentation)

## Error Handling

### Payment Failures

- Declined cards show user-friendly error messages
- Invoice status remains `sent` for retry
- Activity log can track failed attempts

### Webhook Failures

- Stripe automatically retries failed webhooks
- Monitor webhook health in Stripe Dashboard
- Set up alerts for webhook failures

## Future Enhancements

### Phase 1 (Current)

- ✅ One-time invoice payments
- ✅ Automatic status updates via webhooks
- ✅ Customer creation and association

### Phase 2 (Planned)

- [ ] Saved payment methods
- [ ] Recurring invoice subscriptions
- [ ] Payment method management UI
- [ ] Refund processing
- [ ] Partial payments

### Phase 3 (Future)

- [ ] Payment plans (installments)
- [ ] Multiple payment methods per invoice
- [ ] Payment reminders via email
- [ ] Customer portal for payment history

## Troubleshooting

### "Invalid client secret" Error

- Check that payment intent was created successfully
- Verify STRIPE_SECRET_KEY is correct
- Ensure invoice ID is valid

### Webhook Not Receiving Events

- Verify webhook endpoint is accessible
- Check STRIPE_WEBHOOK_SECRET matches
- Review Stripe Dashboard webhook logs
- Ensure webhook events are selected

### Payment Succeeds but Invoice Not Updated

- Check webhook is configured correctly
- Verify Supabase connection in webhook handler
- Review server logs for errors
- Check invoice ID in payment metadata

## Monitoring

### Stripe Dashboard

- View all payments and customers
- Monitor webhook deliveries
- Review payment failures
- Track revenue analytics

### Application Logs

- Payment intent creation
- Webhook processing
- Invoice status updates
- Error logs for debugging

## Resources

- [Stripe Documentation](https://stripe.com/docs)
- [Stripe Elements](https://stripe.com/docs/stripe-js)
- [Payment Intents](https://stripe.com/docs/payments/payment-intents)
- [Webhooks Guide](https://stripe.com/docs/webhooks)
- [Testing Cards](https://stripe.com/docs/testing)
