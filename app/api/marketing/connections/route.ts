import {
  marketingProviders,
  marketingProviderGroup,
} from "@/lib/marketing/providers";
import {
  metaMarketingConfig,
  metaMarketingToken,
  metaMarketingResources,
} from "@/lib/marketing/meta-connections";
import { z } from "zod";
import { sql } from "drizzle-orm";
import { db } from "@/lib/db";
import {
  apiUnauthorized,
  apiSuccess,
  apiForbidden,
  apiValidationError,
  apiInternalError,
} from "@/lib/api/response";
import { resolveStaffAccess } from "@/lib/api/resolve-staff-access";
import { marketingConnectionAccess } from "@/lib/marketing/connection-access";
import {
  googleMarketingResources,
  googleMarketingToken,
  googleMarketingConfig,
} from "@/lib/marketing/google-connections";
export async function GET(request: Request) {
  try {
    const access = await resolveStaffAccess();
    if (!access) return apiUnauthorized(request);
    if (!access.clientId) return apiForbidden(request);
    const entitled = await marketingConnectionAccess();
    const rows = await db.execute(
      sql`SELECT provider,resource_id,updated_at FROM marketing_connections WHERE client_id=${access.clientId}::uuid AND user_id=${access.userId}::uuid`,
    );
    const config = googleMarketingConfig();
    return apiSuccess(request, {
      configured: Boolean(config.clientId && config.clientSecret),
      providers: marketingProviders
        .filter(
          (p) =>
            entitled?.plan.groups.includes(marketingProviderGroup(p)) ||
            rows.some((r) => r.provider === p),
        )
        .map((provider) => ({
          provider,
          configured:
            provider === "meta_ads"
              ? Boolean(
                  metaMarketingConfig().clientId &&
                  metaMarketingConfig().clientSecret &&
                  metaMarketingConfig().configId,
                )
              : Boolean(config.clientId && config.clientSecret),
          permitted: Boolean(
            entitled?.plan.groups.includes(marketingProviderGroup(provider)),
          ),
          connection: rows.find((r) => r.provider === provider) || null,
        })),
    });
  } catch {
    return apiInternalError(request, "Unable to load connections.");
  }
}
const schema = z.object({
  provider: z.enum(marketingProviders),
  resourceId: z.string().max(2048).optional(),
});
export async function POST(request: Request) {
  if (!(await resolveStaffAccess())) return apiUnauthorized(request);
  try {
    const p = schema.safeParse(await request.json());
    if (!p.success) return apiValidationError(request, p.error);
    const access = await marketingConnectionAccess(p.data.provider);
    if (!access) return apiForbidden(request);
    const { token } =
      p.data.provider === "meta_ads"
        ? await metaMarketingToken(access.clientId, access.userId)
        : await googleMarketingToken(
            access.clientId,
            access.userId,
            p.data.provider,
          );
    const resources =
      p.data.provider === "meta_ads"
        ? await metaMarketingResources(token)
        : await googleMarketingResources(token, p.data.provider);
    if (p.data.resourceId) {
      if (!resources.some((r) => r.id === p.data.resourceId))
        return apiForbidden(
          request,
          "Select a resource available to your connected Google account.",
        );
      await db.execute(
        sql`UPDATE marketing_connections SET resource_id=${p.data.resourceId},updated_at=now() WHERE client_id=${access.clientId}::uuid AND user_id=${access.userId}::uuid AND provider=${p.data.provider}`,
      );
    }
    return apiSuccess(request, { resources });
  } catch {
    return apiInternalError(
      request,
      "Unable to load Google accounts. Reconnect or ask your account manager to check API access.",
    );
  }
}
export async function DELETE(request: Request) {
  const access = await resolveStaffAccess();
  if (!access) return apiUnauthorized(request);
  try {
    const p = schema.safeParse(await request.json());
    if (!p.success) return apiValidationError(request, p.error);
    // Disconnect remains available after a plan expires.
    if (!access.clientId) return apiForbidden(request);
    await db.execute(
      sql`DELETE FROM marketing_connections WHERE client_id=${access.clientId}::uuid AND user_id=${access.userId}::uuid AND provider=${p.data.provider}`,
    );
    return apiSuccess(request, { disconnected: true });
  } catch {
    return apiInternalError(request, "Unable to disconnect.");
  }
}
