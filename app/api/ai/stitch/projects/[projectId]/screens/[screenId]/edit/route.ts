import { NextRequest } from "next/server";
import { z } from "zod";
import { resolveStaffAccess } from "@/lib/api/resolve-staff-access";
import {
  apiError,
  apiForbidden,
  apiInternalError,
  apiSuccess,
  apiUnauthorized,
  apiValidationError,
} from "@/lib/api/response";
import { createStitchSdk, serializeScreen } from "@/lib/stitch/client";
import { editStitchScreenSchema } from "@/lib/validations/stitch";

export const runtime = "nodejs";

type RouteContext = { params: Promise<{ projectId: string; screenId: string }> };

export async function POST(request: NextRequest, context: RouteContext) {
  try {
    const { projectId, screenId } = await context.params;
    const access = await resolveStaffAccess();
    if (!access) {
      return apiUnauthorized(request);
    }
    if (!access.isStaff) {
      return apiForbidden(request);
    }

    const body = await request.json();
    const validated = editStitchScreenSchema.parse(body);

    const stitch = await createStitchSdk(access.clientId);
    if (!stitch) {
      return apiError(request, {
        status: 503,
        code: "SERVICE_UNAVAILABLE",
        message: "Google Stitch is not configured",
      });
    }

    const project = stitch.project(projectId);
    const existing = project.screen(screenId);
    const edited = await existing.edit(validated.prompt, validated.deviceType);
    const serialized = await serializeScreen(edited);

    return apiSuccess(request, serialized);
  } catch (error) {
    if (error instanceof z.ZodError) {
      return apiValidationError(request, error);
    }
    console.error("Error editing Stitch screen:", error);
    return apiInternalError(request, "Failed to edit Stitch screen");
  }
}
