import { NextRequest } from "next/server";
import { z } from "zod";
import { sql } from "drizzle-orm";
import { db } from "@/lib/db";
import { getClientIp, rateLimit } from "@/lib/rate-limit";
import { assertTurnstileToken } from "@/lib/turnstile/verify";
import { inviteMarketingBuyer } from "@/lib/marketing/purchase-access";
import { apiError, apiSuccess, apiValidationError, apiInternalError } from "@/lib/api/response";

const schema = z.object({
  planId: z.string().guid(), name: z.string().trim().min(2).max(100),
  company: z.string().trim().min(2).max(150), email: z.string().trim().email().max(254).transform(value => value.toLowerCase()),
  contactFax: z.string().max(200).optional(), turnstileToken: z.string().max(4096).optional(),
});
export async function POST(request: NextRequest) {
  try {
    if (request.headers.get("origin") !== new URL(process.env.NEXT_PUBLIC_APP_URL!).origin) return apiError(request, { status: 403, code: "FORBIDDEN", message: "Invalid request origin." });
    let body: unknown;
    try { body = await request.json(); } catch { return apiError(request, { status: 400, code: "BAD_REQUEST", message: "Invalid request body." }); }
    const parsed = schema.safeParse(body);
    if (!parsed.success) return apiValidationError(request, parsed.error);
    if (parsed.data.contactFax) return apiError(request, { status: 400, code: "BAD_REQUEST", message: "Unable to continue." });
    const limited = await rateLimit(getClientIp(request), { limit: 3, windowMs: 3600000 }, "marketing-access");
    const emailLimited = await rateLimit(parsed.data.email, { limit: 2, windowMs: 3600000 }, "marketing-access-email");
    if (!limited.success || !emailLimited.success) return apiError(request, { status: 429, code: "RATE_LIMITED", message: "Please wait before requesting another invitation." });
    const captcha = await assertTurnstileToken(parsed.data.turnstileToken, getClientIp(request));
    if (!captcha.ok) return apiError(request, { status: captcha.status, code: "BAD_REQUEST", message: captcha.error });
    const [plan] = await db.execute(sql`SELECT id FROM marketing_plans WHERE id=${parsed.data.planId}::uuid AND published AND is_active AND monthly_price_cents>0`);
    if (!plan) return apiError(request, { status: 400, code: "BAD_REQUEST", message: "This plan is unavailable." });
    await inviteMarketingBuyer(parsed.data);
    return apiSuccess(request, { message: "Check your email to continue. If you already have a portal account, sign in or use password recovery. No payment has been collected." });
  } catch {
    return apiInternalError(request, "Unable to send purchase access. Please try again or contact support.");
  }
}
