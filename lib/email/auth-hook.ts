import { getAuthBaseUrl } from "@/lib/supabase/redirect-url";

/** Maps Supabase Auth Hook `email_action_type` to `email_templates.type` rows. */
export function authTemplateTypeForAction(emailActionType: string): string {
  const map: Record<string, string> = {
    signup: "auth_signup",
    invite: "auth_invite",
    magiclink: "auth_magiclink",
    recovery: "auth_recovery",
    email_change: "auth_email_change",
    email: "auth_magiclink",
    reauthentication: "auth_magiclink",
    password_changed_notification: "auth_generic",
    email_changed_notification: "auth_generic",
    phone_changed_notification: "auth_generic",
    identity_linked_notification: "auth_generic",
    identity_unlinked_notification: "auth_generic",
    mfa_factor_enrolled_notification: "auth_generic",
    mfa_factor_unenrolled_notification: "auth_generic",
  };
  return map[emailActionType] ?? "auth_generic";
}

/**
 * OTP `type` query param for `/auth/confirm` → supabase.auth.verifyOtp({ type }).
 */
export function otpQueryTypeForAction(emailActionType: string): string {
  switch (emailActionType) {
    case "invite":
      return "signup";
    case "magiclink":
      return "email";
    case "signup":
      return "signup";
    case "recovery":
      return "recovery";
    case "email_change":
      return "email_change";
    case "email":
      return "email";
    case "reauthentication":
      return "email";
    default:
      return "signup";
  }
}

function isSafeNextPath(value: string | null | undefined): value is string {
  if (!value || typeof value !== "string") return false;
  return value.startsWith("/") && !value.startsWith("//") && !value.includes("..");
}

export function buildAuthConfirmUrl(params: {
  tokenHash: string;
  emailActionType: string;
  redirectTo: string;
}): string {
  const base = getAuthBaseUrl();
  const u = new URL("/auth/confirm", base);
  u.searchParams.set("token_hash", params.tokenHash);
  u.searchParams.set("type", otpQueryTypeForAction(params.emailActionType));
  if (isSafeNextPath(params.redirectTo)) {
    u.searchParams.set("next", params.redirectTo);
  }
  return u.toString();
}

export type AuthHookEmailPayload = {
  user: {
    id: string;
    email?: string;
    new_email?: string;
    user_metadata?: Record<string, unknown>;
  };
  email_data: {
    token: string;
    token_hash: string;
    redirect_to: string;
    email_action_type: string;
    site_url: string;
    token_new: string;
    token_hash_new: string;
  };
};
