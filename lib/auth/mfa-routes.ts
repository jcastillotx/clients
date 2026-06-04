const ADMIN_ROUTES_WITHOUT_MFA = new Set(["/admin/email"]);

export function adminRouteRequiresMfa(pathname: string): boolean {
  const normalizedPath = pathname.replace(/\/$/, "") || "/";

  return normalizedPath.startsWith("/admin") && !ADMIN_ROUTES_WITHOUT_MFA.has(normalizedPath);
}
