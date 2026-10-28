import { randomUUID } from "node:crypto";
import { z } from "zod";
import { sql } from "drizzle-orm";
import { db } from "@/lib/db";
import { resolveStaffAccess } from "@/lib/api/resolve-staff-access";
import {
  apiSuccess,
  apiUnauthorized,
  apiForbidden,
  apiError,
  apiValidationError,
  apiInternalError,
} from "@/lib/api/response";
import { resolvePlatformStripeCredentials } from "@/lib/stripe/settings";
import { createStripeClient } from "@/lib/stripe/client";
import { rateLimit } from "@/lib/rate-limit";
const schema = z.object({ planId: z.string().guid() });
export async function POST(request: Request) {
  const access = await resolveStaffAccess();
  if (!access) return apiUnauthorized(request);
  if (!access.clientId) return apiForbidden(request);
  try {
    const parsed = schema.safeParse(await request.json());
    if (!parsed.success) return apiValidationError(request, parsed.error);
    const limited = await rateLimit(
      access.clientId,
      { limit: 5, windowMs: 3600000 },
      "marketing-checkout",
    );
    if (!limited.success)
      return apiError(request, {
        status: 429,
        code: "RATE_LIMITED",
        message: "Please wait before creating another checkout.",
      });
    const [plan] = await db.execute(
      sql`SELECT * FROM marketing_plans WHERE id=${parsed.data.planId}::uuid AND published AND is_active`,
    );
    if (!plan)
      return apiForbidden(request, "This plan is not available for purchase.");
    const [existing] = await db.execute(
      sql`SELECT client_id FROM client_marketing_plans WHERE client_id=${access.clientId}::uuid AND stripe_subscription_id IS NOT NULL AND status <> 'cancelled'`,
    );
    if (existing)
      return apiError(request, {
        status: 409,
        code: "BAD_REQUEST",
        message:
          "Manage your existing subscription before purchasing another plan.",
      });
    const credentials = await resolvePlatformStripeCredentials();
    if (!credentials.secret_key)
      return apiError(request, {
        status: 503,
        code: "SERVICE_UNAVAILABLE",
        message: "Plan billing is not configured.",
      });
    const stripe = createStripeClient(credentials.secret_key),
      id = randomUUID();
    const [recent] = await db.execute(
      sql`SELECT stripe_session_id FROM marketing_checkout_sessions WHERE client_id=${access.clientId}::uuid AND plan_id=${plan.id}::uuid AND stripe_subscription_id IS NULL AND stripe_session_id IS NOT NULL AND created_at>now()-interval '31 minutes' ORDER BY created_at DESC LIMIT 1`,
    );
    if (recent) {
      const pending = await stripe.checkout.sessions.retrieve(
        String(recent.stripe_session_id),
      );
      if (
        pending.status === "open" &&
        pending.client_reference_id === access.clientId &&
        pending.url
      )
        return apiSuccess(request, { url: pending.url });
    }
    // Reserve locally before creating the Stripe object; only our signed webhook can activate access.
    const reserved = await db.transaction(async (tx) => {
      await tx.execute(
        sql`SELECT pg_advisory_xact_lock(hashtext(${access.clientId}))`,
      );
      const [pending] = await tx.execute(
        sql`SELECT id FROM marketing_checkout_sessions WHERE client_id=${access.clientId}::uuid AND stripe_subscription_id IS NULL AND created_at > now()-interval '31 minutes' LIMIT 1`,
      );
      const [active] = await tx.execute(
        sql`SELECT client_id FROM client_marketing_plans WHERE client_id=${access.clientId}::uuid AND stripe_subscription_id IS NOT NULL AND status <> 'cancelled'`,
      );
      if (pending || active) return false;
      await tx.execute(
        sql`INSERT INTO marketing_checkout_sessions(id,client_id,plan_id,price_cents,currency) VALUES(${id}::uuid,${access.clientId}::uuid,${plan.id}::uuid,${plan.monthly_price_cents},${plan.currency})`,
      );
      return true;
    });
    if (!reserved)
      return apiError(request, {
        status: 409,
        code: "BAD_REQUEST",
        message:
          "An active subscription or recent checkout already exists. Complete that checkout or wait 31 minutes before trying again.",
      });
    const origin = new URL(process.env.NEXT_PUBLIC_APP_URL!).origin;
    const session = await stripe.checkout.sessions.create(
      {
        expires_at: Math.floor(Date.now() / 1000) + 1800,
        mode: "subscription",
        client_reference_id: access.clientId,
        line_items: [
          {
            quantity: 1,
            price_data: {
              currency: String(plan.currency),
              unit_amount: Number(plan.monthly_price_cents),
              recurring: { interval: "month" },
              product_data: { name: String(plan.name) },
            },
          },
        ],
        subscription_data: {
          metadata: { workflow: "marketing_plan", checkout_record_id: id },
        },
        metadata: { workflow: "marketing_plan", checkout_record_id: id },
        success_url: origin + "/marketing/plans?checkout=complete",
        cancel_url: origin + "/marketing/plans?checkout=cancelled",
      },
      { idempotencyKey: "marketing-plan-" + id },
    );
    await db.execute(
      sql`UPDATE marketing_checkout_sessions SET stripe_session_id=${session.id} WHERE id=${id}::uuid`,
    );
    return apiSuccess(request, { url: session.url });
  } catch {
    return apiInternalError(request, "Unable to start checkout.");
  }
}
