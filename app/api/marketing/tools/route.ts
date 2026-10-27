import { and, desc, eq, sql } from "drizzle-orm";
import {
  apiError,
  apiForbidden,
  apiInternalError,
  apiSuccess,
  apiUnauthorized,
  apiValidationError,
} from "@/lib/api/response";
import { rateLimitExceededResponse } from "@/lib/api/rate-limit-response";
import { resolveMarketingToolAccess } from "@/lib/ai/marketing-skills/access";
import {
  getMarketingSkill,
  marketingSkillSummaries,
} from "@/lib/ai/marketing-skills/catalog";
import {
  executeMarketingSkill,
  SKILL_TASK_WORKFLOW,
  skillRunSchema,
  skillArtifactSchema,
} from "@/lib/ai/marketing-skills/execution";
import {
  getMarketingSimulatorUrl,
  hasMarketingAiProvider,
  getMarketingAiProviderStatus,
  MarketingAgentProviderError,
} from "@/lib/ai/marketing-agents/provider";
import { db } from "@/lib/db";
import { aiTasks, aiUsageTracking } from "@/lib/db/schema/ai-features";
import { rateLimit } from "@/lib/rate-limit";
import { resolveStaffAccess } from "@/lib/api/resolve-staff-access";

export const maxDuration = 120;
function accessError(request: Request, error: string) {
  if (error === "unauthorized") return apiUnauthorized(request);
  return apiForbidden(
    request,
    error === "disabled"
      ? "Marketing tools are disabled for this account."
      : "Client is unavailable.",
  );
}
export async function GET(request: Request) {
  try {
    const url = new URL(request.url);
    const scope = await resolveMarketingToolAccess(
      url.searchParams.get("clientId"),
    );
    if (scope.error) return accessError(request, scope.error);
    const skillId = url.searchParams.get("skillId");
    if (skillId) {
      const skill = getMarketingSkill(skillId);
      if (!skill)
        return apiError(request, {
          status: 404,
          code: "NOT_FOUND",
          message: "Marketing tool not found.",
        });
      return apiSuccess(request, { ...skill, reference: undefined });
    }
    const history = await db
      .select({
        id: aiTasks.id,
        input: aiTasks.input,
        output: aiTasks.output,
        status: aiTasks.status,
        error: aiTasks.error,
        metadata: aiTasks.metadata,
        createdAt: aiTasks.createdAt,
      })
      .from(aiTasks)
      .where(
        and(
          eq(aiTasks.clientId, scope.clientId),
          eq(aiTasks.taskType, "custom"),
          sql`${aiTasks.input}->'parameters'->>'workflowId' = ${SKILL_TASK_WORKFLOW}`,
        ),
      )
      .orderBy(desc(aiTasks.createdAt))
      .limit(30);
    const providerStatus = await getMarketingAiProviderStatus(scope.clientId);
    return apiSuccess(request, {
      clientId: scope.clientId,
      tools: marketingSkillSummaries(),
      providerReady: providerStatus.ready,
      providerName: providerStatus.provider,
      simulated: Boolean(getMarketingSimulatorUrl()),
      history: history.flatMap(({ input, output, ...run }) => {
        const skillId = input.parameters?.skillId;
        if (typeof skillId !== "string" || !getMarketingSkill(skillId))
          return [];
        const artifact = skillArtifactSchema.safeParse(output?.result);
        return [
          {
            ...run,
            skillId,
            output: artifact.success ? { result: artifact.data } : null,
          },
        ];
      }),
    });
  } catch (error) {
    console.error(
      "Error loading marketing tools:",
      error instanceof Error ? error.name : "unknown",
    );
    return apiInternalError(request, "Unable to load marketing tools.");
  }
}
export async function POST(request: Request) {
  let taskId: string | undefined;
  try {
    if (!(await resolveStaffAccess())) return apiUnauthorized(request);
    const text = await request.text();
    if (text.length > 60000)
      return apiError(request, {
        status: 413,
        code: "BAD_REQUEST",
        message:
          "Use a brief and evidence totaling fewer than 60,000 characters.",
      });
    let body: unknown;
    try {
      body = JSON.parse(text);
    } catch {
      return apiError(request, {
        status: 400,
        code: "BAD_REQUEST",
        message: "Invalid JSON request.",
      });
    }
    const parsed = skillRunSchema.safeParse(body);
    if (!parsed.success) return apiValidationError(request, parsed.error);
    const scope = await resolveMarketingToolAccess(parsed.data.clientId);
    if (scope.error) return accessError(request, scope.error);
    const limit = process.env.STAGING_ISOLATED === "1" ? 120 : 12;
    const allowed = await rateLimit(
      scope.clientId,
      { limit, windowMs: 3600000 },
      "marketing-skill-run",
    );
    if (!allowed.success)
      return rateLimitExceededResponse(request, allowed, limit);
    if (!(await hasMarketingAiProvider(scope.clientId)))
      return apiError(request, {
        status: 503,
        code: "SERVICE_UNAVAILABLE",
        message:
          "AI drafting is not configured for this account. Your account manager can connect its AI provider. The tool methodology remains available.",
      });
    const skill = getMarketingSkill(parsed.data.skillId)!;
    const simulated = Boolean(getMarketingSimulatorUrl());
    const [task] = await db
      .insert(aiTasks)
      .values({
        clientId: scope.clientId,
        userId: scope.access.userId,
        taskType: "custom",
        input: {
          prompt: skill.name,
          parameters: {
            ...parsed.data,
            clientId: scope.clientId,
            workflowId: SKILL_TASK_WORKFLOW,
          },
        },
        status: "processing",
        startedAt: new Date(),
        metadata: {
          workflowId: SKILL_TASK_WORKFLOW,
          approvalStatus: "pending_approval",
          provider: simulated ? "local_simulator" : undefined,
        },
      })
      .returning({ id: aiTasks.id });
    taskId = task.id;
    const result = await executeMarketingSkill(scope.clientId, parsed.data);
    await db.insert(aiUsageTracking).values({
      clientId: scope.clientId,
      userId: scope.access.userId,
      provider: simulated ? "local_simulator" : result.usage.provider,
      model: result.usage.model,
      tokensUsed: result.usage.totalTokens,
      cost: result.usage.estimatedCost.toFixed(6),
      requestType: "marketing_skill",
      metadata: { taskId, workflowId: skill.id },
    });
    await db
      .update(aiTasks)
      .set({
        output: { result: result.value },
        status: "completed",
        completedAt: new Date(),
        updatedAt: new Date(),
        metadata: {
          workflowId: SKILL_TASK_WORKFLOW,
          approvalStatus: "pending_approval",
          provider: simulated ? "local_simulator" : result.usage.provider,
          model: result.usage.model,
          tokensUsed: result.usage.totalTokens,
          cost: result.usage.estimatedCost,
        },
      })
      .where(eq(aiTasks.id, taskId));
    return apiSuccess(
      request,
      {
        id: taskId,
        skillId: skill.id,
        artifact: result.value,
        simulated,
        approvalStatus: "pending_approval",
      },
      { status: 201 },
    );
  } catch (error) {
    if (taskId)
      await db
        .update(aiTasks)
        .set({
          status: "failed",
          error:
            "The tool could not complete the draft. Please retry or contact your account manager.",
          completedAt: new Date(),
          updatedAt: new Date(),
        })
        .where(eq(aiTasks.id, taskId));
    console.error(
      "Error running marketing skill:",
      error instanceof Error ? error.name : "unknown",
    );
    return error instanceof MarketingAgentProviderError
      ? apiError(request, {
          status: 502,
          code: "SERVICE_UNAVAILABLE",
          message: error.message,
        })
      : apiInternalError(request, "Unable to complete the marketing draft.");
  }
}
