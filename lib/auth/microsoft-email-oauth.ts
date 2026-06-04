const DEFAULT_MICROSOFT_TENANT = "common";

export function getMicrosoftEmailTenant(): string {
  const rawTenant =
    process.env.MICROSOFT_EMAIL_TENANT_ID ||
    process.env.MICROSOFT_TENANT_ID ||
    DEFAULT_MICROSOFT_TENANT;

  return encodeURIComponent(rawTenant.trim() || DEFAULT_MICROSOFT_TENANT);
}

export function getMicrosoftEmailAuthorizeUrl(): string {
  return `https://login.microsoftonline.com/${getMicrosoftEmailTenant()}/oauth2/v2.0/authorize`;
}

export function getMicrosoftEmailTokenUrl(): string {
  return `https://login.microsoftonline.com/${getMicrosoftEmailTenant()}/oauth2/v2.0/token`;
}
