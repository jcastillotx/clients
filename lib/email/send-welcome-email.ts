import { getEmailBrandContext, wrapBrandedEmail } from "@/lib/email/branding";
import { sendEmail } from "@/lib/email/client";
import { getAuthBaseUrl } from "@/lib/supabase/redirect-url";

function escapeHtml(value: string): string {
  return value.replace(/[&<>"']/g, (character) => ({
    "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;",
  })[character] ?? character);
}

/** Send only after account provisioning succeeds; never include credentials. */
export async function sendWelcomeEmail(user: { name: string; email: string }) {
  try {
    const brand = await getEmailBrandContext();
    const baseUrl = getAuthBaseUrl();
    const loginUrl = new URL("/login", baseUrl).toString();
    const resetUrl = new URL("/forgot-password", baseUrl).toString();
    const subject = `Welcome to ${brand.app_name}`;
    const html = wrapBrandedEmail(`
      <h1 style="font-size:24px;margin-top:0;">Welcome to ${escapeHtml(brand.app_name)}</h1>
      <p>Hi ${escapeHtml(user.name)},</p>
      <p>Your portal account is ready. We're glad to have you here!</p>
      <p>Use the portal to stay connected with our team and manage your work with us in one place.</p>
      <p style="margin:28px 0;"><a href="${escapeHtml(loginUrl)}" style="display:inline-block;padding:14px 24px;border-radius:8px;background:${escapeHtml(brand.brand_primary_color)};color:#ffffff;text-decoration:none;font-weight:bold;">Sign in to your portal</a></p>
      <p>Sign in with <strong>${escapeHtml(user.email)}</strong> and the password provided to you. If you need to set or reset your password, <a href="${escapeHtml(resetUrl)}">request a password reset</a>.</p>
      <p>Need help getting started? Reply to this email and our team will help.</p>
      <p>Thank you,<br>${escapeHtml(brand.brand_name)}</p>
    `, brand);
    return await sendEmail({
      to: user.email,
      subject,
      html,
      text: `Hi ${user.name},\n\nWelcome to ${brand.app_name}! Your portal account is ready.\n\nSign in: ${loginUrl}\nEmail: ${user.email}\n\nUse the password provided to you, or set/reset your password here: ${resetUrl}\n\nNeed help? Reply to this email.\n\nThank you,\n${brand.brand_name}`,
    });
  } catch (error) {
    console.error("Error sending portal welcome email:", error);
    return { success: false };
  }
}

/** A company record does not provision a portal login. */
export async function sendClientWelcomeEmail(client: { companyName: string; email: string }) {
  try {
    const brand = await getEmailBrandContext();
    const subject = `Welcome to ${brand.brand_name}`;
    const html = wrapBrandedEmail(`
      <h1 style="font-size:24px;margin-top:0;">Welcome to ${escapeHtml(brand.brand_name)}</h1>
      <p>Hello ${escapeHtml(client.companyName)} team,</p>
      <p>We're glad to have you with us! Our team looks forward to working with you.</p>
      <p>We'll follow up with next steps and any portal access instructions you need.</p>
      <p>Have a question or something to share? Reply to this email to reach our team.</p>
      <p>Thank you,<br>${escapeHtml(brand.brand_name)}</p>
    `, brand);
    return await sendEmail({
      to: client.email,
      subject,
      html,
      text: `Hello ${client.companyName} team,\n\nWelcome to ${brand.brand_name}! We're glad to have you with us.\n\nWe'll follow up with next steps and any portal access instructions you need.\n\nHave a question? Reply to this email to reach our team.\n\nThank you,\n${brand.brand_name}`,
    });
  } catch (error) {
    console.error("Error sending client welcome email:", error);
    return { success: false };
  }
}
