import { and, desc, eq } from "drizzle-orm";

import { db } from "@/lib/db";
import { encryptedSettings } from "@/lib/db/schema/encrypted-settings";
import { decrypt } from "@/lib/encryption";

type StripeSettingKey = "publishable_key" | "secret_key" | "webhook_secret";

export type StripeCredentials = Partial<Record<StripeSettingKey, string>>;

type StripeCredentialRow = {
  clientId: string;
  settingKey: string;
  encryptedValue: string;
};

const stripeSettingKeys = new Set<StripeSettingKey>([
  "publishable_key",
  "secret_key",
  "webhook_secret",
]);

function normalizeCredential(value: string | undefined): string | undefined {
  const trimmed = value?.trim();
  return trimmed && trimmed.length > 0 ? trimmed : undefined;
}

function isStripeSettingKey(key: string): key is StripeSettingKey {
  return stripeSettingKeys.has(key as StripeSettingKey);
}

function credentialsFromRows(rows: StripeCredentialRow[]): StripeCredentials {
  const credentials: StripeCredentials = {};

  for (const row of rows) {
    if (!isStripeSettingKey(row.settingKey)) {
      continue;
    }

    const value = normalizeCredential(decrypt(row.encryptedValue));
    if (value) {
      credentials[row.settingKey] = value;
    }
  }

  return credentials;
}

async function getSavedStripeCredentials(clientId: string): Promise<StripeCredentials> {
  const rows: StripeCredentialRow[] = await db
    .select({
      clientId: encryptedSettings.clientId,
      settingKey: encryptedSettings.settingKey,
      encryptedValue: encryptedSettings.encryptedValue,
    })
    .from(encryptedSettings)
    .where(
      and(
        eq(encryptedSettings.clientId, clientId),
        eq(encryptedSettings.provider, "stripe"),
        eq(encryptedSettings.isActive, true),
      ),
    );

  return credentialsFromRows(rows);
}

async function getLatestSavedStripeCredentials(): Promise<StripeCredentials> {
  const rows: StripeCredentialRow[] = await db
    .select({
      clientId: encryptedSettings.clientId,
      settingKey: encryptedSettings.settingKey,
      encryptedValue: encryptedSettings.encryptedValue,
    })
    .from(encryptedSettings)
    .where(
      and(
        eq(encryptedSettings.provider, "stripe"),
        eq(encryptedSettings.isActive, true),
      ),
    )
    .orderBy(desc(encryptedSettings.updatedAt));

  const rowsByClient = new Map<string, StripeCredentialRow[]>();
  for (const row of rows) {
    const clientRows = rowsByClient.get(row.clientId) ?? [];
    clientRows.push(row);
    rowsByClient.set(row.clientId, clientRows);
  }

  for (const clientRows of rowsByClient.values()) {
    const credentials = credentialsFromRows(clientRows);
    if (credentials.secret_key) {
      return credentials;
    }
  }

  return {};
}

export async function resolveStripeCredentialsForClient(clientId: string): Promise<StripeCredentials> {
  const saved = await getSavedStripeCredentials(clientId);
  const fallback = saved.secret_key ? {} : await getLatestSavedStripeCredentials();
  const selected = saved.secret_key ? saved : fallback;

  return {
    publishable_key:
      selected.publishable_key ??
      normalizeCredential(process.env.NEXT_PUBLIC_STRIPE_PUBLISHABLE_KEY),
    secret_key: selected.secret_key ?? normalizeCredential(process.env.STRIPE_SECRET_KEY),
    webhook_secret:
      selected.webhook_secret ?? normalizeCredential(process.env.STRIPE_WEBHOOK_SECRET),
  };
}

export async function resolveStripeWebhookSecrets(): Promise<string[]> {
  const secrets = new Set<string>();
  const envSecret = normalizeCredential(process.env.STRIPE_WEBHOOK_SECRET);

  if (envSecret) {
    secrets.add(envSecret);
  }

  const rows = await db
    .select({
      encryptedValue: encryptedSettings.encryptedValue,
    })
    .from(encryptedSettings)
    .where(
      and(
        eq(encryptedSettings.provider, "stripe"),
        eq(encryptedSettings.settingKey, "webhook_secret"),
        eq(encryptedSettings.isActive, true),
      ),
    );

  for (const row of rows) {
    const secret = normalizeCredential(decrypt(row.encryptedValue));
    if (secret) {
      secrets.add(secret);
    }
  }

  return [...secrets];
}
