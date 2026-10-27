import { resolvePlatformStripeCredentials } from "@/lib/stripe/settings";
import { z } from "zod";
import { sql } from "drizzle-orm";
import { db } from "@/lib/db";
import { resolveStaffAccess } from "@/lib/api/resolve-staff-access";
import {
  apiSuccess,
  apiUnauthorized,
  apiForbidden,
  apiValidationError,
  apiInternalError,
} from "@/lib/api/response";
import { marketingToolGroups } from "@/lib/ai/marketing-skills/menu";
import { getClientMarketingPlan } from "@/lib/ai/marketing-skills/plans";

export async function GET(request: Request) {
  const access = await resolveStaffAccess();
  if (!access) return apiUnauthorized(request);
  try {
    const plans = await db.execute(
      sql`SELECT id,name,tool_groups,monthly_price_cents,currency,published,is_active FROM marketing_plans WHERE is_active AND (${access.isAdmin} OR published) ORDER BY monthly_price_cents`,
    );
    const subscription = access.clientId
      ? await getClientMarketingPlan(access.clientId)
      : null;
    const [billing] = access.clientId
      ? await db.execute(
          sql`SELECT status, cancel_at_period_end, ends_at FROM client_marketing_plans WHERE client_id=${access.clientId}::uuid AND stripe_subscription_id IS NOT NULL`,
        )
      : [];
    const credentials = await resolvePlatformStripeCredentials();
    return apiSuccess(request, {
      billingMode: credentials.secret_key?.startsWith("sk_test_")
        ? "test"
        : credentials.secret_key?.startsWith("sk_live_")
          ? "live"
          : "unconfigured",
      billing: billing ?? null,
      plans,
      subscription,
      groups: marketingToolGroups,
      isAdmin: access.isAdmin,
    });
  } catch {
    return apiInternalError(request, "Unable to load marketing plans.");
  }
}
const updateSchema = z.object({
  id: z.string().uuid(),
  name: z.string().trim().min(1).max(100),
  monthlyPriceCents: z.number().int().min(100).max(1000000),
  groups: z
    .array(
      z.enum(marketingToolGroups.map((g) => g.id) as [string, ...string[]]),
    )
    .min(1)
    .max(9),
  published: z.boolean(),
});
export async function PATCH(request: Request) {
  const access = await resolveStaffAccess();
  if (!access) return apiUnauthorized(request);
  if (!access.isAdmin) return apiForbidden(request);
  try {
    const parsed = updateSchema.safeParse(await request.json());
    if (!parsed.success) return apiValidationError(request, parsed.error);
    const p = parsed.data;
    await db.execute(
      sql`UPDATE marketing_plans SET name=${p.name},monthly_price_cents=${p.monthlyPriceCents},tool_groups=${JSON.stringify(p.groups)}::jsonb,published=${p.published},updated_at=now() WHERE id=${p.id}::uuid`,
    );
    return apiSuccess(request, { updated: true });
  } catch {
    return apiInternalError(request, "Unable to update marketing plan.");
  }
}
