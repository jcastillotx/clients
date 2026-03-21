import { describe, expect, it } from "vitest";
import { parseNotificationPreferences } from "./preferences";

describe("parseNotificationPreferences", () => {
  it("defaults to enabled channels", () => {
    expect(parseNotificationPreferences(undefined)).toEqual({
      emailEnabled: true,
      inAppEnabled: true,
    });
  });

  it("respects explicit channel settings", () => {
    expect(
      parseNotificationPreferences({
        notifications: { emailEnabled: false, inAppEnabled: true },
      }),
    ).toEqual({
      emailEnabled: false,
      inAppEnabled: true,
    });
  });
});
