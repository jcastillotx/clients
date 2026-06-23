import { z } from "zod";

/**
 * Production-required environment variables.
 * Validates at server startup via instrumentation.ts.
 */
const productionEnvSchema = z
  .object({
    NEXT_PUBLIC_SUPABASE_URL: z.string().url(),
    NEXT_PUBLIC_SUPABASE_ANON_KEY: z.string().min(1),
    DATABASE_URL: z.string().min(1).optional(),
    POSTGRES_URL: z.string().min(1).optional(),
    SUPABASE_SERVICE_KEY: z.string().min(1).optional(),
    SUPABASE_SERVICE_ROLE_KEY: z.string().min(1).optional(),
  })
  .superRefine((env, ctx) => {
    if (!env.DATABASE_URL && !env.POSTGRES_URL) {
      ctx.addIssue({
        code: z.ZodIssueCode.custom,
        message: "DATABASE_URL or POSTGRES_URL is required in production",
        path: ["DATABASE_URL"],
      });
    }

    if (!env.SUPABASE_SERVICE_KEY && !env.SUPABASE_SERVICE_ROLE_KEY) {
      ctx.addIssue({
        code: z.ZodIssueCode.custom,
        message:
          "SUPABASE_SERVICE_KEY or SUPABASE_SERVICE_ROLE_KEY is required in production",
        path: ["SUPABASE_SERVICE_KEY"],
      });
    }
  });

let validated = false;

/**
 * Fail fast when production is misconfigured.
 * Skipped in development/test so local partial setups still work.
 */
export function validateEnvAtStartup(): void {
  if (process.env.NODE_ENV !== "production" || validated) {
    return;
  }

  const result = productionEnvSchema.safeParse(process.env);
  if (!result.success) {
    const formatted = result.error.flatten().fieldErrors;
    console.error("[env] Production configuration invalid:", formatted);
    throw new Error(
      "Invalid production environment configuration. See server logs for details.",
    );
  }

  validated = true;
  warnRecommendedProductionEnv();
}

type RecommendedEnvVar = {
  keys: string[];
  feature: string;
};

const RECOMMENDED_PRODUCTION_ENV: RecommendedEnvVar[] = [
  {
    keys: ["UPSTASH_REDIS_REST_URL", "UPSTASH_REDIS_REST_TOKEN"],
    feature: "distributed rate limiting (falls back to in-memory per instance)",
  },
  {
    keys: ["SENTRY_DSN"],
    feature: "error tracking and alerting",
  },
  {
    keys: ["NEXT_PUBLIC_TURNSTILE_SITE_KEY", "TURNSTILE_SECRET_KEY"],
    feature: "CAPTCHA on public intake and invoice payment forms",
  },
  {
    keys: ["INNGEST_EVENT_KEY", "INNGEST_SIGNING_KEY"],
    feature: "background jobs (invoice reminders, SLA checks, etc.)",
  },
  {
    keys: ["RESEND_API_KEY"],
    feature: "transactional email delivery",
  },
  {
    keys: ["STITCH_API_KEY"],
    feature: "Google Stitch UI design generation",
  },
];

/**
 * Log non-fatal warnings for recommended production integrations.
 * Does not block startup — use before go-live to confirm Vercel env is complete.
 */
export function warnRecommendedProductionEnv(): void {
  if (process.env.NODE_ENV !== "production") {
    return;
  }

  for (const { keys, feature } of RECOMMENDED_PRODUCTION_ENV) {
    const configured = keys.every((key) => Boolean(process.env[key]?.trim()));
    if (!configured) {
      console.warn(
        `[env] Recommended production config missing (${keys.join(", ")}): ${feature}`,
      );
    }
  }
}

/** @internal Test helper */
export function resetEnvValidationForTests(): void {
  validated = false;
}

export function isSupabaseConfigured(): boolean {
  return Boolean(
    process.env.NEXT_PUBLIC_SUPABASE_URL &&
      process.env.NEXT_PUBLIC_SUPABASE_ANON_KEY,
  );
}
