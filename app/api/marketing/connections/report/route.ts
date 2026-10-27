import { googleAdsResourceTarget } from "@/lib/marketing/google-ads-resources";
import { marketingReportStatus } from "@/lib/marketing/report";
import { resolveStaffAccess } from "@/lib/api/resolve-staff-access";
import { z } from "zod";
import {
  apiUnauthorized,
  apiSuccess,
  apiForbidden,
  apiValidationError,
  apiInternalError,
} from "@/lib/api/response";
import { marketingConnectionAccess } from "@/lib/marketing/connection-access";
import {
  googleMarketingProviders,
  googleMarketingToken,
} from "@/lib/marketing/google-connections";
export async function POST(request: Request) {
  if (!(await resolveStaffAccess())) return apiUnauthorized(request);
  try {
    const p = z
      .object({ provider: z.enum(googleMarketingProviders) })
      .safeParse(await request.json());
    if (!p.success) return apiValidationError(request, p.error);
    const access = await marketingConnectionAccess(p.data.provider);
    if (!access) return apiForbidden(request);
    const { token, row } = await googleMarketingToken(
        access.clientId,
        access.userId,
        p.data.provider,
      ),
      resource = String(row.resource_id || "");
    const headers: Record<string, string> = {
      Authorization: "Bearer " + token,
      "Content-Type": "application/json",
    };
    let url: string, body: unknown;
    if (p.data.provider === "google_analytics") {
      if (!/^properties\/\d+$/.test(resource))
        return apiForbidden(request, "Select an Analytics property first.");
      url = `https://analyticsdata.googleapis.com/v1beta/${resource}:runReport`;
      body = {
        dateRanges: [{ startDate: "7daysAgo", endDate: "yesterday" }],
        metrics: [
          { name: "sessions" },
          { name: "activeUsers" },
          { name: "keyEvents" },
        ],
      };
    } else {
      const target = googleAdsResourceTarget(resource);
      if (!target) return apiForbidden(request, "Select an Ads account first.");
      if (process.env.GOOGLE_ADS_DEVELOPER_TOKEN)
        headers["developer-token"] = process.env.GOOGLE_ADS_DEVELOPER_TOKEN;
      const version = process.env.GOOGLE_ADS_API_VERSION || "v26";
      if (!/^v\d+$/.test(version)) throw Error("version");
      if (target.loginCustomerId)
        headers["login-customer-id"] = target.loginCustomerId;
      url = `https://googleads.googleapis.com/${version}/customers/${target.customerId}/googleAds:search`;
      body = {
        query:
          "SELECT customer.id, customer.currency_code, metrics.impressions, metrics.clicks, metrics.cost_micros, metrics.conversions FROM customer WHERE segments.date DURING LAST_7_DAYS LIMIT 1",
      };
    }
    const r = await fetch(url, {
      method: "POST",
      headers,
      body: JSON.stringify(body),
      signal: AbortSignal.timeout(30000),
    });
    if (!r.ok) throw Error("provider");
    const data = await r.json();
    return apiSuccess(request, {
      provider: p.data.provider,
      resource,
      period:
        p.data.provider === "google_analytics"
          ? "Last 7 complete days"
          : "Google Ads LAST_7_DAYS",
      fetchedAt: new Date().toISOString(),
      dataStatus: marketingReportStatus(p.data.provider, data),
      data,
    });
  } catch {
    return apiInternalError(
      request,
      "Unable to read performance data. Check account permissions and API access.",
    );
  }
}
