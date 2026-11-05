import { createHmac } from "node:crypto";
import { z } from "zod";
import { sql } from "drizzle-orm";
import { db } from "@/lib/db";
import { decrypt } from "@/lib/encryption";

export function metaMarketingConfig() {
  const version = process.env.META_MARKETING_API_VERSION || "v26.0";
  if (!/^v\d+\.0$/.test(version)) throw Error("Invalid Meta API version");
  return {
    clientId: process.env.META_MARKETING_CLIENT_ID,
    clientSecret: process.env.META_MARKETING_CLIENT_SECRET,
    configId: process.env.META_MARKETING_LOGIN_CONFIG_ID,
    version,
    callback: new URL(
      "/api/marketing/connections/meta/callback",
      process.env.NEXT_PUBLIC_APP_URL,
    ).toString(),
  };
}
export async function metaMarketingToken(clientId: string, userId: string) {
  const [row] = await db.execute(
    sql`SELECT * FROM marketing_connections WHERE client_id=${clientId}::uuid AND user_id=${userId}::uuid AND provider='meta_ads'`,
  );
  if (
    !row ||
    !row.expires_at ||
    new Date(String(row.expires_at)).getTime() < Date.now() + 60000
  )
    throw Error("Reconnect your Meta account.");
  // Meta user tokens have no refresh token. Never silently fall back to an
  // agency/system token; the same user must authorize again on expiration.
  return { token: decrypt(String(row.access_token)), row };
}
export async function metaGraphRead(
  token: string,
  path: string,
  params: Record<string, string>,
) {
  if (!/^(me\/(adaccounts|permissions)|act_\d+\/insights)$/.test(path))
    throw Error("Invalid Meta endpoint");
  const config = metaMarketingConfig();
  if (!config.clientSecret) throw Error("Meta not configured");
  const url = new URL(`https://graph.facebook.com/${config.version}/${path}`);
  url.search = new URLSearchParams({
    ...params,
    appsecret_proof: createHmac("sha256", config.clientSecret)
      .update(token)
      .digest("hex"),
  }).toString();
  const response = await fetch(url, {
    headers: { Authorization: "Bearer " + token },
    signal: AbortSignal.timeout(30000),
    redirect: "error",
  });
  if (!response.ok) throw Error("Meta reporting unavailable");
  const body: unknown = await response.json();
  if (!body || typeof body !== "object" || "error" in body)
    throw Error("Meta reporting unavailable");
  return body;
}
const accounts = z.object({
  data: z
    .array(z.object({ id: z.string().regex(/^act_\d+$/), name: z.string() }))
    .max(100),
  paging: z
    .object({
      cursors: z.object({ after: z.string().max(2048).optional() }).optional(),
      next: z.string().optional(),
    })
    .optional(),
});
export async function metaMarketingResources(token: string) {
  const resources: Array<{ id: string; name: string }> = [];
  let after = "";
  for (let page = 0; page < 10; page++) {
    const body = accounts.parse(
      await metaGraphRead(token, "me/adaccounts", {
        fields: "id,name",
        limit: "100",
        ...(after ? { after } : {}),
      }),
    );
    resources.push(...body.data);
    if (!body.paging?.next) return resources;
    // Ignore the provider-supplied next URL, which may contain a token. Follow
    // only a bounded cursor against the fixed, authorized Meta origin/path.
    const next = body.paging.cursors?.after;
    if (!next || next === after) throw Error("Invalid Meta pagination");
    after = next;
  }
  throw Error(
    "Connect an account with fewer than 1000 accessible ad accounts.",
  );
}
export const metaAdsReport = z
  .object({
    data: z
      .array(
        z.object({
          account_id: z.string().regex(/^\d+$/),
          account_currency: z.string().optional(),
          impressions: z.string().regex(/^\d+$/).optional(),
          clicks: z.string().regex(/^\d+$/).optional(),
          spend: z
            .string()
            .regex(/^\d+(\.\d+)?$/)
            .optional(),
          date_start: z.string(),
          date_stop: z.string(),
        }),
      )
      .max(1),
  })
  .passthrough();
export async function metaMarketingReport(token: string, resource: string) {
  if (!/^act_\d+$/.test(resource))
    throw Error("Select a Meta ad account first.");
  const body = metaAdsReport.parse(
    await metaGraphRead(token, `${resource}/insights`, {
      fields:
        "account_id,account_currency,impressions,clicks,spend,date_start,date_stop",
      date_preset: "last_7d",
      level: "account",
      limit: "1",
    }),
  );
  if (body.data.some((row) => "act_" + row.account_id !== resource))
    throw Error("Meta account mismatch");
  return {
    data: body.data,
    dataStatus: body.data.length ? ("data" as const) : ("empty" as const),
  };
}
