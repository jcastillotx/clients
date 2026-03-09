import { createServerClient } from "@supabase/ssr";
import { NextResponse, type NextRequest } from "next/server";
import { getSupabaseCookieOptions } from "@/lib/supabase/cookie-options";

export async function updateSession(request: NextRequest) {
  let supabaseResponse = NextResponse.next({
    request,
  });

  const supabaseUrl = process.env.NEXT_PUBLIC_SUPABASE_URL;
  const supabaseAnonKey = process.env.NEXT_PUBLIC_SUPABASE_ANON_KEY;

  if (!supabaseUrl || !supabaseAnonKey) {
    // Supabase not configured — skip auth checks
    return supabaseResponse;
  }

  const supabase = createServerClient(
    supabaseUrl,
    supabaseAnonKey,
    {
      cookieOptions: getSupabaseCookieOptions(),
      cookies: {
        getAll() {
          return request.cookies.getAll();
        },
        setAll(cookiesToSet) {
          cookiesToSet.forEach(({ name, value }) =>
            request.cookies.set(name, value)
          );
          supabaseResponse = NextResponse.next({
            request,
          });
          cookiesToSet.forEach(({ name, value, options }) =>
            supabaseResponse.cookies.set(name, value, options)
          );
        },
      },
    }
  );

  // If a Supabase email auth token is present, route to /auth/confirm first
  // so the token can be exchanged/verified before we check the session.
  const code = request.nextUrl.searchParams.get("code");
  const tokenHash = request.nextUrl.searchParams.get("token_hash");
  const hasAuthTokenParam = Boolean(code || tokenHash);
  if (hasAuthTokenParam && !request.nextUrl.pathname.startsWith("/auth/confirm") && !request.nextUrl.pathname.startsWith("/auth/callback")) {
    const confirmUrl = request.nextUrl.clone();
    confirmUrl.pathname = "/auth/confirm";
    if (!confirmUrl.searchParams.has("next") && request.nextUrl.searchParams.get("type") === "recovery") {
      confirmUrl.searchParams.set("next", "/reset-password");
    }
    return NextResponse.redirect(confirmUrl);
  }

  // Do not run code between createServerClient and
  // supabase.auth.getUser(). A simple mistake could make it very hard to debug
  // issues with users being randomly logged out.

  const {
    data: { user },
  } = await supabase.auth.getUser();

  // Protect dashboard routes
  if (request.nextUrl.pathname.startsWith("/dashboard") && !user) {
    const url = request.nextUrl.clone();
    url.pathname = "/";
    return NextResponse.redirect(url);
  }

  // Protect admin-only routes (admin panel + integrations)
  const isAdminOnlyRoute =
    request.nextUrl.pathname.startsWith("/admin") ||
    request.nextUrl.pathname.startsWith("/integrations");

  if (isAdminOnlyRoute) {
    if (!user) {
      const url = request.nextUrl.clone();
      url.pathname = "/";
      return NextResponse.redirect(url);
    }

    const metadataRole = String(user.user_metadata?.role || user.user_metadata?.app_role || "").toLowerCase();
    let isAdmin = Boolean(
      user.user_metadata?.is_super_admin === true || metadataRole === "admin" || metadataRole === "super_admin",
    );

    if (!isAdmin) {
      const { data: roleRows } = await supabase
        .from("user_roles")
        .select("role:roles(name)")
        .eq("user_id", user.id);

      isAdmin = (roleRows || []).some((row: any) => {
        const roleName = String(row?.role?.name || row?.role?.[0]?.name || "").toLowerCase();
        return roleName === "admin" || roleName === "super_admin";
      });
    }

    if (!isAdmin) {
      const url = request.nextUrl.clone();
      url.pathname = "/dashboard";
      return NextResponse.redirect(url);
    }
  }

  // Redirect to dashboard if already logged in and visiting auth pages
  if (
    (request.nextUrl.pathname === "/" ||
      request.nextUrl.pathname === "/login" ||
      request.nextUrl.pathname === "/register") &&
    user
  ) {
    const hasAuthMessage = request.nextUrl.searchParams.has("error") || request.nextUrl.searchParams.has("message");
    if (hasAuthMessage) {
      return supabaseResponse;
    }
    const url = request.nextUrl.clone();
    url.pathname = "/dashboard";
    url.search = "";
    return NextResponse.redirect(url);
  }

  // IMPORTANT: You *must* return the supabaseResponse object as-is.
  // This ensures cookies are properly forwarded to the browser.
  return supabaseResponse;
}
