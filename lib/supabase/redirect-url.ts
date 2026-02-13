function normalizeBaseUrl(value: string | undefined | null): string | null {
  if (!value) return null;
  const withProtocol = value.startsWith("http://") || value.startsWith("https://") ? value : `https://${value}`;

  try {
    return new URL(withProtocol).origin;
  } catch {
    return null;
  }
}

/**
 * Returns the canonical base URL for this application.
 *
 * Priority (highest first):
 * 1. NEXT_PUBLIC_SITE_URL  – explicitly configured custom domain
 * 2. NEXT_PUBLIC_APP_URL   – alias / legacy env var
 * 3. Browser window.location.origin (client-side only)
 * 4. VERCEL_PROJECT_PRODUCTION_URL – Vercel-provided production domain
 * 5. VERCEL_URL – Vercel deployment URL (preview / branch deploys only)
 * 6. localhost fallback for development
 *
 * The explicit env vars are checked FIRST on both client and server so that
 * auth emails, magic links, and OAuth callbacks always point to the real
 * custom domain rather than an internal *.vercel.app hostname.
 */
export function getAuthBaseUrl(): string {
  // 1 & 2 – Explicit configuration always wins (works on both client & server)
  const siteUrl = normalizeBaseUrl(process.env.NEXT_PUBLIC_SITE_URL);
  if (siteUrl) return siteUrl;

  const appUrl = normalizeBaseUrl(process.env.NEXT_PUBLIC_APP_URL);
  if (appUrl) return appUrl;

  // 3 – On the client, use the browser origin so the user stays on whatever
  //     domain they navigated to (custom domain, preview alias, etc.)
  if (typeof window !== "undefined") {
    const browserOrigin = normalizeBaseUrl(window.location.origin);
    if (browserOrigin) return browserOrigin;
  }

  // 4 & 5 – Vercel-provided URLs (useful for preview / branch deploys)
  const prodUrl =
    normalizeBaseUrl(process.env.NEXT_PUBLIC_VERCEL_PROJECT_PRODUCTION_URL) ||
    normalizeBaseUrl(process.env.VERCEL_PROJECT_PRODUCTION_URL);
  if (prodUrl) return prodUrl;

  // For non-production Vercel environments fall back to the deployment URL
  const vercelUrl =
    normalizeBaseUrl(process.env.NEXT_PUBLIC_VERCEL_URL) ||
    normalizeBaseUrl(process.env.VERCEL_URL);
  if (vercelUrl) return vercelUrl;

  // 6 – Development fallback
  if (process.env.NODE_ENV === "development") {
    return "http://localhost:3000";
  }

  // Production should always have a configured base URL
  throw new Error(
    "No base URL configured. Please set NEXT_PUBLIC_SITE_URL to your custom domain (e.g. https://clients.kre8ivdesigns.com)."
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
  } catch {
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
