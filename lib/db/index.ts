import { drizzle } from "drizzle-orm/postgres-js";
import postgres from "postgres";
import * as schema from "./schema";

const DATABASE_CONNECTION_ENV_KEYS = ["DATABASE_URL", "POSTGRES_URL_NON_POOLING", "POSTGRES_URL"] as const;

export class DatabaseConfigurationError extends Error {
  constructor() {
    super(
      `Database connection string is not configured. Set one of: ${DATABASE_CONNECTION_ENV_KEYS.join(", ")}.`,
    );
    this.name = "DatabaseConfigurationError";
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

const configuredDb = connectionString
  ? drizzle(
      // Disable prefetch as it is not supported for "transaction" pool mode
      postgres(connectionString, { prepare: false }),
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
