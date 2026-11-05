import { createHash } from "node:crypto";
import { NextResponse } from "next/server";
import { z } from "zod";
import { sql } from "drizzle-orm";
import { db } from "@/lib/db";
import { encrypt } from "@/lib/encryption";
import { marketingConnectionAccess } from "@/lib/marketing/connection-access";
import {
  metaMarketingConfig,
  metaGraphRead,
} from "@/lib/marketing/meta-connections";

const tokenResponse = z.object({
  access_token: z.string().min(10),
  expires_in: z
    .number()
    .positive()
    .max(366 * 86400),
});
export async function GET(request: Request) {
  const destination = new URL(
    "/marketing/connections",
    process.env.NEXT_PUBLIC_APP_URL,
  );
  try {
    const query = new URL(request.url).searchParams;
    const state = query.get("state");
    if (!state || state.length > 200) throw Error("Invalid state");
    const [saved] = await db.execute(
      sql`DELETE FROM marketing_oauth_states WHERE state_hash=${createHash("sha256").update(state).digest("hex")} AND provider='meta_ads' AND expires_at>now() RETURNING *`,
    );
    if (!saved) throw Error("Expired state");
    const access = await marketingConnectionAccess("meta_ads");
    if (
      !access ||
      access.userId !== saved.user_id ||
      access.clientId !== saved.client_id ||
      query.has("error") ||
      !query.get("code")
    )
      throw Error("Unauthorized");
    const config = metaMarketingConfig();
    if (!config.clientId || !config.clientSecret || !config.configId)
      throw Error("Not configured");
    async function exchange(values: Record<string, string>) {
      const response = await fetch(
        `https://graph.facebook.com/${config.version}/oauth/access_token`,
        {
          method: "POST",
          headers: { "Content-Type": "application/x-www-form-urlencoded" },
          body: new URLSearchParams({
            client_id: config.clientId!,
            client_secret: config.clientSecret!,
            ...values,
          }),
          signal: AbortSignal.timeout(20000),
          redirect: "error",
        },
      );
      if (!response.ok) throw Error("Exchange failed");
      return tokenResponse.parse(await response.json());
    }
    const short = await exchange({
      code: query.get("code")!,
      redirect_uri: config.callback,
    });
    const long = await exchange({
      grant_type: "fb_exchange_token",
      fb_exchange_token: short.access_token,
    });
    const permissions = z
      .object({
        data: z.array(z.object({ permission: z.string(), status: z.string() })),
      })
      .parse(await metaGraphRead(long.access_token, "me/permissions", {}));
    const granted = permissions.data
      .filter((p) => p.status === "granted")
      .map((p) => p.permission);
    if (
      !granted.includes("ads_read") ||
      granted.some((p) => !["ads_read", "public_profile"].includes(p))
    )
      throw Error("Unexpected permissions");
    await db.execute(
      sql`INSERT INTO marketing_connections(client_id,user_id,provider,access_token,refresh_token,expires_at) VALUES(${access.clientId}::uuid,${access.userId}::uuid,'meta_ads',${encrypt(long.access_token)},NULL,now()+${long.expires_in}*interval '1 second') ON CONFLICT(client_id,user_id,provider) DO UPDATE SET access_token=excluded.access_token,refresh_token=NULL,expires_at=excluded.expires_at,resource_id=NULL,updated_at=now()`,
    );
    destination.searchParams.set("connected", "meta_ads");
  } catch {
    destination.searchParams.set("error", "connection_failed");
  }
  return NextResponse.redirect(destination);
}
