/** Production must never silently accept submissions without CAPTCHA. */
export function isTurnstileEnforced(): boolean {
  return Boolean(
    process.env.NODE_ENV === "production" ||
    (
    process.env.NEXT_PUBLIC_TURNSTILE_SITE_KEY?.trim() &&
      process.env.TURNSTILE_SECRET_KEY?.trim()),
  );
}

export function getTurnstileSiteKey(): string | null {
  const siteKey = process.env.NEXT_PUBLIC_TURNSTILE_SITE_KEY?.trim();
  return siteKey || null;
}
