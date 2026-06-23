import { NextRequest } from "next/server";
import { resolveStaffAccess } from "@/lib/api/resolve-staff-access";
import {
  apiError,
  apiForbidden,
  apiInternalError,
  apiSuccess,
  apiUnauthorized,
} from "@/lib/api/response";
import { createStitchSdk, serializeScreen } from "@/lib/stitch/client";

export const runtime = "nodejs";

type RouteContext = { params: Promise<{ projectId: string; screenId: string }> };

export async function GET(request: NextRequest, context: RouteContext) {
  try {
    const { projectId, screenId } = await context.params;
    const access = await resolveStaffAccess();
    if (!access) {
      return apiUnauthorized(request);
    }
    if (!access.isStaff) {
      return apiForbidden(request);
    }

    const stitch = await createStitchSdk(access.clientId);
    if (!stitch) {
      return apiError(request, {
        status: 503,
        code: "SERVICE_UNAVAILABLE",
        message: "Google Stitch is not configured",
      });
    }

    const project = stitch.project(projectId);
    const screen = await project.getScreen(screenId);
    const serialized = await serializeScreen(screen);

    return apiSuccess(request, serialized);
  } catch (error) {
    console.error("Error fetching Stitch screen:", error);
    return apiInternalError(request, "Failed to fetch Stitch screen");
  }
}
