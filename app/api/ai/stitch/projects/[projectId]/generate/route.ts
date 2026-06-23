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
import { createStitchSdk, serializeProject, serializeScreen } from "@/lib/stitch/client";
import { generateStitchScreenSchema } from "@/lib/validations/stitch";

export const runtime = "nodejs";

type RouteContext = { params: Promise<{ projectId: string }> };

export async function POST(request: NextRequest, context: RouteContext) {
  try {
    const { projectId } = await context.params;
    const access = await resolveStaffAccess();
    if (!access) {
      return apiUnauthorized(request);
    }
    if (!access.isStaff) {
      return apiForbidden(request);
    }

    const body = await request.json();
    const validated = generateStitchScreenSchema.parse(body);

    const stitch = await createStitchSdk(access.clientId);
    if (!stitch) {
      return apiError(request, {
        status: 503,
        code: "SERVICE_UNAVAILABLE",
        message: "Google Stitch is not configured",
      });
    }

    const project = stitch.project(projectId);
    const screen = await project.generate(validated.prompt, validated.deviceType);
    const serializedScreen = await serializeScreen(screen);

    return apiSuccess(
      request,
      {
        project: serializeProject(project),
        screen: serializedScreen,
      },
      { status: 201 },
    );
  } catch (error) {
    if (error instanceof z.ZodError) {
      return apiValidationError(request, error);
    }
    console.error("Error generating Stitch screen:", error);
    return apiInternalError(request, "Failed to generate Stitch screen");
  }
}
