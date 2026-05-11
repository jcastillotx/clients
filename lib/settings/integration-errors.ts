import { isDatabaseConfigurationError } from "@/lib/db";

type PublicIntegrationError = {
  error: string;
  status: number;
};

type ErrorWithCode = Error & {
  code?: string;
};

export function getPublicIntegrationError(
  error: unknown,
  fallbackMessage = "Failed to save integration settings.",
): PublicIntegrationError {
  if (isDatabaseConfigurationError(error)) {
    return {
      error: error.message,
      status: 503,
    };
  }

  if (!(error instanceof Error)) {
    return {
      error: fallbackMessage,
      status: 500,
    };
  }

  const code = (error as ErrorWithCode).code;
  const message = error.message;

  if (message.includes("ENCRYPTION_KEY")) {
    return {
      error:
        "Server encryption is not configured. Add ENCRYPTION_KEY to the production environment and redeploy.",
      status: 503,
    };
  }

  if (code === "42P01" || message.includes('relation "encrypted_settings" does not exist')) {
    return {
      error:
        "Integration settings database table is missing. Run migration 016_create_encrypted_settings.sql before saving API keys.",
      status: 503,
    };
  }

  if (code === "42703") {
    return {
      error:
        "Integration settings database schema is out of date. Apply the latest database migrations before saving API keys.",
      status: 503,
    };
  }

  if (code === "23503") {
    return {
      error:
        "The selected client scope is not available for integration settings. Refresh the page and try again.",
      status: 400,
    };
  }

  return {
    error: fallbackMessage,
    status: 500,
  };
}
