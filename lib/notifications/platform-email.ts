import { createAdminClientIfAvailable } from "@/lib/supabase/server";

const PLATFORM_EMAIL_ENV_KEYS = [
  "PLATFORM_NOTIFICATION_EMAIL",
  "PLATFORM_EMAIL",
  "ADMIN_NOTIFICATION_EMAIL",
] as const;

const PLATFORM_NOTIFICATION_SETTING_KEY = "platform_notification_email";

interface PlatformEmailSettingRow {
  value: string;
}

function parseEmailList(value: string | undefined): string[] {
  if (!value) {
    return [];
  }

  return value
    .split(/[,;]/)
    .map((email) => email.trim())
    .filter(Boolean);
}

function getEnvPlatformNotificationEmails(): string[] {
  const emails = PLATFORM_EMAIL_ENV_KEYS.flatMap((key) =>
    parseEmailList(process.env[key]),
  );
  return Array.from(new Set(emails));
}

export async function getPlatformNotificationEmails(): Promise<string[]> {
  const adminClient = createAdminClientIfAvailable();
  if (!adminClient) {
    return getEnvPlatformNotificationEmails();
  }

  const { data, error } = await adminClient
    .from("system_settings")
    .select("value")
    .eq("category", "email")
    .eq("key", PLATFORM_NOTIFICATION_SETTING_KEY)
    .maybeSingle();

  if (error) {
    console.error("[notifications/platform-email] Failed to load setting:", error);
    return getEnvPlatformNotificationEmails();
  }

  if (data && typeof (data as PlatformEmailSettingRow).value === "string") {
    return Array.from(
      new Set(parseEmailList((data as PlatformEmailSettingRow).value)),
    );
  }

  return getEnvPlatformNotificationEmails();
}

export async function withPlatformNotificationEmails(
  emails: Array<string | null | undefined> = [],
): Promise<string[]> {
  return Array.from(
    new Set([
      ...emails.filter((email): email is string => Boolean(email?.trim())),
      ...(await getPlatformNotificationEmails()),
    ]),
  );
}
