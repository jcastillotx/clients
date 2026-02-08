import type { EmailOtpType } from "@supabase/supabase-js";
import { NextResponse } from "next/server";
import { createClient } from "@/lib/supabase/server";

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

  const supabase = await createClient();

  if (tokenHash && type) {
    const { error } = await supabase.auth.verifyOtp({
      token_hash: tokenHash,
      type,
    });

    if (error) {
      const loginUrl = new URL("/login", request.url);
      loginUrl.searchParams.set("error", "Could not verify your link. Please request a new one.");
      return NextResponse.redirect(loginUrl);
    }

    return NextResponse.redirect(new URL(next, request.url));
  }

  if (code) {
    const { error } = await supabase.auth.exchangeCodeForSession(code);

    if (error) {
      const loginUrl = new URL("/login", request.url);
      loginUrl.searchParams.set("error", "Could not verify your identity. Please try again.");
      return NextResponse.redirect(loginUrl);
    }

    return NextResponse.redirect(new URL(next, request.url));
  }

  return NextResponse.redirect(new URL("/login", request.url));
}
