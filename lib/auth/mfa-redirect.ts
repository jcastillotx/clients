const KNOWN_MFA_TARGETS: Record<string, string> = {
  "/settings/maintenance-templates": "Maintenance plan templates",
  "/settings/service-templates": "Service templates",
  "/settings/form-templates": "Form templates",
  "/admin/maintenance-plans": "Maintenance plan templates",
  "/admin/service-templates": "Service templates",
  "/admin/template-forms": "Form templates",
  "/admin/email": "Email provider",
};

const LEGACY_MFA_REDIRECTS: Record<string, string> = {
  "/admin/template-forms": "/settings/form-templates",
  "/admin/service-templates": "/settings/service-templates",
  "/admin/maintenance-plans": "/settings/maintenance-templates",
};

export function getSafeMfaRedirectPath(nextRaw: string | null | undefined): string {
  const trimmed = nextRaw?.trim() ?? "";

  if (!trimmed.startsWith("/") || trimmed.startsWith("//")) {
    return "/dashboard";
  }

  return trimmed;
}

/** Resolve legacy admin aliases to their settings routes after MFA. */
export function resolveMfaRedirectPath(nextRaw: string | null | undefined): string {
  const safePath = getSafeMfaRedirectPath(nextRaw);
  const [pathOnly, query = ""] = safePath.split("?");
  const normalizedPath = (pathOnly ?? safePath).replace(/\/$/, "") || "/";
  const legacyTarget = LEGACY_MFA_REDIRECTS[normalizedPath];

  if (!legacyTarget) {
    return safePath;
  }

  return query ? `${legacyTarget}?${query}` : legacyTarget;
}

/** Human label for `next` when middleware sends admins here for MFA (AAL2). */
export function friendlyMfaRedirectTarget(nextRaw: string | null | undefined): string | null {
  const safePath = getSafeMfaRedirectPath(nextRaw);
  const path = (safePath.split("?")[0] ?? safePath).replace(/\/$/, "") || "/";

  if (KNOWN_MFA_TARGETS[path]) {
    return KNOWN_MFA_TARGETS[path];
  }

  if (path.startsWith("/admin/")) {
    const slug = path.slice("/admin/".length);

    if (!slug) {
      return "Admin";
    }

    return slug
      .split("/")
      .map((seg) =>
        seg
          .split("-")
          .filter(Boolean)
          .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
          .join(" "),
      )
      .join(" › ");
  }

  return null;
}
