import { NextRequest, NextResponse } from "next/server";
import { Webhook } from "standardwebhooks";
import { sendEmail } from "@/lib/email/client";
import {
  authTemplateTypeForAction,
  buildAuthConfirmUrl,
  type AuthHookEmailPayload,
} from "@/lib/email/auth-hook";
import { renderEmailTemplateAsAdmin } from "@/lib/email/templates";
import { getAuthBaseUrl } from "@/lib/supabase/redirect-url";

export const dynamic = "force-dynamic";

function getWebhookVerifier(): Webhook {
  const raw =
    process.env.SUPABASE_AUTH_HOOK_SECRET?.trim() ||
    process.env.SEND_EMAIL_HOOK_SECRET?.trim() ||
    "";
  if (!raw) {
    throw new Error("Missing SUPABASE_AUTH_HOOK_SECRET (or SEND_EMAIL_HOOK_SECRET) for Send Email hook");
  }
  const normalized = raw.startsWith("v1,") ? raw.slice(3) : raw;
  return new Webhook(normalized);
}

function defaultSubjectForAction(action: string): string {
  switch (action) {
    case "signup":
      return "Confirm your email";
    case "recovery":
      return "Reset your password";
    case "magiclink":
      return "Your sign-in link";
    case "invite":
      return "You have been invited";
    case "email_change":
      return "Confirm your email change";
    default:
      return "Security notification";
  }
}

export async function POST(request: NextRequest) {
  try {
    const verifier = getWebhookVerifier();
    const rawBody = await request.text();
    const headers: Record<string, string> = {};
    request.headers.forEach((value, key) => {
      headers[key] = value;
    });

    const payload = verifier.verify(rawBody, headers) as AuthHookEmailPayload;
    const { user, email_data } = payload;
    const action = email_data.email_action_type;
    const to = user.email;
    if (!to) {
      return NextResponse.json({ error: "Missing user email" }, { status: 400 });
    }

    const siteUrl = getAuthBaseUrl();
    const templateType = authTemplateTypeForAction(action);

    // Secure email change: two messages (current + new address). See Supabase docs for token/hash pairing.
    if (action === "email_change" && email_data.token_new && user.new_email) {
      const urlCurrent = buildAuthConfirmUrl({
        tokenHash: email_data.token_hash_new,
        emailActionType: "email_change",
        redirectTo: email_data.redirect_to,
      });
      const urlNew = buildAuthConfirmUrl({
        tokenHash: email_data.token_hash,
        emailActionType: "email_change",
        redirectTo: email_data.redirect_to,
      });

      const baseCtx = {
        site_url: siteUrl,
        token: email_data.token,
        token_new: email_data.token_new,
        redirect_to: email_data.redirect_to,
        email_action_type: action,
      };

      const renderedOld = await renderEmailTemplateAsAdmin("auth_email_change_current", {
        ...baseCtx,
        email: user.email,
        confirmation_url: urlCurrent,
        otp: email_data.token,
      });
      const renderedNew = await renderEmailTemplateAsAdmin("auth_email_change_new", {
        ...baseCtx,
        email: user.new_email,
        confirmation_url: urlNew,
        otp: email_data.token_new,
      });

      await sendEmail({
        to,
        subject: renderedOld?.subject ?? "Confirm email change",
        html: renderedOld?.html,
        text: renderedOld?.plainText ?? `Confirm email change: ${urlCurrent} (code: ${email_data.token})`,
      });
      await sendEmail({
        to: user.new_email,
        subject: renderedNew?.subject ?? "Confirm your new email",
        html: renderedNew?.html,
        text: renderedNew?.plainText ?? `Confirm new email: ${urlNew} (code: ${email_data.token_new})`,
      });

      return NextResponse.json({});
    }

    const confirmationUrl = buildAuthConfirmUrl({
      tokenHash: email_data.token_hash,
      emailActionType: action,
      redirectTo: email_data.redirect_to,
    });

    const ctx = {
      site_url: siteUrl,
      confirmation_url: confirmationUrl,
      token: email_data.token,
      email: to,
      redirect_to: email_data.redirect_to,
      email_action_type: action,
      app_name: process.env.NEXT_PUBLIC_APP_NAME || "Kre8iv Clients",
    };

    const rendered = await renderEmailTemplateAsAdmin(templateType, ctx);

    if (rendered) {
      await sendEmail({
        to,
        subject: rendered.subject,
        html: rendered.html,
        text: rendered.plainText,
      });
      return NextResponse.json({});
    }

    await sendEmail({
      to,
      subject: defaultSubjectForAction(action),
      html: `<p>${defaultSubjectForAction(action)}</p><p><a href="${confirmationUrl}">Continue</a></p><p>Or enter code: <strong>${email_data.token}</strong></p>`,
      text: `${defaultSubjectForAction(action)}\n\n${confirmationUrl}\n\nCode: ${email_data.token}`,
    });
    return NextResponse.json({});
  } catch (e) {
    console.error("[auth/hooks/send-email]", e);
    return NextResponse.json(
      { error: e instanceof Error ? e.message : "Webhook verification failed" },
      { status: 401 },
    );
  }
}
