import { and, eq } from "drizzle-orm";
import { z } from "zod";
import { resolveStaffAccess } from "@/lib/api/resolve-staff-access";
import {
  apiForbidden,
  apiInternalError,
  apiNotFound,
  apiSuccess,
  apiUnauthorized,
} from "@/lib/api/response";
import { toMarketingAgentRunSummary } from "@/lib/ai/marketing-agents/runs";
import { db } from "@/lib/db";
import { aiTasks } from "@/lib/db/schema/ai-features";

type RouteContext = { params: Promise<{ id: string }> };

export async function GET(request: Request, context: RouteContext) {
  try {
    const access = await resolveStaffAccess();
    if (!access) return apiUnauthorized(request);
    if (!access.isStaff) return apiForbidden(request);

    const { id } = await context.params;
    if (!z.string().uuid().safeParse(id).success) {
      return apiNotFound(request, "Marketing agent run not found");
    }

    const [task] = await db
      .select()
      .from(aiTasks)
      .where(and(eq(aiTasks.id, id), eq(aiTasks.taskType, "custom")))
      .limit(1);
    const run = task ? toMarketingAgentRunSummary(task) : null;

    if (!run) return apiNotFound(request, "Marketing agent run not found");

    return apiSuccess(request, run);
  } catch (error) {
    console.error("Error fetching marketing agent run:", error);
    return apiInternalError(request, "Failed to fetch marketing agent run");
  }
}
