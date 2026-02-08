export function getSupabaseCookieOptions() {
  const domain = process.env.NEXT_PUBLIC_AUTH_COOKIE_DOMAIN;

  return {
    ...(domain ? { domain } : {}),
    path: "/",
    sameSite: "lax" as const,
    secure: process.env.NODE_ENV === "production",
    httpOnly: false,
  };
}
