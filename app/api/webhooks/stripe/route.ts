import { headers } from "next/headers";
import Stripe from "stripe";
import { inngest } from "@/lib/inngest/client";
import { dispatchNotification } from "@/lib/notifications/service";
import { createAdminClientIfAvailable, createClient } from "@/lib/supabase/server";
import { processStripeWebhookRequest } from "@/lib/webhooks/stripe-webhook-request";
import {
  apiError,
  apiInternalError,
  apiSuccess,
  errorCodeFromStatus,
} from "@/lib/api/response";

function getStripeConfig() {
  const secretKey = process.env.STRIPE_SECRET_KEY;
  const webhookSecret = process.env.STRIPE_WEBHOOK_SECRET;

  if (!secretKey || !webhookSecret) {
    return null;
  }

  return {
    stripe: new Stripe(secretKey, {
      apiVersion: "2023-10-16",
    }),
    webhookSecret,
  };
}

async function hasProcessedEvent(eventId: string) {
  const supabase = createAdminClientIfAvailable() ?? (await createClient());
  const { data } = await supabase
    .from("activity_logs")
    .select("id")
    .contains("properties", { stripe_event_id: eventId })
    .limit(1)
    .maybeSingle();

  return Boolean(data?.id);
}

async function logActivity(args: {
  subjectType: "invoice" | "client";
  subjectId: string;
  description: string;
  properties: Record<string, unknown>;
}) {
  const supabase = createAdminClientIfAvailable() ?? (await createClient());
  await supabase.from("activity_logs").insert({
    subject_type: args.subjectType,
    subject_id: args.subjectId,
    description: args.description,
    properties: args.properties,
  });
}

export async function POST(request: Request) {
  const config = getStripeConfig();
  if (!config) {
    return apiError(request, {
      status: 503,
      code: "SERVICE_UNAVAILABLE",
      message: "Stripe webhook is not configured",
    });
  }

  try {
    const body = await request.text();
    const headersList = await headers();
    const signature = headersList.get("stripe-signature");

    const result = await processStripeWebhookRequest({
      body,
      signature,
      webhookSecret: config.webhookSecret,
      constructEvent: (rawBody, rawSignature, secret) =>
        config.stripe.webhooks.constructEvent(rawBody, rawSignature, secret),
      hasProcessedEvent,
      onEvent: async (event) => {
        const supabase = createAdminClientIfAvailable() ?? (await createClient());

        switch (event.type) {
          case "payment_intent.succeeded": {
            const paymentIntent = event.data.object as Stripe.PaymentIntent;
            const invoiceId = paymentIntent.metadata.invoice_id;

            if (invoiceId) {
              await supabase
                .from("invoices")
                .update({
                  status: "paid",
                  paid_at: new Date().toISOString(),
                  payment_method: paymentIntent.payment_method_types[0],
                  stripe_payment_intent_id: paymentIntent.id,
                })
                .eq("id", invoiceId)
                .neq("status", "paid");

              await logActivity({
                subjectType: "invoice",
                subjectId: invoiceId,
                description: `Payment received: ${paymentIntent.amount / 100} ${paymentIntent.currency.toUpperCase()}`,
                properties: {
                  stripe_event_id: event.id,
                  stripe_event_type: event.type,
                  payment_intent_id: paymentIntent.id,
                  amount: paymentIntent.amount,
                  currency: paymentIntent.currency,
                },
              });

              await inngest.send({
                name: "invoice/payment.received",
                data: { invoiceId },
              });

              const { data: invoice } = await supabase
                .from("invoices")
                .select("id, invoice_number, client_id")
                .eq("id", invoiceId)
                .maybeSingle();

              if (invoice) {
                await dispatchNotification({
                  eventType: "invoice_paid",
                  clientId: invoice.client_id,
                  subjectType: "invoice",
                  subjectId: invoice.id,
                  data: {
                    invoiceNumber: invoice.invoice_number,
                    amount: `${(paymentIntent.amount / 100).toFixed(2)} ${paymentIntent.currency.toUpperCase()}`,
                  },
                });
              }
            }
            break;
          }

          case "payment_intent.payment_failed": {
            const paymentIntent = event.data.object as Stripe.PaymentIntent;
            const invoiceId = paymentIntent.metadata.invoice_id;

            if (invoiceId) {
              await logActivity({
                subjectType: "invoice",
                subjectId: invoiceId,
                description: "Payment failed",
                properties: {
                  stripe_event_id: event.id,
                  stripe_event_type: event.type,
                  payment_intent_id: paymentIntent.id,
                  error: paymentIntent.last_payment_error?.message,
                },
              });

              await inngest.send({
                name: "invoice/payment.failed",
                data: {
                  invoiceId,
                  error: paymentIntent.last_payment_error?.message,
                },
              });

              const { data: invoice } = await supabase
                .from("invoices")
                .select("id, invoice_number, client_id")
                .eq("id", invoiceId)
                .maybeSingle();

              if (invoice) {
                await dispatchNotification({
                  eventType: "invoice_payment_failed",
                  clientId: invoice.client_id,
                  subjectType: "invoice",
                  subjectId: invoice.id,
                  data: {
                    invoiceNumber: invoice.invoice_number,
                  },
                });
              }
            }
            break;
          }

          case "charge.refunded": {
            const charge = event.data.object as Stripe.Charge;
            const paymentIntentId = charge.payment_intent as string;

            if (paymentIntentId) {
              const { data: invoice } = await supabase
                .from("invoices")
                .select("id, invoice_number, client_id")
                .eq("stripe_payment_intent_id", paymentIntentId)
                .single();

              if (invoice) {
                await supabase
                  .from("invoices")
                  .update({
                    status: "refunded",
                    refunded_at: new Date().toISOString(),
                  })
                  .eq("id", invoice.id)
                  .neq("status", "refunded");

                await logActivity({
                  subjectType: "invoice",
                  subjectId: invoice.id,
                  description: `Payment refunded: ${charge.amount_refunded / 100} ${charge.currency.toUpperCase()}`,
                  properties: {
                    stripe_event_id: event.id,
                    stripe_event_type: event.type,
                    charge_id: charge.id,
                    amount_refunded: charge.amount_refunded,
                  },
                });

                await dispatchNotification({
                  eventType: "invoice_refunded",
                  clientId: invoice.client_id,
                  subjectType: "invoice",
                  subjectId: invoice.id,
                  data: {
                    invoiceNumber: invoice.invoice_number,
                    amount: `${(charge.amount_refunded / 100).toFixed(2)} ${charge.currency.toUpperCase()}`,
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
              await logActivity({
                subjectType: "client",
                subjectId: clientId,
                description: `Subscription ${event.type.split(".").pop()}: ${subscription.id}`,
                properties: {
                  stripe_event_id: event.id,
                  stripe_event_type: event.type,
                  subscription_id: subscription.id,
                  status: subscription.status,
                  current_period_end: subscription.current_period_end,
                },
              });

              await dispatchNotification({
                eventType: "subscription_updated",
                clientId,
                subjectType: "client",
                subjectId: clientId,
                data: {
                  subscriptionId: subscription.id,
                  status: subscription.status,
                },
              });
            }
            break;
          }

          default:
            console.log(`Unhandled event type: ${event.type}`);
        }
      },
    });

    if (result.status >= 400) {
      const message =
        typeof result.payload.error === "string"
          ? result.payload.error
          : "Webhook request failed";
      return apiError(request, {
        status: result.status,
        code: errorCodeFromStatus(result.status),
        message,
      });
    }

    return apiSuccess(request, result.payload, { extra: result.payload, status: result.status });
  } catch (error) {
    console.error("Webhook error:", error);
    return apiInternalError(
      request,
      error instanceof Error ? error.message : "Webhook processing failed",
    );
  }
}
