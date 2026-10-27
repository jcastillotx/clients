const LOCAL_HOSTS = new Set(["127.0.0.1", "localhost", "[::1]"]);

export function assertIsolatedStaging(
  env: Readonly<Record<string, string | undefined>> = process.env,
): void {
  if (env.STAGING_ISOLATED !== "1") return;
  for (const key of [
    "NEXT_PUBLIC_APP_URL",
    "NEXT_PUBLIC_SITE_URL",
    "NEXT_PUBLIC_SUPABASE_URL",
    "DATABASE_URL",
  ]) {
    const value = env[key];
    if (!value || !LOCAL_HOSTS.has(new URL(value).hostname)) {
      throw new Error(`Isolated staging requires a local ${key}`);
    }
  }
  for (const key of [
    "AWS_S3_ENDPOINT",
    "STRIPE_API_BASE_URL",
    "INNGEST_BASE_URL",
  ]) {
    if (env[key] && !LOCAL_HOSTS.has(new URL(env[key]!).hostname)) {
      throw new Error(`Isolated staging refuses external ${key}`);
    }
  }
  if (env.STRIPE_SECRET_KEY && !env.STRIPE_SECRET_KEY.startsWith("sk_test_")) {
    throw new Error("Isolated staging refuses live Stripe credentials");
  }
  for (const key of [
    "RESEND_API_KEY",
    "OPENAI_API_KEY",
    "ANTHROPIC_API_KEY",
    "SENTRY_DSN",
    "INNGEST_EVENT_KEY",
  ]) {
    if (env[key]) throw new Error(`Isolated staging refuses external ${key}`);
  }
}
