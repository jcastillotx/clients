/** Read-only credential checks. These do not establish delivery, model inference, or sync. */
export const LIVE_CHECK_PROVIDERS = ["stripe", "openai", "anthropic", "google_ai", "resend"];
export const LIVE_CHECK_ROLLOUT = new Date("2026-10-28T00:00:00Z");

export function trustedVerification(provider: string, verifiedAt: Date | null) {
  return LIVE_CHECK_PROVIDERS.includes(provider) && verifiedAt &&
    (provider === "stripe" || verifiedAt >= LIVE_CHECK_ROLLOUT) ? verifiedAt : null;
}

export async function verifyReadOnlyProvider(provider: string, credentials: Record<string, string>) {
  const key = credentials.api_key?.trim();
  if (!key) throw new Error("An API key is required.");
  const targets: Record<string, { url: string; headers: Record<string, string> }> = {
    openai: { url: "https://api.openai.com/v1/models", headers: { Authorization: `Bearer ${key}` } },
    anthropic: { url: "https://api.anthropic.com/v1/models", headers: { "x-api-key": key, "anthropic-version": "2023-06-01" } },
    google_ai: { url: "https://generativelanguage.googleapis.com/v1beta/models", headers: { "x-goog-api-key": key } },
    resend: { url: "https://api.resend.com/domains", headers: { Authorization: `Bearer ${key}` } },
  };
  const target = targets[provider];
  if (!target) throw new Error("Live verification is not available for this provider. Use its connection workflow.");
  const response = await fetch(target.url, {
    headers: target.headers, cache: "no-store", redirect: "error", signal: AbortSignal.timeout(10000),
  });
  if (!response.ok) throw new Error(`Provider credential check failed (HTTP ${response.status}).`);
  const data: unknown = await response.json();
  if (!data || typeof data !== "object" ||
    !(Array.isArray((data as { data?: unknown }).data) || Array.isArray((data as { models?: unknown }).models))) {
    throw new Error("Provider returned an unexpected response.");
  }
}
