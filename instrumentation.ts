export async function register() {
  if (process.env.NEXT_RUNTIME === "nodejs") {
    await import("./sentry.server.config");
    const { validateEnvAtStartup } = await import("@/lib/env");
    validateEnvAtStartup();
  }

  if (process.env.NEXT_RUNTIME === "edge") {
    await import("./sentry.edge.config");
  }
}

export async function onRequestError(
  error: unknown,
  request: {
    path: string;
    method: string;
    headers: Record<string, string | string[] | undefined>;
  },
) {
  if (!process.env.SENTRY_DSN?.trim()) {
    return;
  }

  const requestIdHeader = request.headers["x-request-id"];
  const requestId = Array.isArray(requestIdHeader)
    ? requestIdHeader[0]
    : requestIdHeader;

  const Sentry = await import("@sentry/nextjs");
  Sentry.captureException(error, {
    extra: {
      path: request.path,
      method: request.method,
      requestId,
    },
  });
}
