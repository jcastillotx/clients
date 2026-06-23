function envFlag(name: string, defaultValue = false): boolean {
  const raw = process.env[name];
  if (raw === undefined) {
    return defaultValue;
  }

  return raw === "1" || raw.toLowerCase() === "true";
}

export function hasAiProviderIntegration(): boolean {
  return Boolean(
    process.env.OPENAI_API_KEY?.trim() || process.env.ANTHROPIC_API_KEY?.trim(),
  );
}

/** Template-based email generation until an AI provider is configured. */
export function isAiEmailPreviewMode(): boolean {
  return !hasAiProviderIntegration();
}

/** Social account OAuth is disabled until platform credentials are configured. */
export function isSocialOAuthEnabled(): boolean {
  return (
    envFlag("FEATURE_SOCIAL_OAUTH", false) ||
    envFlag("NEXT_PUBLIC_FEATURE_SOCIAL_OAUTH", false)
  );
}

/** Client components can only read NEXT_PUBLIC_* flags at build time. */
export function isSocialOAuthEnabledOnClient(): boolean {
  return envFlag("NEXT_PUBLIC_FEATURE_SOCIAL_OAUTH", false);
}

/** Platform ad metrics sync requires external API credentials. */
export function isAdsPlatformSyncEnabled(): boolean {
  return envFlag("FEATURE_ADS_PLATFORM_SYNC", false);
}

export function hasEmailDelivery(): boolean {
  return Boolean(process.env.RESEND_API_KEY?.trim());
}
