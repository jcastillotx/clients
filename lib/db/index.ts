import { drizzle } from "drizzle-orm/postgres-js";
import postgres from "postgres";
import * as schema from "./schema";

/**
 * Env lookup order:
 * - On Vercel, prefer `POSTGRES_URL` first — integrations often supply the Supabase
 *   Transaction pooler here, while `DATABASE_URL` is sometimes pasted as Session mode
 *   (port 5432) and exhausts MaxClientsInSessionMode under serverless concurrency.
 * - Locally, `DATABASE_URL` first is the common DX.
 */
const DATABASE_CONNECTION_ENV_KEYS = (
  process.env.VERCEL
    ? (["POSTGRES_URL", "DATABASE_URL", "POSTGRES_URL_NON_POOLING"] as const)
    : (["DATABASE_URL", "POSTGRES_URL", "POSTGRES_URL_NON_POOLING"] as const)
) satisfies readonly string[];

export class DatabaseConfigurationError extends Error {
  constructor(message?: string) {
    super(
      message ??
        `Database connection string is not configured. Set one of: ${DATABASE_CONNECTION_ENV_KEYS.join(", ")}.`,
    );
    this.name = "DatabaseConfigurationError";
  }
}

/**
 * Supabase shared pooler: Session mode is typically port 5432; Transaction mode is 6543.
 * Session pooler + many Vercel lambdas → XX000 MaxClientsInSessionMode.
 */
function assertNotSupabaseSessionPooler(connectionString: string): void {
  try {
    const normalized = connectionString.replace(/^postgres(ql)?:/i, "http:");
    const u = new URL(normalized);
    if (!u.hostname.includes("pooler.supabase.com")) {
      return;
    }
    const port = u.port === "" ? 5432 : Number.parseInt(u.port, 10);
    if (port === 6543) {
      return;
    }
    throw new DatabaseConfigurationError(
      `Supabase connection uses the pooler on port ${port} (Session-style). ` +
        `Use Transaction pooler on port 6543 instead: Supabase Dashboard → Database → ` +
        `Connection string → Transaction. On Vercel, set POSTGRES_URL from the Supabase integration ` +
        `or point DATABASE_URL at the :6543 URI.`,
    );
  } catch (e) {
    if (e instanceof DatabaseConfigurationError) {
      throw e;
    }
    // Ignore parse errors; postgres client will surface a clearer failure.
  }
}

const resolveConnectionString = (): string | null => {
  for (const key of DATABASE_CONNECTION_ENV_KEYS) {
    const value = process.env[key];
    if (!value) {
      continue;
    }

    const normalizedValue = value.trim();
    if (!normalizedValue || normalizedValue === "undefined" || normalizedValue === "null") {
      continue;
    }

    return normalizedValue;
  }

  return null;
};

const connectionString = resolveConnectionString();
if (connectionString) {
  assertNotSupabaseSessionPooler(connectionString);
}

/**
 * Pool size for postgres.js. On Vercel/serverless, keep this low (default 1): each
 * concurrent function instance can open its own pool, and Supabase Session pooler
 * mode enforces a small max clients → "MaxClientsInSessionMode" if you exhaust it.
 * Prefer Supabase's Transaction pooler (port 6543) for DATABASE_URL in production.
 */
function resolvePostgresPoolMax(): number {
  const raw = process.env.POSTGRES_MAX_CONNECTIONS;
  if (!raw) {
    return 1;
  }
  const n = Number.parseInt(raw, 10);
  if (Number.isNaN(n) || n < 1) {
    return 1;
  }
  return Math.min(n, 20);
}

const configuredDb = connectionString
  ? drizzle(
      postgres(connectionString, {
        // Required for Supabase pooler transaction mode; also see POSTGRES_MAX_CONNECTIONS.
        prepare: false,
        max: resolvePostgresPoolMax(),
        idle_timeout: 20,
        connect_timeout: 30,
      }),
      { schema },
    )
  : null;

const missingConfigDbProxy = new Proxy(
  {},
  {
    get() {
      throw new DatabaseConfigurationError();
    },
  },
);

export const db = (configuredDb ?? missingConfigDbProxy) as NonNullable<typeof configuredDb>;

export const isDatabaseConfigurationError = (error: unknown): error is DatabaseConfigurationError =>
  error instanceof DatabaseConfigurationError;
