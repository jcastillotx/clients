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
  const siteUrl = normalizeBaseUrl(process.env.NEXT_PUBLIC_SITE_URL);
  if (siteUrl) return siteUrl;

  if (typeof window !== "undefined") {
    return window.location.origin;
  }

  const envBaseUrl =
    normalizeBaseUrl(process.env.NEXT_PUBLIC_VERCEL_URL) ||
    normalizeBaseUrl(process.env.VERCEL_URL);

  if (envBaseUrl) return envBaseUrl;

  return "http://localhost:3000";
}

export function getAuthConfirmUrl(nextPath: string): string {
  const callbackUrl = new URL("/auth/confirm", getAuthBaseUrl());
  callbackUrl.searchParams.set("next", nextPath);
  return callbackUrl.toString();
}
