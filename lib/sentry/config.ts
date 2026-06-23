export function getSentryEnvironment(): string {
  return (
    process.env.NEXT_PUBLIC_SENTRY_ENVIRONMENT?.trim() ||
    process.env.VERCEL_ENV ||
    process.env.NODE_ENV ||
    "development"
  );
}

export function isSentryEnabled(): boolean {
  return Boolean(process.env.SENTRY_DSN?.trim());
}

export function getSentryOptions() {
  const enabled = isSentryEnabled();

  return {
    dsn: process.env.SENTRY_DSN,
    enabled,
    environment: getSentryEnvironment(),
    tracesSampleRate: process.env.NODE_ENV === "production" ? 0.1 : 1.0,
    debug: process.env.SENTRY_DEBUG === "true",
  };
}
