import { describe, expect, it } from "vitest";

import { adminRouteRequiresMfa } from "./mfa-routes";

describe("admin MFA route requirements", () => {
  it("does not require MFA for the email provider admin page", () => {
    expect(adminRouteRequiresMfa("/admin/email")).toBe(false);
    expect(adminRouteRequiresMfa("/admin/email/")).toBe(false);
  });

  it("requires MFA for other admin pages", () => {
    expect(adminRouteRequiresMfa("/admin")).toBe(true);
    expect(adminRouteRequiresMfa("/admin/template-forms")).toBe(true);
    expect(adminRouteRequiresMfa("/admin/service-templates")).toBe(true);
  });

  it("does not require admin MFA for non-admin pages", () => {
    expect(adminRouteRequiresMfa("/settings/security")).toBe(false);
    expect(adminRouteRequiresMfa("/integrations")).toBe(false);
  });
});
