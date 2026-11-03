import securityHeaders from "@/config/security-headers.json";

export function createContentSecurityPolicy(
  nonce: string,
  local = false,
  development = false,
): string {
  const base = securityHeaders[0].headers.find(
    (header) => header.key === "Content-Security-Policy",
  )!.value;
  let policy = base.replace(
    /script-src [^;]+/,
    `script-src 'self' 'nonce-${nonce}' 'strict-dynamic'${development ? " 'unsafe-eval'" : ""} https://js.stripe.com https://challenges.cloudflare.com`,
  );
  policy += "; frame-ancestors 'none'; script-src-attr 'none'";
  if (local) {
    policy = policy
      .replace(/upgrade-insecure-requests;?/g, "")
      .replace(
        "connect-src ",
        "connect-src http://127.0.0.1:55321 ws://127.0.0.1:55321 ",
      )
      .replace(
        "img-src ",
        "img-src http://127.0.0.1:55321 http://127.0.0.1:59000 ",
      );
  }
  return policy;
}
