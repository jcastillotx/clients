import { loadEmailConfig } from "@/lib/email/providers";
import { getPortalBranding } from "@/lib/branding/get-branding";

export type EmailBrandContext = {
  app_name: string;
  brand_name: string;
  brand_logo_url: string;
  brand_primary_color: string;
  brand_secondary_color: string;
  support_email: string;
  site_url: string;
};

const DEFAULT_LOGO = "https://www.kre8ivdesigns.com/assets/logo.png";

export async function getEmailBrandContext(): Promise<EmailBrandContext> {
  const branding = await getPortalBranding();
  const settings = branding.settings;
  const emailConfig = await loadEmailConfig();
  return {
    app_name: settings.siteTitle || process.env.NEXT_PUBLIC_APP_NAME || "Kre8iv Clients",
    brand_name: settings.brandText || process.env.RESEND_FROM_NAME || "Kre8ivDesigns",
    brand_logo_url: branding.logoUrl || DEFAULT_LOGO,
    brand_primary_color: branding.primaryColor || "#7C3AED",
    brand_secondary_color: branding.secondaryColor || "#ffffff",
    support_email: process.env.EMAIL_REPLY_TO || process.env.RESEND_REPLY_TO || emailConfig?.from_email || "info@kre8ivdesigns.com",
    site_url: process.env.NEXT_PUBLIC_SITE_URL || process.env.NEXT_PUBLIC_APP_URL || "https://clients.example.com",
  };
}

function escapeAttribute(value: string): string {
  return value.replace(/[&<>"']/g, (character) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" })[character] ?? character);
}

/** Adds a consistent, editable-brand shell without changing the template body. */
export function wrapBrandedEmail(html: string, brand: EmailBrandContext): string {
  const logo = escapeAttribute(brand.brand_logo_url);
  const primary = escapeAttribute(brand.brand_primary_color);
  const brandName = escapeAttribute(brand.brand_name);
  const support = escapeAttribute(brand.support_email);
  const body = html.replace(/<!doctype html>|<html[^>]*>|<\/html>|<head[\s\S]*?<\/head>|<body[^>]*>|<\/body>/gi, "").trim();
  return `<!doctype html><html><head><meta charset="utf-8"><meta name="color-scheme" content="light"></head><body style="margin:0;background:#f5f3ff;font-family:Arial,sans-serif;color:#111827;"><div style="max-width:640px;margin:0 auto;padding:28px 16px;"><div style="background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 6px 24px rgba(15,23,42,.08);"><div style="padding:24px 28px;border-bottom:1px solid #ede9fe;text-align:center;"><img src="${logo}" alt="${brandName}" style="display:inline-block;max-width:220px;max-height:64px;height:auto;border:0;"></div><div style="padding:28px;">${body}</div><div style="padding:18px 28px;background:#fafafa;border-top:1px solid #f1f5f9;color:#64748b;font-size:12px;text-align:center;">${brandName} · <a href="mailto:${support}" style="color:${primary};">${support}</a></div></div></div></body></html>`;
}
