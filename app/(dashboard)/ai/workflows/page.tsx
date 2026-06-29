import { Metadata } from "next";
import { createClient } from "@/lib/supabase/server";
import { WorkflowBuilder } from "@/components/ai/workflow-builder";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Plus, Zap, Clock, CheckCircle2 } from "lucide-react";

export const metadata: Metadata = {
  title: "AI Workflows",
  description: "Automate tasks with AI-powered workflows",
};

export default async function AiWorkflowsPage() {
  const supabase = await createClient();

  const {
    data: { user },
  } = await supabase.auth.getUser();

  const { data: userData } = user
    ? await supabase.from("users").select("client_id, is_super_admin").eq("id", user.id).single()
    : { data: null };

  const clientId = userData?.client_id || null;
  const isAdmin = Boolean(userData?.is_super_admin);

  let workflowQuery = supabase
    .from("ai_workflows")
    .select("id, name, description, trigger_type, is_active, run_count, metadata, created_at, updated_at")
    .order("updated_at", { ascending: false });

  if (clientId) {
    workflowQuery = workflowQuery.eq("client_id", clientId);
  }

  const { data: workflows } = await workflowQuery;
  const all = workflows || [];

  const totalWorkflows = all.length;
  const activeCount = all.filter((w) => w.is_active).length;
  const pausedCount = totalWorkflows - activeCount;
  const totalRuns = all.reduce((sum, w) => sum + (w.run_count ?? 0), 0);

  // Compute overall success rate from lastRun statuses
  const successCount = all.filter((w) => {
    const meta = w.metadata as { lastRun?: { status?: string } } | null;
    return meta?.lastRun?.status === "success";
  }).length;
  const ratedCount = all.filter((w) => {
    const meta = w.metadata as { lastRun?: { status?: string } } | null;
    return meta?.lastRun?.status != null;
  }).length;
  const successRate = ratedCount > 0 ? ((successCount / ratedCount) * 100).toFixed(1) : null;

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

      <div className="grid gap-6 mb-6 md:grid-cols-3">
        <Card>
          <CardHeader className="flex flex-row items-center justify-between pb-2">
            <CardTitle className="text-sm font-medium">Total Workflows</CardTitle>
            <Zap className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{totalWorkflows}</div>
            <p className="text-xs text-muted-foreground">
              {activeCount} active{pausedCount > 0 ? `, ${pausedCount} paused` : ""}
            </p>
          </CardContent>
        </Card>
        <Card>
          <CardHeader className="flex flex-row items-center justify-between pb-2">
            <CardTitle className="text-sm font-medium">Total Runs</CardTitle>
            <Clock className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{totalRuns.toLocaleString()}</div>
            <p className="text-xs text-muted-foreground">Across all workflows</p>
          </CardContent>
        </Card>
        <Card>
          <CardHeader className="flex flex-row items-center justify-between pb-2">
            <CardTitle className="text-sm font-medium">Success Rate</CardTitle>
            <CheckCircle2 className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{successRate != null ? `${successRate}%` : "—"}</div>
            <p className="text-xs text-muted-foreground">Last run results</p>
          </CardContent>
        </Card>
      </div>

      <div className="grid gap-6 lg:grid-cols-[280px_1fr]">
        <Card>
          <CardHeader>
            <CardTitle className="text-sm">Templates</CardTitle>
          </CardHeader>
          <CardContent className="space-y-2">
            {[
              { name: "Client Onboarding", desc: "Automate welcome emails and setup" },
              { name: "Invoice Follow-up", desc: "Send payment reminders automatically" },
              { name: "Weekly Report", desc: "Generate and email weekly summaries" },
              { name: "Task Assignment", desc: "Auto-assign tasks based on criteria" },
              { name: "Content Analysis", desc: "Analyze and categorize documents" },
            ].map((t) => (
              <button
                key={t.name}
                className="w-full text-left p-3 rounded-lg border hover:bg-accent transition-colors"
              >
                <div className="font-medium text-sm">{t.name}</div>
                <div className="text-xs text-muted-foreground">{t.desc}</div>
              </button>
            ))}
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Active Workflows</CardTitle>
          </CardHeader>
          <CardContent>
            <WorkflowBuilder initialWorkflows={all} />
          </CardContent>
        </Card>
      </div>
    </div>
  );
}
