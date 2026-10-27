/** Real OpenRouter acceptance is a separate localhost-only environment, not isolated simulator mode.
 * @param {Readonly<Record<string, string | undefined>>} env
 */
export function isLocalOpenRouterAcceptance(env = process.env) {
  if (!env.STAGING_PROVIDER_ACCEPTANCE) return false;
  if (
    env.STAGING_PROVIDER_ACCEPTANCE !== "openrouter" ||
    env.STAGING_ISOLATED === "1"
  ) {
    throw new Error(
      "Provider acceptance must be separate from isolated simulator mode",
    );
  }
  const required = {
    NEXT_PUBLIC_APP_URL: "3101",
    NEXT_PUBLIC_SITE_URL: "3101",
    NEXT_PUBLIC_SUPABASE_URL: "55321",
    DATABASE_URL: "55322",
  };
  for (const [key, port] of Object.entries(required)) {
    const url = new URL(env[key] || "");
    if (!["127.0.0.1", "localhost"].includes(url.hostname) || url.port !== port)
      throw new Error(
        "Provider acceptance requires dedicated localhost services: " + key,
      );
  }
  for (const key of [
    "AWS_S3_ENDPOINT",
    "STRIPE_API_BASE_URL",
    "INNGEST_BASE_URL",
  ]) {
    if (
      env[key] &&
      !["127.0.0.1", "localhost"].includes(new URL(env[key]).hostname)
    )
      throw new Error("Provider acceptance refuses external " + key);
  }
  if (env.STRIPE_SECRET_KEY?.startsWith("sk_live_"))
    throw new Error("Provider acceptance refuses live payments");
  for (const key of [
    "OPENAI_API_KEY",
    "ANTHROPIC_API_KEY",
    "GOOGLE_AI_API_KEY",
    "RESEND_API_KEY",
    "MICROSOFT_EMAIL_CLIENT_SECRET",
  ]) {
    if (env[key])
      throw new Error(
        "Provider acceptance permits only the dedicated OpenRouter credential",
      );
  }
  return true;
}
