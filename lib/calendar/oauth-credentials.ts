import { and, desc, eq } from "drizzle-orm";

import { db } from "@/lib/db";
import { encryptedSettings } from "@/lib/db/schema/encrypted-settings";
import { users } from "@/lib/db/schema/users";
import { decrypt } from "@/lib/encryption";

export type CalendarOAuthProvider = "google" | "microsoft";

type CalendarCredentialKey = "client_id" | "client_secret" | "tenant_id";

export type CalendarOAuthCredentials = Partial<Record<CalendarCredentialKey, string>> & {
  source: "saved" | "env" | "none";
  clientScopeId: string | null;
};

type CalendarCredentialRow = {
  clientId: string;
  settingKey: string;
  encryptedValue: string;
};

const credentialKeys = new Set<CalendarCredentialKey>([
  "client_id",
  "client_secret",
  "tenant_id",
]);

function normalizeCredential(value: string | undefined): string | undefined {
  const trimmed = value?.trim();
  return trimmed && trimmed.length > 0 ? trimmed : undefined;
}

function getSettingsProvider(provider: CalendarOAuthProvider) {
  return provider === "google" ? "google_calendar" : "microsoft_calendar";
}

function isCalendarCredentialKey(key: string): key is CalendarCredentialKey {
  return credentialKeys.has(key as CalendarCredentialKey);
}

function credentialsFromRows(
  rows: CalendarCredentialRow[],
  clientScopeId: string | null,
): CalendarOAuthCredentials {
  const credentials: CalendarOAuthCredentials = {
    source: "saved",
    clientScopeId,
  };

  for (const row of rows) {
    if (!isCalendarCredentialKey(row.settingKey)) {
      continue;
    }

    const value = normalizeCredential(decrypt(row.encryptedValue));
    if (value) {
      credentials[row.settingKey] = value;
    }
  }

  return credentials;
}

async function getUserClientId(userId: string): Promise<string | null> {
  const [user] = await db
    .select({ clientId: users.clientId })
    .from(users)
    .where(eq(users.id, userId))
    .limit(1);

  return user?.clientId ?? null;
}

async function getSavedCalendarCredentials(
  provider: CalendarOAuthProvider,
  clientId: string | null,
): Promise<CalendarOAuthCredentials | null> {
  if (!clientId) {
    return null;
  }

  const rows: CalendarCredentialRow[] = await db
    .select({
      clientId: encryptedSettings.clientId,
      settingKey: encryptedSettings.settingKey,
      encryptedValue: encryptedSettings.encryptedValue,
    })
    .from(encryptedSettings)
    .where(
      and(
        eq(encryptedSettings.clientId, clientId),
        eq(encryptedSettings.provider, getSettingsProvider(provider)),
        eq(encryptedSettings.isActive, true),
      ),
    );

  if (rows.length === 0) {
    return null;
  }

  return credentialsFromRows(rows, clientId);
}

async function getLatestSavedCalendarCredentials(
  provider: CalendarOAuthProvider,
): Promise<CalendarOAuthCredentials | null> {
  const rows: CalendarCredentialRow[] = await db
    .select({
      clientId: encryptedSettings.clientId,
      settingKey: encryptedSettings.settingKey,
      encryptedValue: encryptedSettings.encryptedValue,
    })
    .from(encryptedSettings)
    .where(
      and(
        eq(encryptedSettings.provider, getSettingsProvider(provider)),
        eq(encryptedSettings.isActive, true),
      ),
    )
    .orderBy(desc(encryptedSettings.updatedAt));

  const rowsByClient = new Map<string, CalendarCredentialRow[]>();
  for (const row of rows) {
    const clientRows = rowsByClient.get(row.clientId) ?? [];
    clientRows.push(row);
    rowsByClient.set(row.clientId, clientRows);
  }

  for (const [clientId, clientRows] of rowsByClient) {
    const credentials = credentialsFromRows(clientRows, clientId);
    if (credentials.client_id && credentials.client_secret) {
      return credentials;
    }
  }

  return null;
}

function getEnvCalendarCredentials(provider: CalendarOAuthProvider): CalendarOAuthCredentials {
  if (provider === "google") {
    return {
      client_id: normalizeCredential(
        process.env.GOOGLE_CALENDAR_CLIENT_ID ||
          process.env.GOOGLE_EMAIL_CLIENT_ID ||
          process.env.GOOGLE_CLIENT_ID,
      ),
      client_secret: normalizeCredential(
        process.env.GOOGLE_CALENDAR_CLIENT_SECRET ||
          process.env.GOOGLE_EMAIL_CLIENT_SECRET ||
          process.env.GOOGLE_CLIENT_SECRET,
      ),
      source: "env",
      clientScopeId: null,
    };
  }

  return {
    client_id: normalizeCredential(
      process.env.MICROSOFT_CALENDAR_CLIENT_ID ||
        process.env.MICROSOFT_EMAIL_CLIENT_ID ||
        process.env.MICROSOFT_CLIENT_ID,
    ),
    client_secret: normalizeCredential(
      process.env.MICROSOFT_CALENDAR_CLIENT_SECRET ||
        process.env.MICROSOFT_EMAIL_CLIENT_SECRET ||
        process.env.MICROSOFT_CLIENT_SECRET,
    ),
    tenant_id: normalizeCredential(
      process.env.MICROSOFT_CALENDAR_TENANT_ID ||
        process.env.MICROSOFT_EMAIL_TENANT_ID ||
        process.env.MICROSOFT_TENANT_ID,
    ),
    source: "env",
    clientScopeId: null,
  };
}

export function hasCompleteCalendarOAuthCredentials(
  credentials: CalendarOAuthCredentials,
): boolean {
  return Boolean(credentials.client_id && credentials.client_secret);
}

export async function resolveCalendarOAuthCredentialsForUser(
  userId: string,
  provider: CalendarOAuthProvider,
  preferredClientId?: string | null,
): Promise<CalendarOAuthCredentials> {
  const savedForPreferredClient = await getSavedCalendarCredentials(
    provider,
    preferredClientId ?? null,
  );
  if (savedForPreferredClient?.client_id && savedForPreferredClient.client_secret) {
    return savedForPreferredClient;
  }

  const userClientId = await getUserClientId(userId);
  const savedForUserClient = await getSavedCalendarCredentials(provider, userClientId);
  if (savedForUserClient?.client_id && savedForUserClient.client_secret) {
    return savedForUserClient;
  }

  const latestSaved = await getLatestSavedCalendarCredentials(provider);
  if (latestSaved?.client_id && latestSaved.client_secret) {
    return latestSaved;
  }

  const envCredentials = getEnvCalendarCredentials(provider);
  if (envCredentials.client_id || envCredentials.client_secret) {
    return envCredentials;
  }

  return {
    source: "none",
    clientScopeId: null,
  };
}

export function getMicrosoftCalendarTenant(credentials: CalendarOAuthCredentials): string {
  return encodeURIComponent(credentials.tenant_id?.trim() || "common");
}
