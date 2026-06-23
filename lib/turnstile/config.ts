/** Cloudflare Turnstile is enforced when both keys are configured. */
export function isTurnstileEnforced(): boolean {
  return Boolean(
    process.env.NEXT_PUBLIC_TURNSTILE_SITE_KEY?.trim() &&
      process.env.TURNSTILE_SECRET_KEY?.trim(),
  );
}

export function getTurnstileSiteKey(): string | null {
  const siteKey = process.env.NEXT_PUBLIC_TURNSTILE_SITE_KEY?.trim();
  return siteKey || null;
}
