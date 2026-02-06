import { Suspense } from "react";
import { Metadata } from "next";
import { WorkflowBuilder } from "@/components/ai/workflow-builder";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Skeleton } from "@/components/ui/skeleton";
import { Plus, Zap, Clock, CheckCircle2 } from "lucide-react";

export const metadata: Metadata = {
  title: "AI Workflows",
  description: "Automate tasks with AI-powered workflows",
};

function WorkflowsLoading() {
  return (
    <div className="space-y-4">
      {[1, 2, 3].map((i) => (
        <Skeleton key={i} className="h-24 w-full" />
      ))}
    </div>
  );
}

export default function AiWorkflowsPage() {
  return (
    <div className="container mx-auto p-6">
      <div className="mb-6 flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold tracking-tight">AI Workflows</h1>
          <p className="text-muted-foreground mt-2">Automate repetitive tasks with intelligent workflows</p>
        </div>
        <Button>
          <Plus className="mr-2 h-4 w-4" />
          Create Workflow
        </Button>
      </div>

      <div className="grid gap-6 mb-6 md:grid-cols-4">
        <Card>
          <CardHeader className="flex flex-row items-center justify-between pb-2">
            <CardTitle className="text-sm font-medium">Total Workflows</CardTitle>
            <Zap className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">12</div>
            <p className="text-xs text-muted-foreground">8 active, 4 paused</p>
          </CardContent>
        </Card>
        <Card>
          <CardHeader className="flex flex-row items-center justify-between pb-2">
            <CardTitle className="text-sm font-medium">Runs Today</CardTitle>
            <Clock className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">347</div>
            <p className="text-xs text-muted-foreground">+23% from yesterday</p>
          </CardContent>
        </Card>
        <Card>
          <CardHeader className="flex flex-row items-center justify-between pb-2">
            <CardTitle className="text-sm font-medium">Success Rate</CardTitle>
            <CheckCircle2 className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">98.5%</div>
            <p className="text-xs text-muted-foreground">Last 7 days</p>
          </CardContent>
        </Card>
        <Card>
          <CardHeader className="flex flex-row items-center justify-between pb-2">
            <CardTitle className="text-sm font-medium">Time Saved</CardTitle>
            <Clock className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">42.3h</div>
            <p className="text-xs text-muted-foreground">This month</p>
          </CardContent>
        </Card>
      </div>

      <div className="grid gap-6 lg:grid-cols-[300px_1fr]">
        <Card>
          <CardHeader>
            <CardTitle>Workflow Templates</CardTitle>
            <CardDescription>Start with a pre-built template</CardDescription>
          </CardHeader>
          <CardContent className="space-y-2">
            <button className="w-full text-left p-3 rounded-lg border hover:bg-accent transition-colors">
              <div className="font-medium text-sm">Client Onboarding</div>
              <div className="text-xs text-muted-foreground">Automate welcome emails and setup</div>
            </button>
            <button className="w-full text-left p-3 rounded-lg border hover:bg-accent transition-colors">
              <div className="font-medium text-sm">Invoice Follow-up</div>
              <div className="text-xs text-muted-foreground">Send payment reminders automatically</div>
            </button>
            <button className="w-full text-left p-3 rounded-lg border hover:bg-accent transition-colors">
              <div className="font-medium text-sm">Weekly Report</div>
              <div className="text-xs text-muted-foreground">Generate and email weekly summaries</div>
            </button>
            <button className="w-full text-left p-3 rounded-lg border hover:bg-accent transition-colors">
              <div className="font-medium text-sm">Task Assignment</div>
              <div className="text-xs text-muted-foreground">Auto-assign tasks based on criteria</div>
            </button>
            <button className="w-full text-left p-3 rounded-lg border hover:bg-accent transition-colors">
              <div className="font-medium text-sm">Content Analysis</div>
              <div className="text-xs text-muted-foreground">Analyze and categorize documents</div>
            </button>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Active Workflows</CardTitle>
            <CardDescription>Manage and monitor your workflows</CardDescription>
          </CardHeader>
          <CardContent>
            <Suspense fallback={<WorkflowsLoading />}>
              <WorkflowBuilder />
            </Suspense>
          </CardContent>
        </Card>
      </div>
    </div>
  );
}
