/**
 * Structured logger for SOC2-compliant audit trails.
 *
 * Outputs JSON lines in production for easy ingestion by log aggregators
 * (Datadog, Logtail, CloudWatch, etc.). Falls back to readable console
 * output in development.
 *
 * Optionally integrates with Sentry when SENTRY_DSN is configured.
 */

type LogLevel = "debug" | "info" | "warn" | "error";

interface LogPayload {
  level: LogLevel;
  message: string;
  timestamp: string;
  service: string;
  /** Optional structured context — never include PII/secrets here */
  context?: Record<string, unknown>;
  error?: {
    name: string;
    message: string;
    stack?: string;
  };
}

const SERVICE_NAME = process.env.NEXT_PUBLIC_APP_NAME ?? "kre8iv-clients";
const IS_PROD = process.env.NODE_ENV === "production";

function formatError(err: unknown): LogPayload["error"] | undefined {
  if (!err) return undefined;
  if (err instanceof Error) {
    return { name: err.name, message: err.message, stack: IS_PROD ? undefined : err.stack };
  }
  return { name: "UnknownError", message: String(err) };
}

function write(payload: LogPayload) {
  if (IS_PROD) {
    // JSON lines — one object per line for log aggregators
    process.stdout.write(JSON.stringify(payload) + "\n");
  } else {
    const { level, message, context, error } = payload;
    const parts: unknown[] = [`[${level.toUpperCase()}]`, message];
    if (context && Object.keys(context).length > 0) parts.push(context);
    if (error) parts.push(error);
    // Use appropriate console level
    if (level === "error") console.error(...parts);
    else if (level === "warn") console.warn(...parts);
    else console.log(...parts);
  }

  // Forward errors to Sentry if available
  if (payload.level === "error" && process.env.SENTRY_DSN) {
    try {
      // Dynamic import to avoid bundling Sentry when not configured
      // @ts-ignore — Sentry may not be installed; silently skip if absent
      const Sentry = require("@sentry/nextjs") as typeof import("@sentry/nextjs");
      if (payload.error) {
        Sentry.captureMessage(payload.message, {
          level: "error",
          extra: { ...payload.context, rawError: payload.error },
        });
      }
    } catch {
      // Sentry not available; ignore
    }
  }
}

function createEntry(
  level: LogLevel,
  message: string,
  context?: Record<string, unknown>,
  err?: unknown
): LogPayload {
  return {
    level,
    message,
    timestamp: new Date().toISOString(),
    service: SERVICE_NAME,
    ...(context ? { context } : {}),
    ...(err ? { error: formatError(err) } : {}),
  };
}

export const logger = {
  debug(message: string, context?: Record<string, unknown>) {
    if (!IS_PROD) write(createEntry("debug", message, context));
  },

  info(message: string, context?: Record<string, unknown>) {
    write(createEntry("info", message, context));
  },

  warn(message: string, context?: Record<string, unknown>) {
    write(createEntry("warn", message, context));
  },

  error(message: string, err?: unknown, context?: Record<string, unknown>) {
    write(createEntry("error", message, context, err));
  },
};

/**
 * Log a security audit event (SOC2 CC6.2, CC6.3, CC6.8).
 * These are always written regardless of log level.
 */
export function auditLog(
  action: string,
  userId: string | undefined,
  context?: Record<string, unknown>
) {
  write(
    createEntry("info", `AUDIT: ${action}`, {
      userId: userId ?? "anonymous",
      ...context,
    })
  );
}
