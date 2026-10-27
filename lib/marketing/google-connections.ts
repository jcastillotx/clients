import { sql } from "drizzle-orm";
import { db } from "@/lib/db";
import { encrypt, decrypt } from "@/lib/encryption";
export const googleMarketingProviders = [
  "google_analytics",
  "google_ads",
] as const;
export type GoogleMarketingProvider = (typeof googleMarketingProviders)[number];
export function googleMarketingConfig() {
  return {
    clientId:
      process.env.GOOGLE_MARKETING_CLIENT_ID ||
      process.env.GOOGLE_CALENDAR_CLIENT_ID,
    clientSecret:
      process.env.GOOGLE_MARKETING_CLIENT_SECRET ||
      process.env.GOOGLE_CALENDAR_CLIENT_SECRET,
    callback: new URL(
      "/api/marketing/connections/callback",
      process.env.NEXT_PUBLIC_APP_URL,
    ).toString(),
  };
}
export const googleMarketingScopes = {
  google_analytics: "https://www.googleapis.com/auth/analytics.readonly",
  google_ads: "https://www.googleapis.com/auth/adwords",
};
export function connectionGroup(provider: GoogleMarketingProvider) {
  return provider === "google_ads" ? "paid-media" : "analytics";
}
export async function googleMarketingToken(
  clientId: string,
  userId: string,
  provider: GoogleMarketingProvider,
) {
  const [row] = await db.execute(
    sql`SELECT * FROM marketing_connections WHERE client_id=${clientId}::uuid AND user_id=${userId}::uuid AND provider=${provider}`,
  );
  if (!row) throw Error("Connect your Google account first.");
  let token = decrypt(String(row.access_token));
  if (
    !row.expires_at ||
    new Date(String(row.expires_at)).getTime() < Date.now() + 60000
  ) {
    if (!row.refresh_token) throw Error("Reconnect your Google account.");
    const config = googleMarketingConfig();
    const response = await fetch("https://oauth2.googleapis.com/token", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: new URLSearchParams({
        client_id: config.clientId!,
        client_secret: config.clientSecret!,
        refresh_token: decrypt(String(row.refresh_token)),
        grant_type: "refresh_token",
      }),
      signal: AbortSignal.timeout(20000),
    });
    if (!response.ok)
      throw Error("Google access expired. Reconnect your account.");
    const body = await response.json();
    if (typeof body.access_token !== "string")
      throw Error("Google token response invalid.");
    token = body.access_token;
    await db.execute(
      sql`UPDATE marketing_connections SET access_token=${encrypt(token)},refresh_token=${body.refresh_token ? encrypt(body.refresh_token) : row.refresh_token},expires_at=now()+${Number(body.expires_in) || 3600}*interval '1 second',updated_at=now() WHERE id=${row.id}::uuid`,
    );
  }
  return { token, row };
}
export async function googleMarketingResources(
  token: string,
  provider: GoogleMarketingProvider,
) {
  const headers: Record<string, string> = { Authorization: "Bearer " + token };
  if (provider === "google_ads" && process.env.GOOGLE_ADS_DEVELOPER_TOKEN)
    headers["developer-token"] = process.env.GOOGLE_ADS_DEVELOPER_TOKEN;
  const resources: Array<{ id: string; name: string }> = [];
  if (provider === "google_ads") {
    const version = process.env.GOOGLE_ADS_API_VERSION || "v26";
    if (!/^v\d+$/.test(version)) throw Error("Ads API version invalid.");
    const r = await fetch(
      `https://googleads.googleapis.com/${version}/customers:listAccessibleCustomers`,
      { headers, signal: AbortSignal.timeout(20000) },
    );
    if (!r.ok)
      throw Error(
        "Google Ads accounts unavailable. Check API access and account permissions.",
      );
    const b = await r.json();
    const roots: string[] = (b.resourceNames || []).filter(
      (id: unknown) => typeof id === "string" && /^customers\/\d+$/.test(id),
    );
    if (roots.length > 25)
      throw Error(
        "Connect an account with no more than 25 directly accessible Ads accounts.",
      );
    type AdsRow = {
      customer?: { descriptiveName?: string; manager?: boolean };
      customerClient?: {
        resourceName?: string;
        descriptiveName?: string;
        manager?: boolean;
      };
    };
    async function query(
      customer: string,
      query: string,
      login?: string,
    ): Promise<AdsRow[]> {
      const response = await fetch(
        `https://googleads.googleapis.com/${version}/${customer}/googleAds:search`,
        {
          method: "POST",
          headers: {
            ...headers,
            "Content-Type": "application/json",
            ...(login ? { "login-customer-id": login } : {}),
          },
          body: JSON.stringify({ query }),
          signal: AbortSignal.timeout(20000),
        },
      );
      if (!response.ok) throw Error("Google Ads account metadata unavailable.");
      const body = (await response.json()) as { results?: AdsRow[] };
      if (body.results && !Array.isArray(body.results))
        throw Error("Google Ads response invalid.");
      return body.results || [];
    }
    const groups = await Promise.all(
      roots.map(async (id) => {
        const [row] = await query(
          id,
          "SELECT customer.id, customer.descriptive_name, customer.manager FROM customer LIMIT 1",
        );
        if (!row?.customer) throw Error("Google Ads account unavailable.");
        if (row.customer.manager !== true)
          return [
            {
              id,
              name:
                row.customer.descriptiveName ||
                "Google Ads " + id.split("/")[1],
            },
          ];
        const rows = await query(
          id,
          "SELECT customer_client.resource_name, customer_client.descriptive_name, customer_client.manager FROM customer_client WHERE customer_client.manager = FALSE LIMIT 1001",
          id.split("/")[1],
        );
        if (rows.length > 1000)
          throw Error(
            "Select a manager with no more than 1000 advertising accounts.",
          );
        return rows.flatMap(({ customerClient: c }) =>
          c?.resourceName?.startsWith(id + "/customerClients/") &&
          /^customers\/\d+\/customerClients\/\d+$/.test(c.resourceName) &&
          c.manager !== true
            ? [
                {
                  id: c.resourceName,
                  name:
                    c.descriptiveName ||
                    "Google Ads " + c.resourceName.split("/").pop(),
                },
              ]
            : [],
        );
      }),
    );
    for (const group of groups) resources.push(...group);
    if (resources.length > 1000)
      throw Error(
        "Too many Ads accounts. Connect a more focused Google account.",
      );
  } else {
    let pageToken = "";
    for (let page = 0; page < 20; page++) {
      const url = new URL(
        "https://analyticsadmin.googleapis.com/v1beta/accountSummaries",
      );
      url.searchParams.set("pageSize", "200");
      if (pageToken) url.searchParams.set("pageToken", pageToken);
      const r = await fetch(url, {
        headers,
        signal: AbortSignal.timeout(20000),
      });
      if (!r.ok)
        throw Error(
          "Analytics properties unavailable. Check API access and permissions.",
        );
      const b = await r.json();
      for (const a of b.accountSummaries || [])
        for (const p of a.propertySummaries || [])
          if (/^properties\/\d+$/.test(p.property))
            resources.push({
              id: p.property,
              name: p.displayName || p.property,
            });
      pageToken = b.nextPageToken || "";
      if (!pageToken) break;
    }
  }
  return resources;
}
