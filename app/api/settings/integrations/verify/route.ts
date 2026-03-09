import { NextResponse } from "next/server";
import Stripe from "stripe";
import { and, eq } from "drizzle-orm";
import { z } from "zod";
import { db } from "@/lib/db";
import { encryptedSettings, type IntegrationProvider } from "@/lib/db/schema/encrypted-settings";
import { decrypt } from "@/lib/encryption";
import { createClient } from "@/lib/supabase/server";
import { isUserAdmin } from "@/lib/rbac/check";

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
      return NextResponse.json({ error: "Unauthorized" }, { status: 401 });
    }

    if (!(await isUserAdmin(user.id))) {
      return NextResponse.json({ error: "Forbidden" }, { status: 403 });
    }

    const body = await request.json();
    const parsed = verifySchema.safeParse(body);

    if (!parsed.success) {
      return NextResponse.json({ error: "Invalid request body", details: parsed.error.flatten() }, { status: 400 });
    }

    const { clientId, provider } = parsed.data;
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
          eq(encryptedSettings.provider, provider as IntegrationProvider),
          eq(encryptedSettings.isActive, true),
        ),
      );

    if (credentials.length === 0) {
      return NextResponse.json({ error: "No saved credentials found for this provider" }, { status: 404 });
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
          eq(encryptedSettings.provider, provider as IntegrationProvider),
          eq(encryptedSettings.isActive, true),
        ),
      );

    return NextResponse.json({
      success: true,
      provider,
      verifiedAt: verifiedAt.toISOString(),
      message: getSuccessMessage(provider),
    });
  } catch (error) {
    console.error("Error verifying integration settings:", error);
    return NextResponse.json(
      { error: error instanceof Error ? error.message : "Failed to verify integration" },
      { status: 500 },
    );
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
    apiVersion: "2024-12-18.acacia" as any,
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
