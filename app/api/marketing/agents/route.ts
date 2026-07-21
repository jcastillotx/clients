import { and, desc, eq, isNull } from "drizzle-orm";
import { z } from "zod";
import { resolveStaffAccess } from "@/lib/api/resolve-staff-access";
import {
  apiForbidden,
  apiInternalError,
  apiSuccess,
  apiUnauthorized,
  apiValidationError,
} from "@/lib/api/response";
import { rateLimitExceededResponse } from "@/lib/api/rate-limit-response";
import { getMarketingAgentWorkflow } from "@/lib/ai/marketing-agents/catalog";
import {
  isMarketingAgentTask,
  toMarketingAgentRunSummary,
} from "@/lib/ai/marketing-agents/runs";
import { marketingAgentRunRequestSchema } from "@/lib/ai/marketing-agents/validation";
import { db } from "@/lib/db";
import { aiTasks } from "@/lib/db/schema/ai-features";
import { clients } from "@/lib/db/schema/clients";
import { inngest } from "@/lib/inngest/client";
import { rateLimit } from "@/lib/rate-limit";

const MARKETING_AGENT_RUN_LIMIT = 12;
const MARKETING_AGENT_RUN_WINDOW_MS = 60 * 60 * 1000;

const listSchema = z.object({
  clientId: z.string().uuid(),
});

async function requireStaff(request: Request) {
  const access = await resolveStaffAccess();
  if (!access) return { error: apiUnauthorized(request) };
  if (!access.isStaff) return { error: apiForbidden(request) };
  return { access };
}

async function clientExists(clientId: string): Promise<boolean> {
  const [client] = await db
    .select({ id: clients.id })
    .from(clients)
    .where(and(eq(clients.id, clientId), isNull(clients.deletedAt)))
    .limit(1);
  return Boolean(client);
}

export async function GET(request: Request) {
  try {
    const auth = await requireStaff(request);
    if ("error" in auth) return auth.error;

    const parsed = listSchema.safeParse({
      clientId: new URL(request.url).searchParams.get("clientId"),
    });
    if (!parsed.success) return apiValidationError(request, parsed.error);

    const rows = await db
      .select()
      .from(aiTasks)
      .where(
        and(
          eq(aiTasks.clientId, parsed.data.clientId),
          eq(aiTasks.taskType, "custom"),
        ),
      )
      .orderBy(desc(aiTasks.createdAt))
      .limit(50);
    const runs = rows
      .filter(isMarketingAgentTask)
      .map(toMarketingAgentRunSummary)
      .filter((run) => run !== null)
      .slice(0, 25);

    return apiSuccess(request, runs);
  } catch (error) {
    console.error("Error listing marketing agent runs:", error);
    return apiInternalError(request, "Failed to list marketing agent runs");
  }
}

export async function POST(request: Request) {
  try {
    const auth = await requireStaff(request);
    if ("error" in auth) return auth.error;

    const rateLimitResult = await rateLimit(
      auth.access.userId,
      {
        limit: MARKETING_AGENT_RUN_LIMIT,
        windowMs: MARKETING_AGENT_RUN_WINDOW_MS,
      },
      "marketing-agent-run",
    );
    if (!rateLimitResult.success) {
      return rateLimitExceededResponse(
        request,
        rateLimitResult,
        MARKETING_AGENT_RUN_LIMIT,
      );
    }

    const requestBody = await request.json().catch(() => null);
    const parsed = marketingAgentRunRequestSchema.safeParse(requestBody);
    if (!parsed.success) return apiValidationError(request, parsed.error);

    const input = parsed.data;
    if (!(await clientExists(input.clientId))) {
      return apiForbidden(request, "Client is unavailable");
    }

    const workflow = getMarketingAgentWorkflow(input.workflowId);
    const [task] = await db
      .insert(aiTasks)
      .values({
        clientId: input.clientId,
        userId: auth.access.userId,
        taskType: "custom",
        input: {
          prompt: workflow.name,
          parameters: input,
        },
        status: "pending",
        metadata: {
          workflowId: workflow.id,
          approvalStatus: "pending_approval",
          priority: 5,
        },
      })
      .returning();

    if (!task) {
      throw new Error("Failed to create marketing agent task");
    }

    try {
      await inngest.send({
        name: "marketing-agent/run.requested",
        data: {
          taskId: task.id,
          clientId: input.clientId,
          userId: auth.access.userId,
        },
      });
    } catch (eventError) {
      await db
        .update(aiTasks)
        .set({
          status: "failed",
          error: "The marketing agent worker could not be started.",
          completedAt: new Date(),
          updatedAt: new Date(),
        })
        .where(eq(aiTasks.id, task.id));
      throw eventError;
    }

    return apiSuccess(
      request,
      toMarketingAgentRunSummary(task),
      { status: 202 },
    );
  } catch (error) {
    console.error("Error starting marketing agent run:", error);
    return apiInternalError(request, "Failed to start marketing agent run");
  }
}
