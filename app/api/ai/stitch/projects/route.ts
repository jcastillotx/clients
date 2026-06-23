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
import { createStitchSdk, serializeProject } from "@/lib/stitch/client";
import { createStitchProjectSchema } from "@/lib/validations/stitch";

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

    const stitch = await createStitchSdk(access.clientId);
    if (!stitch) {
      return apiError(request, {
        status: 503,
        code: "SERVICE_UNAVAILABLE",
        message: "Google Stitch is not configured",
      });
    }

    const projects = await stitch.projects();
    return apiSuccess(request, projects.map(serializeProject));
  } catch (error) {
    console.error("Error listing Stitch projects:", error);
    return apiInternalError(request, "Failed to list Stitch projects");
  }
}

export async function POST(request: NextRequest) {
  try {
    const access = await resolveStaffAccess();
    if (!access) {
      return apiUnauthorized(request);
    }
    if (!access.isStaff) {
      return apiForbidden(request);
    }

    const body = await request.json();
    const validated = createStitchProjectSchema.parse(body);

    const stitch = await createStitchSdk(access.clientId);
    if (!stitch) {
      return apiError(request, {
        status: 503,
        code: "SERVICE_UNAVAILABLE",
        message: "Google Stitch is not configured",
      });
    }

    const project = await stitch.createProject(validated.title);
    return apiSuccess(request, serializeProject(project), { status: 201 });
  } catch (error) {
    if (error instanceof z.ZodError) {
      return apiValidationError(request, error);
    }
    console.error("Error creating Stitch project:", error);
    return apiInternalError(request, "Failed to create Stitch project");
  }
}
