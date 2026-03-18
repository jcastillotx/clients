import crypto from "crypto";

type SignedTokenPayload = Record<string, unknown> & {
  exp: number;
};

function base64UrlEncode(value: string | Buffer): string {
  return Buffer.from(value)
    .toString("base64")
    .replace(/\+/g, "-")
    .replace(/\//g, "_")
    .replace(/=+$/g, "");
}

function base64UrlDecode(value: string): string {
  const normalized = value.replace(/-/g, "+").replace(/_/g, "/");
  const padded = normalized + "=".repeat((4 - (normalized.length % 4)) % 4);
  return Buffer.from(padded, "base64").toString("utf8");
}

function sign(input: string, secret: string): string {
  return base64UrlEncode(
    crypto.createHmac("sha256", secret).update(input).digest(),
  );
}

export function createSignedToken(
  payload: Record<string, unknown>,
  secret: string,
  expiresInSeconds: number,
): string {
  const exp = Math.floor(Date.now() / 1000) + expiresInSeconds;
  const signedPayload: SignedTokenPayload = { ...payload, exp };
  const body = base64UrlEncode(JSON.stringify(signedPayload));
  const signature = sign(body, secret);
  return `${body}.${signature}`;
}

export function verifySignedToken(
  token: string,
  secret: string,
): SignedTokenPayload | null {
  const [body, signature] = token.split(".");
  if (!body || !signature) {
    return null;
  }

  const expected = sign(body, secret);
  const a = Buffer.from(signature);
  const b = Buffer.from(expected);
  if (a.length !== b.length || !crypto.timingSafeEqual(a, b)) {
    return null;
  }

  try {
    const payload = JSON.parse(base64UrlDecode(body)) as SignedTokenPayload;
    const now = Math.floor(Date.now() / 1000);
    if (!payload.exp || payload.exp < now) {
      return null;
    }
    return payload;
  } catch {
    return null;
  }
}

export function getSigningSecret(primaryEnv: string): string | null {
  const primary = process.env[primaryEnv]?.trim();
  if (primary) {
    return primary;
  }

  const fallback = process.env.ENCRYPTION_KEY?.trim();
  if (fallback) {
    return fallback;
  }

  return null;
}
