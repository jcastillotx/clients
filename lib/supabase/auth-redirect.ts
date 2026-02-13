import type { EmailOtpType } from "@supabase/supabase-js";
import { NextResponse } from "next/server";
import { createClient } from "@/lib/supabase/server";
import { getAuthBaseUrl } from "@/lib/supabase/redirect-url";

function isSafeNextPath(value: string | null): value is string {
  return typeof value === "string" && value.startsWith("/") && !value.startsWith("//");
}

export async function completeSupabaseAuthFromUrl(request: Request) {
  const requestUrl = new URL(request.url);
  const code = requestUrl.searchParams.get("code");
  const tokenHash = requestUrl.searchParams.get("token_hash");
  const type = requestUrl.searchParams.get("type") as EmailOtpType | null;
  const fallbackNext = type === "recovery" ? "/reset-password" : "/dashboard";
  const next = isSafeNextPath(requestUrl.searchParams.get("next"))
    ? (requestUrl.searchParams.get("next") as string)
    : fallbackNext;
  const isRecoveryFlow = type === "recovery" || next === "/reset-password";

  // Use the canonical base URL (custom domain) instead of request.url which
  // may resolve to an internal *.vercel.app hostname on Vercel deployments.
  const baseUrl = getAuthBaseUrl();
  const redirectTarget = new URL(next, baseUrl);

  const supabase = await createClient();

  if (tokenHash && type) {
    const { error } = await supabase.auth.verifyOtp({
      token_hash: tokenHash,
      type,
    });

    if (error) {
      const errorUrl = new URL(isRecoveryFlow ? "/reset-password" : "/login", baseUrl);
      errorUrl.searchParams.set("error", "Could not verify your link. Please request a new one.");
      return NextResponse.redirect(errorUrl);
    }

    return NextResponse.redirect(redirectTarget);
  }

  if (code) {
    const { error } = await supabase.auth.exchangeCodeForSession(code);

    if (error) {
      const errorUrl = new URL(isRecoveryFlow ? "/reset-password" : "/login", baseUrl);
      const isLikelyCodeVerifierMismatch = /code verifier|pkce|both auth code and code verifier/i.test(
        error.message,
      );
      errorUrl.searchParams.set(
        "error",
        isLikelyCodeVerifierMismatch
          ? "This sign-in link was opened on a different domain. Request a new link and open it on the same domain."
          : "Could not verify your identity. Please try again.",
      );
      return NextResponse.redirect(errorUrl);
    }

    return NextResponse.redirect(redirectTarget);
  }

  return NextResponse.redirect(new URL("/login", baseUrl));
}
