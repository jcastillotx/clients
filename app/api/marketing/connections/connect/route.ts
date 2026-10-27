import { marketingProviders } from "@/lib/marketing/providers";
import { metaMarketingConfig } from "@/lib/marketing/meta-connections";
import { resolveStaffAccess } from "@/lib/api/resolve-staff-access";
import { randomBytes, createHash } from "node:crypto";
import { z } from "zod";
import { sql } from "drizzle-orm";
import { db } from "@/lib/db";
import { encrypt } from "@/lib/encryption";
import {
  apiUnauthorized,
  apiSuccess,
  apiForbidden,
  apiValidationError,
  apiInternalError,
} from "@/lib/api/response";
import { marketingConnectionAccess } from "@/lib/marketing/connection-access";
import {
  googleMarketingConfig,
  googleMarketingScopes,
} from "@/lib/marketing/google-connections";
export async function POST(request: Request) {
  if (!(await resolveStaffAccess())) return apiUnauthorized(request);
  try {
    const p = z
      .object({ provider: z.enum(marketingProviders) })
      .safeParse(await request.json());
    if (!p.success) return apiValidationError(request, p.error);
    const access = await marketingConnectionAccess(p.data.provider);
    if (!access)
      return apiForbidden(
        request,
        "Your active marketing plan must include this connection.",
      );
    if (p.data.provider === "meta_ads") {
      const config = metaMarketingConfig();
      if (!config.clientId || !config.clientSecret || !config.configId)
        return apiInternalError(
          request,
          "Meta marketing connections are not configured.",
        );
      const state = randomBytes(32).toString("base64url");
      await db.execute(
        sql`DELETE FROM marketing_oauth_states WHERE expires_at < now()`,
      );
      await db.execute(
        sql`INSERT INTO marketing_oauth_states(state_hash,user_id,client_id,provider,verifier,expires_at) VALUES(${createHash("sha256").update(state).digest("hex")},${access.userId}::uuid,${access.clientId}::uuid,'meta_ads',${encrypt(randomBytes(32).toString("base64url"))},now()+interval '10 minutes')`,
      );
      const url = new URL(
        `https://www.facebook.com/${config.version}/dialog/oauth`,
      );
      url.search = new URLSearchParams({
        client_id: config.clientId,
        config_id: config.configId,
        redirect_uri: config.callback,
        response_type: "code",
        state,
      }).toString();
      return apiSuccess(request, { url: url.toString() });
    }
    const config = googleMarketingConfig();
    if (!config.clientId || !config.clientSecret)
      return apiInternalError(
        request,
        "Google marketing connections are not configured.",
      );
    const state = randomBytes(32).toString("base64url"),
      verifier = randomBytes(32).toString("base64url");
    await db.execute(
      sql`DELETE FROM marketing_oauth_states WHERE expires_at < now()`,
    );
    await db.execute(
      sql`INSERT INTO marketing_oauth_states(state_hash,user_id,client_id,provider,verifier,expires_at) VALUES(${createHash("sha256").update(state).digest("hex")},${access.userId}::uuid,${access.clientId}::uuid,${p.data.provider},${encrypt(verifier)},now()+interval '10 minutes')`,
    );
    const url = new URL("https://accounts.google.com/o/oauth2/v2/auth");
    url.search = new URLSearchParams({
      client_id: config.clientId,
      redirect_uri: config.callback,
      response_type: "code",
      scope: googleMarketingScopes[p.data.provider],
      access_type: "offline",
      prompt: "consent",
      state,
      code_challenge: createHash("sha256").update(verifier).digest("base64url"),
      code_challenge_method: "S256",
    }).toString();
    return apiSuccess(request, { url: url.toString() });
  } catch {
    return apiInternalError(request, "Unable to start Google connection.");
  }
}
