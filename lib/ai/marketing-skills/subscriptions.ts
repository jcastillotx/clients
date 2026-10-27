import type Stripe from "stripe";
import { sql } from "drizzle-orm";
import { db } from "@/lib/db";
import { createStripeClient } from "@/lib/stripe/client";
import { resolvePlatformStripeCredentials } from "@/lib/stripe/settings";

export function marketingSubscriptionActive(status: string) {
  return status === "active" || status === "trialing";
}
export async function syncMarketingSubscription(
  event: { type: string; data: { object: unknown } },
  matchedSecret: string | null,
) {
  const object = event.data.object as Stripe.Subscription;
  if (
    !event.type.startsWith("customer.subscription.") ||
    object.metadata?.workflow !== "marketing_plan"
  )
    return false;
  const credentials = await resolvePlatformStripeCredentials();
  if (
    !matchedSecret ||
    matchedSecret !== credentials.webhook_secret ||
    !credentials.secret_key
  )
    throw Error("Incorrect platform payment account");
  // Canonical retrieval prevents an out-of-order event from restoring expired access.
  const stripe = createStripeClient(credentials.secret_key);
  const subscription = await stripe.subscriptions.retrieve(object.id);
  const recordId = subscription.metadata.checkout_record_id;
  if (!recordId || !/^[\da-f-]{36}$/i.test(recordId))
    throw Error("Missing checkout record");
  const [record] = await db.execute(
    sql`SELECT * FROM marketing_checkout_sessions WHERE id=${recordId}::uuid`,
  );
  if (!record?.stripe_session_id) throw Error("Checkout record unavailable");
  const session = await stripe.checkout.sessions.retrieve(
    String(record.stripe_session_id),
  );
  const subId =
    typeof session.subscription === "string"
      ? session.subscription
      : session.subscription?.id;
  const customer =
    typeof subscription.customer === "string"
      ? subscription.customer
      : subscription.customer.id;
  if (
    subId !== subscription.id ||
    session.client_reference_id !== String(record.client_id)
  )
    throw Error("Checkout subscription mismatch");
  const price = subscription.items.data[0]?.price;
  if (
    subscription.items.data.length !== 1 ||
    price?.unit_amount !== Number(record.price_cents) ||
    price.currency !== record.currency
  )
    throw Error("Subscription price mismatch");
  const status = marketingSubscriptionActive(subscription.status)
    ? "active"
    : subscription.status === "canceled"
      ? "cancelled"
      : "paused";
  await db.execute(sql`INSERT INTO client_marketing_plans(client_id,plan_id,status,ends_at,stripe_subscription_id,stripe_customer_id,cancel_at_period_end)
 VALUES(${record.client_id}::uuid,${record.plan_id}::uuid,${status},to_timestamp(${subscription.current_period_end}),${subscription.id},${customer},${subscription.cancel_at_period_end})
 ON CONFLICT(client_id) DO UPDATE SET plan_id=excluded.plan_id,status=excluded.status,ends_at=excluded.ends_at,stripe_subscription_id=excluded.stripe_subscription_id,stripe_customer_id=excluded.stripe_customer_id,cancel_at_period_end=excluded.cancel_at_period_end,updated_at=now()
 WHERE client_marketing_plans.stripe_subscription_id=excluded.stripe_subscription_id OR client_marketing_plans.status='cancelled'`);
  await db.execute(
    sql`UPDATE marketing_checkout_sessions SET stripe_subscription_id=${subscription.id} WHERE id=${recordId}::uuid`,
  );
  return true;
}
