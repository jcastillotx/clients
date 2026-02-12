function normalizeBaseUrl(value: string | undefined | null): string | null {
  if (!value) return null;
  const withProtocol = value.startsWith("http://") || value.startsWith("https://") ? value : `https://${value}`;

  try {
    return new URL(withProtocol).origin;
  } catch {
    return null;
  }
}

export function getAuthBaseUrl(): string {
  if (typeof window !== "undefined") {
    // Use the active browser origin first so auth links stay on the same
    // domain the user is currently using (custom domains, staging aliases).
    const browserOrigin = normalizeBaseUrl(window.location.origin);
    if (browserOrigin) return browserOrigin;
  }

  const siteUrl = normalizeBaseUrl(process.env.NEXT_PUBLIC_SITE_URL);
  if (siteUrl) return siteUrl;

  const appUrl = normalizeBaseUrl(process.env.NEXT_PUBLIC_APP_URL);
  if (appUrl) return appUrl;

  const envBaseUrl =
    normalizeBaseUrl(process.env.NEXT_PUBLIC_VERCEL_PROJECT_PRODUCTION_URL) ||
    normalizeBaseUrl(process.env.VERCEL_PROJECT_PRODUCTION_URL) ||
    normalizeBaseUrl(process.env.NEXT_PUBLIC_VERCEL_URL) ||
    normalizeBaseUrl(process.env.VERCEL_URL);

  if (envBaseUrl && process.env.VERCEL_ENV !== "production") return envBaseUrl;

  // Development fallback - only used when no env vars are set
  if (process.env.NODE_ENV === "development") {
    return "http://localhost:3000";
  }

  // Production should always have a configured base URL
  throw new Error(
    "No base URL configured. Please set NEXT_PUBLIC_SITE_URL or NEXT_PUBLIC_APP_URL."
  );
}

function isSafeNextPathForConfirm(nextPath: string | null | undefined): boolean {
  // Must be a relative path that starts with "/" but not a protocol-relative URL ("//").
  // Also reject paths with ".." to prevent path traversal attacks.
  if (!nextPath || typeof nextPath !== "string") {
    return false;
  }
  
  // Iteratively decode URL-encoded characters to handle double-encoding attacks
  let decodedPath = nextPath;
  let previousPath = "";
  let iterations = 0;
  const maxIterations = 5; // Prevent infinite loops
  
  try {
    while (decodedPath !== previousPath && iterations < maxIterations) {
      previousPath = decodedPath;
      decodedPath = decodeURIComponent(decodedPath);
      iterations++;
    }
  } catch (e) {
    // Invalid URI sequence - reject it
    return false;
  }
  
  return (
    decodedPath.startsWith("/") &&
    !decodedPath.startsWith("//") &&
    !decodedPath.includes("..")
  );
}

export function getAuthConfirmUrl(nextPath: string): string {
  const callbackUrl = new URL("/auth/confirm", getAuthBaseUrl());
  const safeNextPath = isSafeNextPathForConfirm(nextPath) ? nextPath : "/";
  callbackUrl.searchParams.set("next", safeNextPath);
  return callbackUrl.toString();
}
