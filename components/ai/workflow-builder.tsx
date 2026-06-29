"use client";

import { useState } from "react";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Switch } from "@/components/ui/switch";
import { Play, Pause, Settings, Trash2, Calendar, Zap, CheckCircle2, XCircle, Clock } from "lucide-react";

interface WorkflowRow {
  id: string;
  name: string;
  description: string | null;
  trigger_type: string;
  is_active: boolean;
  run_count: number | null;
  metadata: {
    lastRun?: { timestamp: string; status: string; duration: number };
    schedule?: { cron?: string };
  } | null;
}

interface WorkflowBuilderProps {
  initialWorkflows: WorkflowRow[];
}

export function WorkflowBuilder({ initialWorkflows }: WorkflowBuilderProps) {
  const [workflows, setWorkflows] = useState(initialWorkflows);

  const toggleWorkflow = (id: string) => {
    setWorkflows((prev) => prev.map((w) => (w.id === id ? { ...w, is_active: !w.is_active } : w)));
    // TODO: PATCH /api/ai/workflows/:id to persist is_active change
  };

  const getTriggerIcon = (type: string) => {
    switch (type) {
      case "scheduled": return <Calendar className="h-4 w-4" />;
      case "event": return <Zap className="h-4 w-4" />;
      default: return <Play className="h-4 w-4" />;
    }
  };

  const formatDuration = (ms: number) => `${(ms / 1000).toFixed(1)}s`;

  const formatDate = (dateString: string) => {
    const diffMs = Date.now() - new Date(dateString).getTime();
    const diffMins = Math.floor(diffMs / 60000);
    const diffHours = Math.floor(diffMins / 60);
    const diffDays = Math.floor(diffHours / 24);
    if (diffMins < 60) return `${diffMins}m ago`;
    if (diffHours < 24) return `${diffHours}h ago`;
    return `${diffDays}d ago`;
  };

  if (workflows.length === 0) {
    return (
      <div className="flex flex-col items-center justify-center py-12 text-center">
        <Zap className="mb-3 h-10 w-10 text-muted-foreground" />
        <p className="text-sm font-medium">No workflows yet</p>
        <p className="text-xs text-muted-foreground mt-1">
          Create a workflow or start from a template on the left.
        </p>
      </div>
    );
  }

  return (
    <div className="space-y-4">
      {workflows.map((workflow) => {
        const lastRun = workflow.metadata?.lastRun;
        return (
          <div key={workflow.id} className="border rounded-lg p-4 hover:bg-accent/50 transition-colors">
            <div className="flex items-start justify-between mb-3">
              <div className="flex-1">
                <div className="flex items-center gap-2 mb-1">
                  <h3 className="font-semibold">{workflow.name}</h3>
                  <Badge variant="outline" className="text-xs">
                    {getTriggerIcon(workflow.trigger_type)}
                    <span className="ml-1 capitalize">{workflow.trigger_type}</span>
                  </Badge>
                  {workflow.is_active && (
                    <Badge variant="default" className="text-xs">Active</Badge>
                  )}
                </div>
                <p className="text-sm text-muted-foreground">{workflow.description}</p>
              </div>
              <Switch checked={workflow.is_active} onCheckedChange={() => toggleWorkflow(workflow.id)} />
            </div>

            <div className="flex items-center justify-between text-sm">
              <div className="flex items-center gap-4 text-muted-foreground">
                <div className="flex items-center gap-1">
                  <Play className="h-3 w-3" />
                  <span>{(workflow.run_count ?? 0).toLocaleString()} runs</span>
                </div>
                {lastRun && (
                  <>
                    <div className="flex items-center gap-1">
                      {lastRun.status === "success" ? (
                        <CheckCircle2 className="h-3 w-3 text-green-500" />
                      ) : (
                        <XCircle className="h-3 w-3 text-red-500" />
                      )}
                      <span className="capitalize">{lastRun.status}</span>
                    </div>
                    <div className="flex items-center gap-1">
                      <Clock className="h-3 w-3" />
                      <span>{formatDuration(lastRun.duration)}</span>
                    </div>
                    <span>{formatDate(lastRun.timestamp)}</span>
                  </>
                )}
              </div>

              <div className="flex items-center gap-2">
                <Button variant="ghost" size="sm">
                  <Settings className="h-4 w-4" />
                </Button>
                <Button variant="ghost" size="sm">
                  {workflow.is_active ? <Pause className="h-4 w-4" /> : <Play className="h-4 w-4" />}
                </Button>
                <Button variant="ghost" size="sm" className="text-destructive">
                  <Trash2 className="h-4 w-4" />
                </Button>
              </div>
            </div>
          </div>
        );
      })}
    </div>
  );
}
