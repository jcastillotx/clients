export const marketingProviders = [
  "google_analytics",
  "google_ads",
  "google_search_console",
  "meta_ads",
] as const;
export type MarketingProvider = (typeof marketingProviders)[number];
export function marketingProviderGroup(provider: MarketingProvider) {
  if (provider === "google_search_console") return "seo";
  return provider === "google_analytics" ? "analytics" : "paid-media";
}
