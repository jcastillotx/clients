import { isTurnstileEnforced } from "@/lib/turnstile/config";

interface TurnstileVerifyResponse {
  success: boolean;
  "error-codes"?: string[];
}

export async function verifyTurnstileToken(
  token: string,
  remoteIp?: string,
): Promise<boolean> {
  const secret = process.env.TURNSTILE_SECRET_KEY?.trim();
  if (!secret) {
    return false;
  }

  const body = new URLSearchParams();
  body.set("secret", secret);
  body.set("response", token);
  if (remoteIp && remoteIp !== "unknown") {
    body.set("remoteip", remoteIp);
  }

  const response = await fetch(
    "https://challenges.cloudflare.com/turnstile/v0/siteverify",
    {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: body.toString(),
    },
  );

  if (!response.ok) {
    return false;
  }

  const payload = (await response.json()) as TurnstileVerifyResponse;
  return payload.success === true;
}

export type TurnstileAssertionResult =
  | { ok: true }
  | { ok: false; error: string; status: 400 | 503 };

/**
 * Validates Turnstile when configured. Skips when keys are absent (local dev).
 */
export async function assertTurnstileToken(
  token: string | undefined | null,
  remoteIp?: string,
): Promise<TurnstileAssertionResult> {
  if (!isTurnstileEnforced()) {
    return { ok: true };
  }

  if (!token?.trim()) {
    return {
      ok: false,
      error: "CAPTCHA verification is required.",
      status: 400,
    };
  }

  try {
    const valid = await verifyTurnstileToken(token.trim(), remoteIp);
    if (!valid) {
      return {
        ok: false,
        error: "CAPTCHA verification failed. Please try again.",
        status: 400,
      };
    }
    return { ok: true };
  } catch {
    return {
      ok: false,
      error: "CAPTCHA verification is temporarily unavailable.",
      status: 503,
    };
  }
}
