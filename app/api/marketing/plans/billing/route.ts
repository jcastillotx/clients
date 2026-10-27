import { sql } from "drizzle-orm";
import { db } from "@/lib/db";
import { resolveStaffAccess } from "@/lib/api/resolve-staff-access";
import {
  apiSuccess,
  apiUnauthorized,
  apiForbidden,
  apiInternalError,
} from "@/lib/api/response";
import { resolvePlatformStripeCredentials } from "@/lib/stripe/settings";
import { createStripeClient } from "@/lib/stripe/client";
export async function POST(request: Request) {
  const access = await resolveStaffAccess();
  if (!access) return apiUnauthorized(request);
  if (!access.clientId) return apiForbidden(request);
  try {
    const [row] = await db.execute(
      sql`SELECT stripe_customer_id FROM client_marketing_plans WHERE client_id=${access.clientId}::uuid`,
    );
    if (!row?.stripe_customer_id)
      return apiForbidden(
        request,
        "No billing subscription is linked to this account.",
      );
    const credentials = await resolvePlatformStripeCredentials();
    if (!credentials.secret_key) throw Error("unconfigured");
    const session = await createStripeClient(
      credentials.secret_key,
    ).billingPortal.sessions.create({
      customer: String(row.stripe_customer_id),
      return_url: new URL(
        "/marketing/plans",
        process.env.NEXT_PUBLIC_APP_URL,
      ).toString(),
    });
    return apiSuccess(request, { url: session.url });
  } catch {
    return apiInternalError(request, "Unable to open billing management.");
  }
}
