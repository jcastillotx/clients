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
  const publicEnvBaseUrl =
    normalizeBaseUrl(process.env.NEXT_PUBLIC_SITE_URL) ||
    normalizeBaseUrl(process.env.NEXT_PUBLIC_VERCEL_URL);

  // Browser runtime: only NEXT_PUBLIC_* vars are available.
  if (typeof window !== "undefined") {
    if (publicEnvBaseUrl) {
      return publicEnvBaseUrl;
    }

    const hostname = window.location.hostname;
    const isLocalhost =
      hostname === "localhost" ||
      hostname === "127.0.0.1" ||
      hostname === "::1";

    // Allow falling back to the current origin only for localhost / dev.
    if (isLocalhost || process.env.NODE_ENV !== "production") {
      return window.location.origin;
    }

    throw new Error(
      "getAuthBaseUrl: NEXT_PUBLIC_SITE_URL or NEXT_PUBLIC_VERCEL_URL must be set in production."
    );
  }

  // Server runtime: VERCEL_URL is available.
  const envBaseUrl =
    publicEnvBaseUrl || normalizeBaseUrl(process.env.VERCEL_URL);

  if (envBaseUrl) return envBaseUrl;
  return "http://localhost:3000";
}

function isSafeNextPath(nextPath: string): boolean {
  // Only allow same-origin relative paths (e.g. "/dashboard").
  // Disallow protocol-relative URLs ("//evil.com") and absolute URLs.
  if (!nextPath) return false;
  if (!nextPath.startsWith("/")) return false;
  if (nextPath.startsWith("//")) return false;

  try {
    // If parsing as a URL succeeds *with* a scheme, it's not a plain path.
    const url = new URL(nextPath);
    // If it has a protocol, treat it as unsafe.
    if (url.protocol === "http:" || url.protocol === "https:") {
      return false;
    }
  } catch {
    // Parsing failed, which is expected for plain paths like "/foo".
  }

  return true;
}

export function getAuthConfirmUrl(nextPath: string): string {
  const callbackUrl = new URL("/auth/confirm", getAuthBaseUrl());
  const safeNextPath = isSafeNextPath(nextPath) ? nextPath : "/";
  callbackUrl.searchParams.set("next", safeNextPath);
  return callbackUrl.toString();
}
