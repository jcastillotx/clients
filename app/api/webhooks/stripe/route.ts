import { headers } from "next/headers";
import { NextResponse } from "next/server";
import Stripe from "stripe";
import { createClient } from "@/lib/supabase/server";
import { inngest } from "@/lib/inngest/client";

const stripe = new Stripe(process.env.STRIPE_SECRET_KEY!, {
  apiVersion: "2024-11-20.acacia" as any,
});

const webhookSecret = process.env.STRIPE_WEBHOOK_SECRET!;

export async function POST(req: Request) {
  try {
    const body = await req.text();
    const headersList = await headers();
    const signature = headersList.get("stripe-signature");

    if (!signature) {
      return NextResponse.json({ error: "Missing signature" }, { status: 400 });
    }

    // Verify webhook signature
    let event: Stripe.Event;
    try {
      event = stripe.webhooks.constructEvent(body, signature, webhookSecret);
    } catch (err) {
      console.error("Webhook signature verification failed:", err);
      return NextResponse.json({ error: "Invalid signature" }, { status: 400 });
    }

    const supabase = await createClient();

    // Handle different event types
    switch (event.type) {
      case "payment_intent.succeeded": {
        const paymentIntent = event.data.object as Stripe.PaymentIntent;
        const invoiceId = paymentIntent.metadata.invoice_id;

        if (invoiceId) {
          // Update invoice status
          await supabase
            .from("invoices")
            .update({
              status: "paid",
              paid_at: new Date().toISOString(),
              payment_method: paymentIntent.payment_method_types[0],
              stripe_payment_intent_id: paymentIntent.id,
            })
            .eq("id", invoiceId);

          // Log activity
          await supabase.from("activity_logs").insert({
            subject_type: "invoice",
            subject_id: invoiceId,
            description: `Payment received: ${paymentIntent.amount / 100} ${paymentIntent.currency.toUpperCase()}`,
            properties: {
              payment_intent_id: paymentIntent.id,
              amount: paymentIntent.amount,
              currency: paymentIntent.currency,
            },
          });

          // Trigger thank you email
          await inngest.send({
            name: "invoice/payment.received",
            data: { invoiceId },
          });
        }
        break;
      }

      case "payment_intent.payment_failed": {
        const paymentIntent = event.data.object as Stripe.PaymentIntent;
        const invoiceId = paymentIntent.metadata.invoice_id;

        if (invoiceId) {
          // Log failed payment
          await supabase.from("activity_logs").insert({
            subject_type: "invoice",
            subject_id: invoiceId,
            description: "Payment failed",
            properties: {
              payment_intent_id: paymentIntent.id,
              error: paymentIntent.last_payment_error?.message,
            },
          });

          // Send failure notification
          await inngest.send({
            name: "invoice/payment.failed",
            data: {
              invoiceId,
              error: paymentIntent.last_payment_error?.message,
            },
          });
        }
        break;
      }

      case "charge.refunded": {
        const charge = event.data.object as Stripe.Charge;
        const paymentIntentId = charge.payment_intent as string;

        if (paymentIntentId) {
          // Find invoice by payment intent
          const { data: invoice } = await supabase
            .from("invoices")
            .select("id")
            .eq("stripe_payment_intent_id", paymentIntentId)
            .single();

          if (invoice) {
            await supabase
              .from("invoices")
              .update({
                status: "refunded",
                refunded_at: new Date().toISOString(),
              })
              .eq("id", invoice.id);

            await supabase.from("activity_logs").insert({
              subject_type: "invoice",
              subject_id: invoice.id,
              description: `Payment refunded: ${charge.amount_refunded / 100} ${charge.currency.toUpperCase()}`,
              properties: {
                charge_id: charge.id,
                amount_refunded: charge.amount_refunded,
              },
            });
          }
        }
        break;
      }

      case "customer.subscription.created":
      case "customer.subscription.updated":
      case "customer.subscription.deleted": {
        const subscription = event.data.object as Stripe.Subscription;
        const clientId = subscription.metadata.client_id;

        if (clientId) {
          await supabase.from("activity_logs").insert({
            subject_type: "client",
            subject_id: clientId,
            description: `Subscription ${event.type.split(".").pop()}: ${subscription.id}`,
            properties: {
              subscription_id: subscription.id,
              status: subscription.status,
              current_period_end: subscription.current_period_end,
            },
          });
        }
        break;
      }

      default:
        console.log(`Unhandled event type: ${event.type}`);
    }

    return NextResponse.json({ received: true });
  } catch (error) {
    console.error("Webhook error:", error);
    return NextResponse.json(
      {
        error: error instanceof Error ? error.message : "Webhook processing failed",
      },
      { status: 500 },
    );
  }
}
