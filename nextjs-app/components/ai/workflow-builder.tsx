"use client";

import { useState } from "react";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Switch } from "@/components/ui/switch";
import { Play, Pause, Settings, Trash2, Calendar, Zap, CheckCircle2, XCircle, Clock } from "lucide-react";

interface Workflow {
  id: string;
  name: string;
  description: string;
  triggerType: "manual" | "scheduled" | "event" | "webhook";
  isActive: boolean;
  runCount: number;
  lastRun?: {
    timestamp: string;
    status: "success" | "failed";
    duration: number;
  };
}

const mockWorkflows: Workflow[] = [
  {
    id: "1",
    name: "Client Onboarding Automation",
    description: "Automatically send welcome emails and setup tasks when a new client is added",
    triggerType: "event",
    isActive: true,
    runCount: 42,
    lastRun: {
      timestamp: "2024-02-04T10:30:00Z",
      status: "success",
      duration: 2300,
    },
  },
  {
    id: "2",
    name: "Weekly Report Generation",
    description: "Generate and email weekly performance reports every Monday at 9 AM",
    triggerType: "scheduled",
    isActive: true,
    runCount: 156,
    lastRun: {
      timestamp: "2024-02-05T09:00:00Z",
      status: "success",
      duration: 4500,
    },
  },
  {
    id: "3",
    name: "Invoice Payment Reminder",
    description: "Send automated reminders for overdue invoices",
    triggerType: "scheduled",
    isActive: true,
    runCount: 89,
    lastRun: {
      timestamp: "2024-02-04T14:00:00Z",
      status: "success",
      duration: 1200,
    },
  },
  {
    id: "4",
    name: "Document Analysis",
    description: "Analyze and categorize uploaded documents",
    triggerType: "event",
    isActive: false,
    runCount: 23,
  },
];

export function WorkflowBuilder() {
  const [workflows, setWorkflows] = useState<Workflow[]>(mockWorkflows);

  const toggleWorkflow = (id: string) => {
    setWorkflows((prev) => prev.map((w) => (w.id === id ? { ...w, isActive: !w.isActive } : w)));
  };

  const getTriggerIcon = (type: string) => {
    switch (type) {
      case "scheduled":
        return <Calendar className="h-4 w-4" />;
      case "event":
        return <Zap className="h-4 w-4" />;
      case "webhook":
        return <Play className="h-4 w-4" />;
      default:
        return <Play className="h-4 w-4" />;
    }
  };

  const formatDuration = (ms: number) => {
    return `${(ms / 1000).toFixed(1)}s`;
  };

  const formatDate = (dateString: string) => {
    const date = new Date(dateString);
    const now = new Date();
    const diffMs = now.getTime() - date.getTime();
    const diffMins = Math.floor(diffMs / 60000);
    const diffHours = Math.floor(diffMins / 60);
    const diffDays = Math.floor(diffHours / 24);

    if (diffMins < 60) return `${diffMins}m ago`;
    if (diffHours < 24) return `${diffHours}h ago`;
    return `${diffDays}d ago`;
  };

  return (
    <div className="space-y-4">
      {workflows.map((workflow) => (
        <div key={workflow.id} className="border rounded-lg p-4 hover:bg-accent/50 transition-colors">
          <div className="flex items-start justify-between mb-3">
            <div className="flex-1">
              <div className="flex items-center gap-2 mb-1">
                <h3 className="font-semibold">{workflow.name}</h3>
                <Badge variant="outline" className="text-xs">
                  {getTriggerIcon(workflow.triggerType)}
                  <span className="ml-1 capitalize">{workflow.triggerType}</span>
                </Badge>
                {workflow.isActive && (
                  <Badge variant="default" className="text-xs">
                    Active
                  </Badge>
                )}
              </div>
              <p className="text-sm text-muted-foreground">{workflow.description}</p>
            </div>
            <Switch checked={workflow.isActive} onCheckedChange={() => toggleWorkflow(workflow.id)} />
          </div>

          <div className="flex items-center justify-between text-sm">
            <div className="flex items-center gap-4 text-muted-foreground">
              <div className="flex items-center gap-1">
                <Play className="h-3 w-3" />
                <span>{workflow.runCount} runs</span>
              </div>
              {workflow.lastRun && (
                <>
                  <div className="flex items-center gap-1">
                    {workflow.lastRun.status === "success" ? (
                      <CheckCircle2 className="h-3 w-3 text-green-500" />
                    ) : (
                      <XCircle className="h-3 w-3 text-red-500" />
                    )}
                    <span className="capitalize">{workflow.lastRun.status}</span>
                  </div>
                  <div className="flex items-center gap-1">
                    <Clock className="h-3 w-3" />
                    <span>{formatDuration(workflow.lastRun.duration)}</span>
                  </div>
                  <span>{formatDate(workflow.lastRun.timestamp)}</span>
                </>
              )}
            </div>

            <div className="flex items-center gap-2">
              <Button variant="ghost" size="sm">
                <Settings className="h-4 w-4" />
              </Button>
              <Button variant="ghost" size="sm">
                {workflow.isActive ? <Pause className="h-4 w-4" /> : <Play className="h-4 w-4" />}
              </Button>
              <Button variant="ghost" size="sm" className="text-destructive">
                <Trash2 className="h-4 w-4" />
              </Button>
            </div>
          </div>
        </div>
      ))}
    </div>
  );
}
