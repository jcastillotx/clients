import { createServerClient } from "@supabase/ssr";
import { NextResponse, type NextRequest } from "next/server";
import { adminRouteRequiresMfa } from "@/lib/auth/mfa-routes";
import { shouldRouteToAuthConfirm } from "@/lib/supabase/auth-token-redirect";
import { getSupabaseCookieOptions } from "@/lib/supabase/cookie-options";
import { isSupabaseConfigured } from "@/lib/env";
import { limiters, getClientIp, rateLimitLimits } from "@/lib/rate-limit";

type RoleMembershipRow = {
  role?: { name?: string | null } | Array<{ name?: string | null }> | null;
};

function normalizeMiddlewareRoleName(row: RoleMembershipRow): string {
  const role = row.role;
  const roleName = Array.isArray(role) ? role[0]?.name : role?.name;

  return String(roleName || "").toLowerCase();
}

export async function updateSession(request: NextRequest) {
  let supabaseResponse = NextResponse.next({
    request,
  });

  if (!isSupabaseConfigured()) {
    if (process.env.NODE_ENV === "production") {
      return new NextResponse("Service Unavailable", { status: 503 });
    }
    return supabaseResponse;
  }

  const supabaseUrl = process.env.NEXT_PUBLIC_SUPABASE_URL!;
  const supabaseAnonKey = process.env.NEXT_PUBLIC_SUPABASE_ANON_KEY!;

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

  // Rate limit auth-related paths (SOC2 CC6.1 — brute-force protection)
  const isAuthPath =
    request.nextUrl.pathname.startsWith("/api/auth") ||
    request.nextUrl.pathname.startsWith("/login") ||
    request.nextUrl.pathname.startsWith("/register") ||
    request.nextUrl.pathname.startsWith("/forgot-password") ||
    request.nextUrl.pathname.startsWith("/reset-password") ||
    request.nextUrl.pathname.startsWith("/auth/confirm");

  const ip = getClientIp(request);

  if (isAuthPath) {
    const result = await limiters.auth(ip);
    if (!result.success) {
      return new NextResponse("Too Many Requests", {
        status: 429,
        headers: {
          "Retry-After": String(Math.ceil((result.resetAt - Date.now()) / 1000)),
          "X-RateLimit-Limit": String(rateLimitLimits.auth),
          "X-RateLimit-Remaining": "0",
          "X-RateLimit-Reset": String(result.resetAt),
        },
      });
    }
  }

  const isPublicApiPost =
    request.method === "POST" &&
    request.nextUrl.pathname.startsWith("/api/public/");

  if (isPublicApiPost) {
    const isIntake = request.nextUrl.pathname.includes("project-requests");
    const result = isIntake
      ? await limiters.publicIntake(ip)
      : await limiters.publicPayment(ip);
    const limit = isIntake
      ? rateLimitLimits.publicIntake
      : rateLimitLimits.publicPayment;

    if (!result.success) {
      return new NextResponse(
        JSON.stringify({ error: "Too many requests. Please try again later." }),
        {
          status: 429,
          headers: {
            "Content-Type": "application/json",
            "Retry-After": String(Math.ceil((result.resetAt - Date.now()) / 1000)),
            "X-RateLimit-Limit": String(limit),
            "X-RateLimit-Remaining": "0",
            "X-RateLimit-Reset": String(result.resetAt),
          },
        },
      );
    }
  }

  // If a Supabase email auth token is present, route to /auth/confirm first
  // so the token can be exchanged/verified before we check the session.
  if (shouldRouteToAuthConfirm(request.nextUrl.pathname, request.nextUrl.searchParams)) {
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

  /** MFA (AAL2) is required for sensitive /admin/* pages, with explicit route exceptions. */
  const requiresMfa = adminRouteRequiresMfa(request.nextUrl.pathname);

  if (isAdminOnlyRoute) {
    if (!user) {
      const url = request.nextUrl.clone();
      url.pathname = "/";
      return NextResponse.redirect(url);
    }

    const [{ data: dbUser }, { data: roleRows }] = await Promise.all([
      supabase
        .from("users")
        .select("is_super_admin")
        .eq("id", user.id)
        .maybeSingle(),
      supabase
        .from("user_roles")
        .select("role:roles(name)")
        .eq("user_id", user.id),
    ]);

    const isAdmin =
      dbUser?.is_super_admin === true ||
      ((roleRows || []) as RoleMembershipRow[]).some((row) => {
        const roleName = normalizeMiddlewareRoleName(row);
        return roleName === "admin" || roleName === "super_admin";
      });

    if (!isAdmin) {
      const url = request.nextUrl.clone();
      url.pathname = "/dashboard";
      return NextResponse.redirect(url);
    }

    // MFA enforcement for /admin panel routes (SOC2 CC6.1, CC6.3)
    // Skip when already on settings/security to avoid a redirect loop.
    const isOnSecuritySettings = request.nextUrl.pathname.startsWith("/settings/security");

    if (requiresMfa && !isOnSecuritySettings) {
      try {
        const { data: aalData } = await supabase.auth.mfa.getAuthenticatorAssuranceLevel();
        if (aalData && aalData.currentLevel !== "aal2") {
          const url = request.nextUrl.clone();
          url.pathname = "/settings/security";
          url.searchParams.set("mfa_required", "1");
          url.searchParams.set("next", `${request.nextUrl.pathname}${request.nextUrl.search}`);
          return NextResponse.redirect(url);
        }
      } catch {
        // If AAL check fails (e.g. during initial setup), allow through
      }
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
