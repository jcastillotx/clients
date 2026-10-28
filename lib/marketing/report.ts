import { z } from "zod";
import type { GoogleMarketingProvider } from "./google-connections";
const analyticsReport = z
  .object({
    kind: z.literal("analyticsData#runReport"),
    rows: z.array(z.unknown()).optional(),
    metricHeaders: z
      .array(z.object({ name: z.string() }).passthrough())
      .optional(),
    metadata: z.record(z.string(), z.unknown()).optional(),
  })
  .passthrough();
const adsReport = z
  .object({ results: z.array(z.unknown()).optional() })
  .passthrough();
export function marketingReportStatus(
  provider: GoogleMarketingProvider,
  data: unknown,
) {
  if (provider === "google_analytics") {
    const report = analyticsReport.parse(data);
    return report.rows?.length ? ("data" as const) : ("empty" as const);
  }
  const report = adsReport.parse(data);
  if ("error" in report) throw Error("Provider error");
  return report.results?.length ? ("data" as const) : ("empty" as const);
}
