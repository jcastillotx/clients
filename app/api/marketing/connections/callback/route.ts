import { createHash } from "node:crypto";
import { NextResponse } from "next/server";
import { sql } from "drizzle-orm";
import { db } from "@/lib/db";
import { encrypt, decrypt } from "@/lib/encryption";
import { marketingConnectionAccess } from "@/lib/marketing/connection-access";
import {
  googleMarketingProviders,
  googleMarketingConfig,
  googleMarketingScopes,
  type GoogleMarketingProvider,
} from "@/lib/marketing/google-connections";
export async function GET(request: Request) {
  const destination = new URL(
    "/marketing/connections",
    process.env.NEXT_PUBLIC_APP_URL,
  );
  try {
    const q = new URL(request.url).searchParams,
      state = q.get("state");
    if (!state || state.length > 200) throw Error("invalid");
    const [saved] = await db.execute(
      sql`DELETE FROM marketing_oauth_states WHERE state_hash=${createHash("sha256").update(state).digest("hex")} AND expires_at>now() RETURNING *`,
    );
    if (
      !saved ||
      !googleMarketingProviders.includes(
        saved.provider as GoogleMarketingProvider,
      )
    )
      throw Error("expired");
    const provider = saved.provider as GoogleMarketingProvider,
      access = await marketingConnectionAccess(provider);
    if (
      !access ||
      access.userId !== saved.user_id ||
      access.clientId !== saved.client_id ||
      q.has("error") ||
      !q.get("code")
    )
      throw Error("unauthorized");
    const config = googleMarketingConfig();
    const r = await fetch("https://oauth2.googleapis.com/token", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: new URLSearchParams({
        client_id: config.clientId!,
        client_secret: config.clientSecret!,
        redirect_uri: config.callback,
        code: q.get("code")!,
        code_verifier: decrypt(String(saved.verifier)),
        grant_type: "authorization_code",
      }),
      signal: AbortSignal.timeout(20000),
    });
    if (!r.ok) throw Error("exchange");
    const body = await r.json();
    if (
      typeof body.refresh_token !== "string" ||
      typeof body.access_token !== "string" ||
      !String(body.scope || "")
        .split(" ")
        .includes(googleMarketingScopes[provider])
    )
      throw Error("scope");
    await db.execute(
      sql`INSERT INTO marketing_connections(client_id,user_id,provider,access_token,refresh_token,expires_at) VALUES(${access.clientId}::uuid,${access.userId}::uuid,${provider},${encrypt(body.access_token)},${body.refresh_token ? encrypt(body.refresh_token) : null},now()+${Number(body.expires_in) || 3600}*interval '1 second') ON CONFLICT(client_id,user_id,provider) DO UPDATE SET access_token=excluded.access_token,refresh_token=excluded.refresh_token,expires_at=excluded.expires_at,resource_id=NULL,updated_at=now()`,
    );
    destination.searchParams.set("connected", provider);
  } catch {
    destination.searchParams.set("error", "connection_failed");
  }
  return NextResponse.redirect(destination);
}
