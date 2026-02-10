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
  const appUrl = normalizeBaseUrl(process.env.NEXT_PUBLIC_APP_URL);
  if (appUrl) return appUrl;
  const siteUrl = normalizeBaseUrl(process.env.NEXT_PUBLIC_SITE_URL);
  if (siteUrl) return siteUrl;

  if (typeof window !== "undefined") {
    return window.location.origin;
  }

  const envBaseUrl =
    normalizeBaseUrl(process.env.NEXT_PUBLIC_VERCEL_URL) ||
    normalizeBaseUrl(process.env.VERCEL_URL);

  if (envBaseUrl) return envBaseUrl;

  // Development fallback - only used when no env vars are set
  if (process.env.NODE_ENV === "development") {
    return "http://localhost:3000";
  }

  // Production should always have a configured base URL
  throw new Error(
    "No base URL configured. Please set NEXT_PUBLIC_APP_URL environment variable."
  );
}

function isSafeNextPathForConfirm(nextPath: string | null | undefined): boolean {
  // Must be a relative path that starts with "/" but not a protocol-relative URL ("//").
  // Also reject paths with ".." to prevent path traversal attacks.
  if (!nextPath || typeof nextPath !== "string") {
    return false;
  }
  
  // Normalize the path to decode any URL-encoded characters
  const decodedPath = decodeURIComponent(nextPath);
  
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
