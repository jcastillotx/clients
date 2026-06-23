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

type RouteContext = { params: Promise<{ projectId: string }> };

export async function GET(request: NextRequest, context: RouteContext) {
  try {
    const { projectId } = await context.params;
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
    const screens = await project.screens();
    const serialized = await Promise.all(screens.map((screen) => serializeScreen(screen)));

    return apiSuccess(request, serialized);
  } catch (error) {
    console.error("Error listing Stitch screens:", error);
    return apiInternalError(request, "Failed to list Stitch screens");
  }
}
