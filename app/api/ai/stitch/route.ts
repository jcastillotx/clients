import { NextRequest } from "next/server";
import { resolveStaffAccess } from "@/lib/api/resolve-staff-access";
import {
  apiError,
  apiForbidden,
  apiInternalError,
  apiSuccess,
  apiUnauthorized,
} from "@/lib/api/response";
import { isStitchConfigured, resolveStitchApiKey } from "@/lib/stitch/resolve-api-key";

export const runtime = "nodejs";

export async function GET(request: NextRequest) {
  try {
    const access = await resolveStaffAccess();
    if (!access) {
      return apiUnauthorized(request);
    }
    if (!access.isStaff) {
      return apiForbidden(request);
    }

    const apiKey = await resolveStitchApiKey(access.clientId);
    if (!isStitchConfigured(apiKey)) {
      return apiError(request, {
        status: 503,
        code: "SERVICE_UNAVAILABLE",
        message:
          "Google Stitch is not configured. Add STITCH_API_KEY or save a Stitch API key in Settings → Integrations.",
      });
    }

    return apiSuccess(request, {
      configured: true,
      source: access.clientId ? "client_or_env" : "env",
    });
  } catch (error) {
    console.error("Error checking Stitch status:", error);
    return apiInternalError(request, "Failed to check Stitch configuration");
  }
}
