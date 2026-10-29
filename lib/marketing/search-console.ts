import { z } from "zod";

export const searchConsoleSite = z
  .string()
  .max(2048)
  .refine((value) => {
    if (/^sc-domain:[a-z0-9.-]+$/i.test(value)) return true;
    try {
      const url = new URL(value);
      return (
        ["https:", "http:"].includes(url.protocol) &&
        !url.username &&
        !url.password &&
        !url.hash &&
        !url.search
      );
    } catch {
      return false;
    }
  });
export const searchConsoleSites = z.object({
  siteEntry: z
    .array(
      z.object({
        siteUrl: searchConsoleSite,
        permissionLevel: z.enum([
          "siteOwner",
          "siteFullUser",
          "siteRestrictedUser",
          "siteUnverifiedUser",
        ]),
      }),
    )
    .max(10000)
    .optional(),
});
export const searchConsoleReport = z
  .object({
    rows: z
      .array(
        z.object({
          clicks: z.number().finite().nonnegative(),
          impressions: z.number().finite().nonnegative(),
          ctr: z.number().finite().min(0).max(1),
          position: z.number().finite().nonnegative(),
        }),
      )
      .max(1)
      .optional(),
    responseAggregationType: z
      .enum(["auto", "byProperty", "byPage"])
      .optional(),
  })
  .strict();

export function searchConsoleRequest(resource: string, now = new Date()) {
  searchConsoleSite.parse(resource);
  // Search Console dates use Pacific time. Leave three days for processing and
  // request finalized data explicitly; missing rows are never invented zeros.
  const pacific = new Intl.DateTimeFormat("en-CA", {
    timeZone: "America/Los_Angeles",
    year: "numeric",
    month: "2-digit",
    day: "2-digit",
  }).format(now);
  const end = new Date(pacific + "T12:00:00Z");
  end.setUTCDate(end.getUTCDate() - 3);
  const start = new Date(end);
  start.setUTCDate(start.getUTCDate() - 6);
  const startDate = start.toISOString().slice(0, 10);
  const endDate = end.toISOString().slice(0, 10);
  return {
    url: `https://www.googleapis.com/webmasters/v3/sites/${encodeURIComponent(resource)}/searchAnalytics/query`,
    body: { startDate, endDate, type: "web", dataState: "final", rowLimit: 1 },
    period: `${startDate} through ${endDate} (Pacific time; finalized web search)`,
  };
}
