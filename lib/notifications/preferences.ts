export type NotificationPreferences = {
  emailEnabled: boolean;
  inAppEnabled: boolean;
};

export function parseNotificationPreferences(
  input: unknown,
): NotificationPreferences {
  const settings = (input || {}) as {
    notifications?: {
      emailEnabled?: boolean;
      inAppEnabled?: boolean;
    };
  };

  return {
    emailEnabled: settings.notifications?.emailEnabled ?? true,
    inAppEnabled: settings.notifications?.inAppEnabled ?? true,
  };
}
