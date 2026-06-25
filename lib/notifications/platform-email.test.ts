import { afterEach, describe, expect, it, vi } from "vitest";
import {
  getPlatformNotificationEmails,
  withPlatformNotificationEmails,
} from "./platform-email";
import { createAdminClientIfAvailable } from "@/lib/supabase/server";

vi.mock("@/lib/supabase/server", () => ({
  createAdminClientIfAvailable: vi.fn(),
}));

function restoreEnvValue(key: string, value: string | undefined) {
  if (value === undefined) {
    delete process.env[key];
    return;
  }

  process.env[key] = value;
}

describe("platform notification emails", () => {
  const originalPlatformNotificationEmail =
    process.env.PLATFORM_NOTIFICATION_EMAIL;
  const originalPlatformEmail = process.env.PLATFORM_EMAIL;
  const originalAdminNotificationEmail = process.env.ADMIN_NOTIFICATION_EMAIL;

  afterEach(() => {
    vi.clearAllMocks();
    restoreEnvValue(
      "PLATFORM_NOTIFICATION_EMAIL",
      originalPlatformNotificationEmail,
    );
    restoreEnvValue("PLATFORM_EMAIL", originalPlatformEmail);
    restoreEnvValue(
      "ADMIN_NOTIFICATION_EMAIL",
      originalAdminNotificationEmail,
    );
  });

  it("reads comma and semicolon separated platform inboxes from admin settings", async () => {
    vi.mocked(createAdminClientIfAvailable).mockReturnValue({
      from: vi.fn().mockReturnValue({
        select: vi.fn().mockReturnThis(),
        eq: vi.fn().mockReturnThis(),
        maybeSingle: vi.fn().mockResolvedValue({
          data: {
            value: "ops@example.com, support@example.com;ops@example.com",
          },
          error: null,
        }),
      }),
    } as never);

    expect(await getPlatformNotificationEmails()).toEqual([
      "ops@example.com",
      "support@example.com",
    ]);
  });

  it("falls back to env when the admin setting is missing", async () => {
    vi.mocked(createAdminClientIfAvailable).mockReturnValue(null);
    process.env.PLATFORM_NOTIFICATION_EMAIL =
      "ops@example.com, support@example.com;ops@example.com";
    delete process.env.PLATFORM_EMAIL;
    delete process.env.ADMIN_NOTIFICATION_EMAIL;

    expect(await getPlatformNotificationEmails()).toEqual([
      "ops@example.com",
      "support@example.com",
    ]);
  });

  it("appends platform inboxes without duplicating existing recipients", async () => {
    vi.mocked(createAdminClientIfAvailable).mockReturnValue(null);
    process.env.PLATFORM_NOTIFICATION_EMAIL = "ops@example.com";
    delete process.env.PLATFORM_EMAIL;
    delete process.env.ADMIN_NOTIFICATION_EMAIL;

    expect(
      await withPlatformNotificationEmails([
        "client@example.com",
        "ops@example.com",
      ]),
    ).toEqual(["client@example.com", "ops@example.com"]);
  });
});
