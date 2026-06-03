import { describe, expect, it } from "vitest";

import { friendlyMfaRedirectTarget, getSafeMfaRedirectPath } from "./mfa-redirect";

describe("MFA redirect helpers", () => {
  it("keeps same-site redirect paths with query strings", () => {
    expect(getSafeMfaRedirectPath("/admin/email?tab=provider")).toBe("/admin/email?tab=provider");
  });

  it("falls back for external or protocol-relative redirect values", () => {
    expect(getSafeMfaRedirectPath("https://example.com/admin/email")).toBe("/dashboard");
    expect(getSafeMfaRedirectPath("//example.com/admin/email")).toBe("/dashboard");
    expect(getSafeMfaRedirectPath(null)).toBe("/dashboard");
  });

  it("labels known admin targets", () => {
    expect(friendlyMfaRedirectTarget("/admin/email")).toBe("Email provider");
    expect(friendlyMfaRedirectTarget("/admin/template-forms?foo=bar")).toBe("Form templates");
  });

  it("builds labels for unknown admin targets", () => {
    expect(friendlyMfaRedirectTarget("/admin/custom-tools/access-log")).toBe("Custom Tools › Access Log");
  });

  it("does not label unsafe or non-admin fallback targets", () => {
    expect(friendlyMfaRedirectTarget("https://example.com/admin/email")).toBeNull();
    expect(friendlyMfaRedirectTarget("/settings/security")).toBeNull();
  });
});
