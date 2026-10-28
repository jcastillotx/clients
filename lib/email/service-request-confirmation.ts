import { getEmailBrandContext, wrapBrandedEmail } from "@/lib/email/branding";
import { sendEmail } from "@/lib/email/client";

function escapeHtml(value: string): string {
  return value.replace(/[&<>"']/g, (character) => ({
    "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;",
  })[character] ?? character);
}

/** Public addresses are unverified: acknowledge receipt without echoing free-form input or internal links. */
export async function sendServiceRequestConfirmation(input: {
  email: string;
  requestId: string;
}): Promise<boolean> {
  try {
    const brand = await getEmailBrandContext();
    const reference = escapeHtml(input.requestId);
    const result = await sendEmail({
      to: input.email,
      replyTo: brand.support_email,
      subject: `Service request received | ${brand.brand_name}`,
      html: wrapBrandedEmail(`
        <h1 style="font-size:24px;margin-top:0;">We received your service request</h1>
        <p>Thank you for contacting ${escapeHtml(brand.brand_name)}.</p>
        <p>Your request has been received and is awaiting review. Our team will review the information you submitted and follow up using your contact details.</p>
        <p style="padding:16px;background:#f5f3ff;border-radius:8px;"><strong>Request reference</strong><br>${reference}</p>
        <p>Please keep this reference for any follow-up. If you have a question, reply to this email to reach our team.</p>
        <p>This email confirms receipt of your request. Project scope, pricing, and timing will be discussed during review.</p>
        <p style="font-size:12px;color:#64748b;">If you did not submit a request using this email address, no action is required. You can contact our team if you need assistance.</p>
        <p>Thank you,<br>${escapeHtml(brand.brand_name)}</p>
      `, brand),
      text: `We received your service request\n\nThank you for contacting ${brand.brand_name}.\n\nYour request has been received and is awaiting review. Our team will review the information you submitted and follow up using your contact details.\n\nRequest reference: ${input.requestId}\n\nPlease keep this reference for any follow-up. If you have a question, reply to this email to reach our team at ${brand.support_email}.\n\nThis email confirms receipt of your request. Project scope, pricing, and timing will be discussed during review.\n\nIf you did not submit a request using this email address, no action is required.\n\nThank you,\n${brand.brand_name}`,
    });
    return result.success;
  } catch (error) {
    console.error("Error sending service request confirmation:", error);
    return false;
  }
}
