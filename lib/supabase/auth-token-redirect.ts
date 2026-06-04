export function shouldRouteToAuthConfirm(pathname: string, searchParams: URLSearchParams): boolean {
  const hasAuthTokenParam = searchParams.has("code") || searchParams.has("token_hash");

  return (
    hasAuthTokenParam &&
    !pathname.startsWith("/api/") &&
    !pathname.startsWith("/auth/confirm") &&
    !pathname.startsWith("/auth/callback")
  );
}
