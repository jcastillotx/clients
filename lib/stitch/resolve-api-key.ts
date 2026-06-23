import { and, eq } from "drizzle-orm";
import { db } from "@/lib/db";
import { encryptedSettings } from "@/lib/db/schema/encrypted-settings";
import { decrypt } from "@/lib/encryption";

/**
 * Resolve Stitch API key for a tenant: per-client encrypted setting first, then env fallback.
 */
export async function resolveStitchApiKey(clientId: string | null): Promise<string | null> {
  if (clientId) {
    try {
      const rows = await db
        .select({
          encryptedValue: encryptedSettings.encryptedValue,
        })
        .from(encryptedSettings)
        .where(
          and(
            eq(encryptedSettings.clientId, clientId),
            eq(encryptedSettings.provider, "google_stitch"),
            eq(encryptedSettings.settingKey, "api_key"),
            eq(encryptedSettings.isActive, true),
          ),
        )
        .limit(1);

      const encrypted = rows[0]?.encryptedValue;
      if (encrypted) {
        const apiKey = decrypt(encrypted).trim();
        if (apiKey) {
          return apiKey;
        }
      }
    } catch {
      // Fall through to env when encryption is unavailable in dev.
    }
  }

  const envKey = process.env.STITCH_API_KEY?.trim();
  return envKey || null;
}

export function isStitchConfigured(apiKey: string | null): apiKey is string {
  return Boolean(apiKey);
}
