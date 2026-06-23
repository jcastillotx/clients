import Stripe from "stripe";
import { and, eq } from "drizzle-orm";
import { z } from "zod";
import { db } from "@/lib/db";
import { encryptedSettings } from "@/lib/db/schema/encrypted-settings";
import { decrypt } from "@/lib/encryption";
import { createClient } from "@/lib/supabase/server";
import { isUserAdmin } from "@/lib/rbac/check";
import { getPublicIntegrationError } from "@/lib/settings/integration-errors";
import { validateIntegrationProviderCategory } from "@/lib/settings/integration-validation";
import {
  apiError,
  apiForbidden,
  apiNotFound,
  apiSuccess,
  apiUnauthorized,
  apiValidationError,
} from "@/lib/api/response";

const verifySchema = z.object({
  clientId: z.string().uuid(),
  provider: z.string().min(1),
});

type SavedCredential = {
  id: string;
  settingKey: string;
  encryptedValue: string;
};

export async function POST(request: Request) {
  try {
    const supabase = await createClient();
    const {
      data: { user },
    } = await supabase.auth.getUser();

    if (!user) {
      return apiUnauthorized(request);
    }

    if (!(await isUserAdmin(user.id))) {
      return apiForbidden(request);
    }

    const body = await request.json();
    const parsed = verifySchema.safeParse(body);

    if (!parsed.success) {
      return apiValidationError(request, parsed.error);
    }

    const { clientId, provider: requestedProvider } = parsed.data;
    const providerValidation = validateIntegrationProviderCategory(requestedProvider);

    if (!providerValidation.success) {
      return apiError(request, {
        status: 400,
        code: "BAD_REQUEST",
        message: providerValidation.error,
      });
    }

    const { provider } = providerValidation;
    const credentials: SavedCredential[] = await db
      .select({
        id: encryptedSettings.id,
        settingKey: encryptedSettings.settingKey,
        encryptedValue: encryptedSettings.encryptedValue,
      })
      .from(encryptedSettings)
      .where(
        and(
          eq(encryptedSettings.clientId, clientId),
          eq(encryptedSettings.provider, provider),
          eq(encryptedSettings.isActive, true),
        ),
      );

    if (credentials.length === 0) {
      return apiNotFound(request, "No saved credentials found for this provider");
    }

    const decryptedCredentials = Object.fromEntries(
      credentials.map((credential: SavedCredential) => [credential.settingKey, decrypt(credential.encryptedValue)]),
    );

    await verifyProvider(provider, decryptedCredentials);

    const verifiedAt = new Date();
    await db
      .update(encryptedSettings)
      .set({
        lastVerifiedAt: verifiedAt,
        updatedBy: user.id,
        updatedAt: verifiedAt,
      })
      .where(
        and(
          eq(encryptedSettings.clientId, clientId),
          eq(encryptedSettings.provider, provider),
          eq(encryptedSettings.isActive, true),
        ),
      );

    const message = getSuccessMessage(provider);
    return apiSuccess(
      request,
      { provider, verifiedAt: verifiedAt.toISOString(), message },
      { extra: { success: true, provider, verifiedAt: verifiedAt.toISOString(), message } },
    );
  } catch (error) {
    console.error("Error verifying integration settings:", error);
    const publicError = getPublicIntegrationError(error, "Failed to verify integration settings.");
    return apiError(request, {
      status: publicError.status,
      code: publicError.status >= 500 ? "INTERNAL_ERROR" : "BAD_REQUEST",
      message: publicError.error,
    });
  }
}

async function verifyProvider(provider: string, credentials: Record<string, string>) {
  switch (provider) {
    case "stripe":
      await verifyStripe(credentials);
      return;
    default:
      validateCredentialPresence(credentials);
      return;
  }
}

function validateCredentialPresence(credentials: Record<string, string>) {
  const hasAnyValue = Object.values(credentials).some((value) => value.trim().length > 0);
  if (!hasAnyValue) {
    throw new Error("No credentials available to verify.");
  }
}

async function verifyStripe(credentials: Record<string, string>) {
  const publishableKey = credentials.publishable_key?.trim();
  const secretKey = credentials.secret_key?.trim();
  const webhookSecret = credentials.webhook_secret?.trim();

  if (!publishableKey || !secretKey) {
    throw new Error("Stripe requires both a publishable key and secret key.");
  }

  if (!publishableKey.startsWith("pk_")) {
    throw new Error("Stripe publishable key must start with pk_.");
  }

  if (!secretKey.startsWith("sk_")) {
    throw new Error("Stripe secret key must start with sk_.");
  }

  if (webhookSecret && !webhookSecret.startsWith("whsec_")) {
    throw new Error("Stripe webhook secret must start with whsec_.");
  }

  const stripe = new Stripe(secretKey, {
    apiVersion: "2023-10-16",
    typescript: true,
  });

  await stripe.balance.retrieve();
}

function getSuccessMessage(provider: string) {
  switch (provider) {
    case "stripe":
      return "Stripe credentials verified successfully.";
    default:
      return "Credentials verified successfully.";
  }
}
