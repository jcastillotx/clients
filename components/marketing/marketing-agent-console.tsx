"use client";

import { useCallback, useEffect, useState } from "react";
import {
  AlertTriangle,
  Bot,
  CalendarDays,
  CheckCircle2,
  Clock3,
  Loader2,
  Megaphone,
  Play,
  ShieldCheck,
  XCircle,
} from "lucide-react";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Checkbox } from "@/components/ui/checkbox";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Textarea } from "@/components/ui/textarea";
import { useToast } from "@/hooks/use-toast";
import { fetchApi } from "@/lib/api/client";
import { marketingAgentWorkflows } from "@/lib/ai/marketing-agents/catalog";
import type {
  CampaignPlanArtifact,
  ContentCalendarArtifact,
  MarketingAgentRunSummary,
  MarketingAgentWorkflowId,
  QualityReport,
} from "@/lib/ai/marketing-agents/types";

interface ClientOption {
  id: string;
  companyName: string;
}

interface MarketingAgentConsoleProps {
  clients: ClientOption[];
}

const platformOptions = [
  "facebook",
  "instagram",
  "linkedin",
  "twitter",
  "tiktok",
  "youtube",
] as const;

const campaignChannelOptions = [
  "email",
  "social media",
  "content",
  "seo",
  "google ads",
  "meta ads",
] as const;

const terminalStatuses = new Set(["completed", "failed", "cancelled"]);

function statusBadge(run: MarketingAgentRunSummary) {
  if (run.status === "completed") {
    return <Badge className="bg-emerald-600">Completed</Badge>;
  }
  if (run.status === "failed") {
    return <Badge variant="destructive">Failed</Badge>;
  }
  if (run.status === "processing") {
    return <Badge variant="secondary">Processing</Badge>;
  }
  return <Badge variant="outline">Pending</Badge>;
}

function QualityReportView({ report }: { report: QualityReport }) {
  const DecisionIcon =
    report.decision === "PASS"
      ? CheckCircle2
      : report.decision === "BLOCKED"
        ? XCircle
        : AlertTriangle;
  const decisionClass =
    report.decision === "PASS"
      ? "text-emerald-600"
      : report.decision === "BLOCKED"
        ? "text-destructive"
        : "text-amber-600";

  return (
    <div className="space-y-3 rounded-lg border p-4">
      <div className="flex items-center justify-between">
        <div className={`flex items-center gap-2 font-semibold ${decisionClass}`}>
          <DecisionIcon className="h-5 w-5" />
          {report.decision}
        </div>
        <span className="text-sm font-medium">Quality score: {report.score}/100</span>
      </div>
      {report.findings.length === 0 ? (
        <p className="text-sm text-muted-foreground">No blocking or warning-level findings.</p>
      ) : (
        <div className="space-y-2">
          {report.findings.map((finding, index) => (
            <div key={`${finding.category}-${index}`} className="rounded-md bg-muted p-3 text-sm">
              <div className="font-medium capitalize">{finding.category.replace("_", " ")}</div>
              <p>{finding.message}</p>
              <p className="mt-1 text-muted-foreground">{finding.suggestion}</p>
            </div>
          ))}
        </div>
      )}
    </div>
  );
}

function CampaignPlanView({ artifact }: { artifact: CampaignPlanArtifact }) {
  return (
    <div className="space-y-4">
      <div>
        <h3 className="text-xl font-semibold">{artifact.title}</h3>
        <p className="mt-1 text-sm text-muted-foreground">{artifact.executiveSummary}</p>
      </div>
      <div className="grid gap-3 md:grid-cols-2">
        {artifact.channels.map((channel) => (
          <div key={channel.name} className="rounded-lg border p-3">
            <div className="flex items-center justify-between gap-3">
              <span className="font-medium">{channel.name}</span>
              <Badge variant="outline">{channel.budgetPercentage}%</Badge>
            </div>
            <p className="mt-1 text-sm text-muted-foreground">{channel.role}</p>
          </div>
        ))}
      </div>
      <div>
        <h4 className="mb-2 font-medium">KPIs</h4>
        <div className="space-y-2">
          {artifact.kpis.map((kpi) => (
            <div key={kpi.name} className="flex justify-between gap-4 rounded-md bg-muted p-3 text-sm">
              <span>{kpi.name}</span>
              <span className="font-medium">{kpi.target}</span>
            </div>
          ))}
        </div>
      </div>
    </div>
  );
}

function ContentCalendarView({ artifact }: { artifact: ContentCalendarArtifact }) {
  return (
    <div className="space-y-4">
      <div>
        <h3 className="text-xl font-semibold">{artifact.title}</h3>
        <p className="mt-1 text-sm text-muted-foreground">{artifact.strategySummary}</p>
      </div>
      <div className="space-y-3">
        {artifact.items.map((item, index) => (
          <div key={`${item.scheduledDate}-${item.platform}-${index}`} className="rounded-lg border p-4">
            <div className="flex flex-wrap items-center justify-between gap-2">
              <span className="font-medium">{item.title}</span>
              <div className="flex gap-2">
                <Badge variant="outline" className="capitalize">{item.platform}</Badge>
                <Badge variant="secondary">{item.scheduledDate}</Badge>
              </div>
            </div>
            <p className="mt-3 whitespace-pre-wrap text-sm">{item.copy}</p>
            {item.cta && <p className="mt-2 text-sm font-medium">CTA: {item.cta}</p>}
          </div>
        ))}
      </div>
    </div>
  );
}

function RunResult({ run }: { run: MarketingAgentRunSummary }) {
  const result = run.result;
  if (!result) return null;

  return (
    <div className="space-y-5">
      <div className="flex flex-wrap gap-2">
        {result.agentTrace.map((entry) => (
          <Badge key={`${entry.agentId}-${entry.startedAt}`} variant="secondary">
            <Bot className="mr-1 h-3 w-3" />
            {entry.agentName}
          </Badge>
        ))}
        <Badge variant="outline">
          <ShieldCheck className="mr-1 h-3 w-3" />
          Human approval required
        </Badge>
      </div>

      {run.workflowId === "campaign_plan" && (
        <CampaignPlanView artifact={result.artifact as CampaignPlanArtifact} />
      )}
      {run.workflowId === "content_calendar" && (
        <ContentCalendarView artifact={result.artifact as ContentCalendarArtifact} />
      )}
      <QualityReportView report={result.qualityReport} />

      {result.createdRecords.length > 0 && (
        <p className="text-sm text-muted-foreground">
          Created {result.createdRecords.length} approval-safe draft record{result.createdRecords.length === 1 ? "" : "s"}.
        </p>
      )}
    </div>
  );
}

export function MarketingAgentConsole({ clients }: MarketingAgentConsoleProps) {
  const { toast } = useToast();
  const [clientId, setClientId] = useState(clients[0]?.id ?? "");
  const [workflowId, setWorkflowId] = useState<MarketingAgentWorkflowId>("campaign_plan");
  const [campaignName, setCampaignName] = useState("");
  const [objective, setObjective] = useState("");
  const [targetAudience, setTargetAudience] = useState("");
  const [budget, setBudget] = useState("");
  const [startDate, setStartDate] = useState(new Date().toISOString().slice(0, 10));
  const [endDate, setEndDate] = useState("");
  const [numberOfItems, setNumberOfItems] = useState("12");
  const [campaignChannels, setCampaignChannels] = useState<string[]>(["email", "social media"]);
  const [platforms, setPlatforms] = useState<string[]>(["linkedin", "instagram"]);
  const [content, setContent] = useState("");
  const [evidence, setEvidence] = useState("");
  const [instructions, setInstructions] = useState("");
  const [createDrafts, setCreateDrafts] = useState(true);
  const [runs, setRuns] = useState<MarketingAgentRunSummary[]>([]);
  const [selectedRun, setSelectedRun] = useState<MarketingAgentRunSummary | null>(null);
  const [isLoadingRuns, setIsLoadingRuns] = useState(false);
  const [isStarting, setIsStarting] = useState(false);

  const loadRuns = useCallback(async () => {
    if (!clientId) return;
    setIsLoadingRuns(true);
    try {
      const data = await fetchApi<MarketingAgentRunSummary[]>(
        `/api/marketing/agents?clientId=${encodeURIComponent(clientId)}`,
        {},
        { fallbackMessage: "Failed to load marketing agent runs" },
      );
      setRuns(data);
      setSelectedRun((current) =>
        current?.clientId === clientId
          ? data.find((run) => run.id === current.id) ?? data[0] ?? null
          : data[0] ?? null,
      );
    } catch (error) {
      toast({
        title: "Could not load runs",
        description: error instanceof Error ? error.message : "Please try again.",
        variant: "destructive",
      });
    } finally {
      setIsLoadingRuns(false);
    }
  }, [clientId, toast]);

  useEffect(() => {
    void loadRuns();
  }, [loadRuns]);

  const togglePlatform = (platform: string, checked: boolean) => {
    setPlatforms((current) =>
      checked
        ? Array.from(new Set([...current, platform]))
        : current.filter((candidate) => candidate !== platform),
    );
  };

  const toggleCampaignChannel = (channel: string, checked: boolean) => {
    setCampaignChannels((current) =>
      checked
        ? Array.from(new Set([...current, channel]))
        : current.filter((candidate) => candidate !== channel),
    );
  };

  const pollRun = async (runId: string) => {
    for (let attempt = 0; attempt < 90; attempt += 1) {
      await new Promise((resolve) => window.setTimeout(resolve, 2000));
      const run = await fetchApi<MarketingAgentRunSummary>(
        `/api/marketing/agents/${runId}`,
        {},
        { fallbackMessage: "Failed to refresh marketing agent run" },
      );
      setSelectedRun(run);
      setRuns((current) => [run, ...current.filter((item) => item.id !== run.id)]);
      if (terminalStatuses.has(run.status)) return run;
    }
    throw new Error("The agent is still running. It will remain available in recent runs.");
  };

  const buildPayload = () => {
    const common = { clientId, workflowId, instructions, createDrafts };
    if (workflowId === "campaign_plan") {
      return {
        ...common,
        campaignName,
        objective,
        targetAudience,
        budget: budget ? Number(budget) : undefined,
        startDate: startDate || undefined,
        endDate: endDate || undefined,
        channels: campaignChannels,
      };
    }
    if (workflowId === "content_calendar") {
      return {
        ...common,
        objective,
        targetAudience,
        startDate,
        numberOfItems: Number(numberOfItems),
        platforms,
      };
    }
    return { ...common, content, evidence };
  };

  const startRun = async () => {
    setIsStarting(true);
    try {
      const run = await fetchApi<MarketingAgentRunSummary>(
        "/api/marketing/agents",
        {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify(buildPayload()),
        },
        { fallbackMessage: "Failed to start marketing agent run" },
      );
      setSelectedRun(run);
      setRuns((current) => [run, ...current.filter((item) => item.id !== run.id)]);
      toast({ title: "Marketing agents started", description: "The run is processing in the background." });
      const completedRun = await pollRun(run.id);
      toast({
        title: completedRun.status === "completed" ? "Agent run complete" : "Agent run failed",
        description: completedRun.error || "The result is ready for review.",
        variant: completedRun.status === "failed" ? "destructive" : "default",
      });
    } catch (error) {
      toast({
        title: "Agent run unavailable",
        description: error instanceof Error ? error.message : "Please try again.",
        variant: "destructive",
      });
    } finally {
      setIsStarting(false);
    }
  };

  const workflowIcon = (id: MarketingAgentWorkflowId) => {
    if (id === "campaign_plan") return Megaphone;
    if (id === "content_calendar") return CalendarDays;
    return ShieldCheck;
  };

  return (
    <div className="grid gap-6 xl:grid-cols-[minmax(0,1fr)_360px]">
      <div className="space-y-6">
        <div className="grid gap-3 md:grid-cols-3">
          {marketingAgentWorkflows.map((workflow) => {
            const Icon = workflowIcon(workflow.id);
            const selected = workflow.id === workflowId;
            return (
              <button
                key={workflow.id}
                type="button"
                onClick={() => setWorkflowId(workflow.id)}
                className={`rounded-xl border p-4 text-left transition-colors ${selected ? "border-primary bg-primary/5" : "hover:bg-muted/60"}`}
              >
                <Icon className="mb-3 h-5 w-5" />
                <div className="font-semibold">{workflow.name}</div>
                <p className="mt-1 text-sm text-muted-foreground">{workflow.description}</p>
              </button>
            );
          })}
        </div>

        <Card>
          <CardHeader><CardTitle>Configure agent run</CardTitle></CardHeader>
          <CardContent className="space-y-5">
            <div className="space-y-2">
              <Label>Client</Label>
              <Select value={clientId} onValueChange={setClientId}>
                <SelectTrigger><SelectValue placeholder="Select a client" /></SelectTrigger>
                <SelectContent>
                  {clients.map((client) => (
                    <SelectItem key={client.id} value={client.id}>{client.companyName}</SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>

            {workflowId === "campaign_plan" && (
              <div className="space-y-2">
                <Label htmlFor="campaign-name">Campaign name</Label>
                <Input id="campaign-name" value={campaignName} onChange={(event) => setCampaignName(event.target.value)} placeholder="Fall client acquisition campaign" />
              </div>
            )}

            {workflowId !== "quality_check" && (
              <>
                <div className="space-y-2">
                  <Label htmlFor="objective">Objective</Label>
                  <Textarea id="objective" value={objective} onChange={(event) => setObjective(event.target.value)} placeholder="Describe the business result this work should support." />
                </div>
                <div className="space-y-2">
                  <Label htmlFor="audience">Target audience</Label>
                  <Textarea id="audience" value={targetAudience} onChange={(event) => setTargetAudience(event.target.value)} placeholder="Describe the primary audience and buying context." />
                </div>
              </>
            )}

            {workflowId === "campaign_plan" && (
              <div className="space-y-2">
                <Label htmlFor="budget">Budget</Label>
                <Input id="budget" type="number" min="0" value={budget} onChange={(event) => setBudget(event.target.value)} placeholder="5000" />
              </div>
            )}

            {workflowId !== "quality_check" && (
              <>
                <div className="space-y-2">
                  <Label htmlFor="start-date">Start date</Label>
                  <Input id="start-date" type="date" value={startDate} onChange={(event) => setStartDate(event.target.value)} />
                </div>
              </>
            )}

            {workflowId === "campaign_plan" && (
              <>
                <div className="space-y-2">
                  <Label htmlFor="end-date">End date</Label>
                  <Input id="end-date" type="date" min={startDate} value={endDate} onChange={(event) => setEndDate(event.target.value)} />
                </div>
                <div className="space-y-3">
                  <Label>Channels</Label>
                  <div className="grid gap-3 sm:grid-cols-2 md:grid-cols-3">
                    {campaignChannelOptions.map((channel) => (
                      <label key={channel} className="flex items-center gap-2 text-sm capitalize">
                        <Checkbox checked={campaignChannels.includes(channel)} onCheckedChange={(checked) => toggleCampaignChannel(channel, checked === true)} />
                        {channel}
                      </label>
                    ))}
                  </div>
                </div>
              </>
            )}

            {workflowId === "content_calendar" && (
              <>
                <div className="space-y-3">
                  <Label>Platforms</Label>
                  <div className="grid gap-3 sm:grid-cols-2 md:grid-cols-3">
                    {platformOptions.map((platform) => (
                      <label key={platform} className="flex items-center gap-2 text-sm capitalize">
                        <Checkbox checked={platforms.includes(platform)} onCheckedChange={(checked) => togglePlatform(platform, checked === true)} />
                        {platform}
                      </label>
                    ))}
                  </div>
                </div>
                <div className="space-y-2">
                  <Label htmlFor="item-count">Number of items</Label>
                  <Input id="item-count" type="number" min="1" max="45" value={numberOfItems} onChange={(event) => setNumberOfItems(event.target.value)} />
                </div>
              </>
            )}

            {workflowId === "quality_check" && (
              <>
                <div className="space-y-2">
                  <Label htmlFor="quality-content">Content to review</Label>
                  <Textarea id="quality-content" className="min-h-48" value={content} onChange={(event) => setContent(event.target.value)} placeholder="Paste the marketing content to review." />
                </div>
                <div className="space-y-2">
                  <Label htmlFor="evidence">Supporting evidence</Label>
                  <Textarea id="evidence" value={evidence} onChange={(event) => setEvidence(event.target.value)} placeholder="Optional sources or evidence supporting numeric claims." />
                </div>
              </>
            )}

            <div className="space-y-2">
              <Label htmlFor="instructions">Additional instructions</Label>
              <Textarea id="instructions" value={instructions} onChange={(event) => setInstructions(event.target.value)} placeholder="Optional campaign constraints, offers, or required messages." />
            </div>

            {workflowId !== "quality_check" && (
              <label className="flex items-center gap-2 text-sm">
                <Checkbox checked={createDrafts} onCheckedChange={(checked) => setCreateDrafts(checked === true)} />
                Save approval-safe drafts to the client workspace
              </label>
            )}

            <Button onClick={() => void startRun()} disabled={isStarting || !clientId}>
              {isStarting ? <Loader2 className="mr-2 h-4 w-4 animate-spin" /> : <Play className="mr-2 h-4 w-4" />}
              Run agents
            </Button>
          </CardContent>
        </Card>

        {selectedRun && (
          <Card>
            <CardHeader>
              <div className="flex items-center justify-between gap-3">
                <CardTitle>{selectedRun.workflowName}</CardTitle>
                {statusBadge(selectedRun)}
              </div>
            </CardHeader>
            <CardContent>
              {selectedRun.error && <p className="text-sm text-destructive">{selectedRun.error}</p>}
              {selectedRun.status === "processing" && (
                <div className="flex items-center gap-2 text-sm text-muted-foreground"><Loader2 className="h-4 w-4 animate-spin" /> Agents are working…</div>
              )}
              <RunResult run={selectedRun} />
            </CardContent>
          </Card>
        )}
      </div>

      <Card className="h-fit">
        <CardHeader>
          <div className="flex items-center justify-between">
            <CardTitle className="text-base">Recent runs</CardTitle>
            {isLoadingRuns && <Loader2 className="h-4 w-4 animate-spin" />}
          </div>
        </CardHeader>
        <CardContent className="space-y-2">
          {runs.length === 0 && <p className="text-sm text-muted-foreground">No agent runs for this client yet.</p>}
          {runs.map((run) => (
            <button key={run.id} type="button" onClick={() => setSelectedRun(run)} className="w-full rounded-lg border p-3 text-left hover:bg-muted/60">
              <div className="flex items-center justify-between gap-2">
                <span className="text-sm font-medium">{run.workflowName}</span>
                {statusBadge(run)}
              </div>
              <div className="mt-2 flex items-center gap-1 text-xs text-muted-foreground">
                <Clock3 className="h-3 w-3" />
                {new Date(run.createdAt).toLocaleString()}
              </div>
            </button>
          ))}
        </CardContent>
      </Card>
    </div>
  );
}
