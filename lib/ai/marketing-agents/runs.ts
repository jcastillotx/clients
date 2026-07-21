import type {
  MarketingAgentRunResult,
  MarketingAgentRunSummary,
  MarketingAgentWorkflowId,
} from "./types";
import { getMarketingAgentWorkflow } from "./catalog";
import { marketingAgentWorkflowIds } from "./types";

interface MarketingAgentTaskRow {
  id: string;
  clientId: string | null;
  input: {
    parameters?: Record<string, unknown>;
  };
  output: {
    result?: unknown;
  } | null;
  status: MarketingAgentRunSummary["status"];
  error: string | null;
  metadata: {
    approvalStatus?: "pending_approval";
  } | null;
  createdAt: Date;
  updatedAt: Date;
  completedAt: Date | null;
}

function isWorkflowId(value: unknown): value is MarketingAgentWorkflowId {
  return marketingAgentWorkflowIds.includes(
    value as MarketingAgentWorkflowId,
  );
}

function isMarketingAgentResult(
  value: unknown,
): value is MarketingAgentRunResult {
  if (!value || typeof value !== "object") return false;
  const workflowId = (value as { workflowId?: unknown }).workflowId;
  return isWorkflowId(workflowId);
}

export function isMarketingAgentTask(row: MarketingAgentTaskRow): boolean {
  return isWorkflowId(row.input.parameters?.workflowId);
}

export function toMarketingAgentRunSummary(
  row: MarketingAgentTaskRow,
): MarketingAgentRunSummary | null {
  const workflowId = row.input.parameters?.workflowId;
  if (!isWorkflowId(workflowId)) return null;

  const rawResult = row.output?.result;
  const result = isMarketingAgentResult(rawResult) ? rawResult : null;

  return {
    id: row.id,
    clientId: row.clientId,
    workflowId,
    workflowName: getMarketingAgentWorkflow(workflowId).name,
    status: row.status,
    approvalStatus: row.metadata?.approvalStatus ?? null,
    error: row.error,
    result,
    createdAt: row.createdAt.toISOString(),
    updatedAt: row.updatedAt.toISOString(),
    completedAt: row.completedAt?.toISOString() ?? null,
  };
}
