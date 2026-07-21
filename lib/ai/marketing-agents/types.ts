export const marketingAgentWorkflowIds = [
  "campaign_plan",
  "content_calendar",
  "quality_check",
] as const;

export type MarketingAgentWorkflowId =
  (typeof marketingAgentWorkflowIds)[number];

export const marketingAgentRunStatuses = [
  "pending",
  "processing",
  "completed",
  "failed",
  "cancelled",
] as const;

export type MarketingAgentRunStatus =
  (typeof marketingAgentRunStatuses)[number];

export const qualityDecisions = ["PASS", "WARN", "BLOCKED"] as const;
export type QualityDecision = (typeof qualityDecisions)[number];

export interface AgentTraceEntry {
  agentId: string;
  agentName: string;
  status: "completed" | "failed";
  summary: string;
  startedAt: string;
  completedAt: string;
}

export interface QualityFinding {
  severity: "info" | "warning" | "critical";
  category: "brand_voice" | "claims" | "readability" | "formatting";
  message: string;
  suggestion: string;
}

export interface QualityReport {
  decision: QualityDecision;
  score: number;
  findings: QualityFinding[];
  checks: {
    bannedLanguage: boolean;
    unsupportedClaims: boolean;
    readability: boolean;
    formatting: boolean;
  };
}

export interface CampaignPlanArtifact {
  title: string;
  executiveSummary: string;
  objective: string;
  targetAudience: string;
  keyMessages: string[];
  channels: Array<{
    name: string;
    role: string;
    budgetPercentage: number;
    tactics: string[];
  }>;
  timeline: Array<{
    phase: string;
    startWeek: number;
    endWeek: number;
    deliverables: string[];
  }>;
  kpis: Array<{
    name: string;
    target: string;
    measurement: string;
  }>;
  risks: string[];
}

export interface ContentCalendarArtifact {
  title: string;
  strategySummary: string;
  items: Array<{
    title: string;
    platform:
      | "facebook"
      | "instagram"
      | "linkedin"
      | "twitter"
      | "x"
      | "tiktok"
      | "pinterest"
      | "youtube"
      | "other";
    contentType:
      | "post"
      | "story"
      | "reel"
      | "video"
      | "article"
      | "blog"
      | "tweet"
      | "other";
    scheduledDate: string;
    copy: string;
    cta: string;
    hashtags: string[];
  }>;
}

export type MarketingAgentArtifact =
  | CampaignPlanArtifact
  | ContentCalendarArtifact
  | QualityReport;

export interface MarketingAgentRunResult {
  workflowId: MarketingAgentWorkflowId;
  approvalStatus: "pending_approval";
  artifact: MarketingAgentArtifact;
  qualityReport: QualityReport;
  agentTrace: AgentTraceEntry[];
  createdRecords: Array<{
    type: "campaign" | "content_calendar_item";
    id: string;
  }>;
}

export interface MarketingAgentRunSummary {
  id: string;
  clientId: string | null;
  workflowId: MarketingAgentWorkflowId;
  workflowName: string;
  status: MarketingAgentRunStatus;
  approvalStatus: "pending_approval" | null;
  error: string | null;
  result: MarketingAgentRunResult | null;
  createdAt: string;
  updatedAt: string;
  completedAt: string | null;
}
