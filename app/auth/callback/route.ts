import { createClient } from "@/lib/supabase/server";
import { NextResponse } from "next/server";

export async function GET(request: Request) {
  const requestUrl = new URL(request.url);
  const code = requestUrl.searchParams.get("code");
  const next = requestUrl.searchParams.get("next") || "/dashboard";
  const type = requestUrl.searchParams.get("type");

  if (code) {
    const supabase = await createClient();
    const { error } = await supabase.auth.exchangeCodeForSession(code);

    if (error) {
      // If code exchange fails, redirect to login with error
      const loginUrl = new URL("/login", request.url);
      loginUrl.searchParams.set("error", "Could not verify your identity. Please try again.");
      return NextResponse.redirect(loginUrl);
    }

    // If this is a recovery flow, always go to reset-password
    if (type === "recovery" && next === "/dashboard") {
      return NextResponse.redirect(new URL("/reset-password", request.url));
    }
  }

  return NextResponse.redirect(new URL(next, request.url));
}
