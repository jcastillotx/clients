import { Suspense } from "react";
import { db } from "@/lib/db";
import { projects, projectBudgets, projectMilestones, projectDeliverables } from "@/lib/db/schema/projects";
import { clients } from "@/lib/db/schema/clients";
import { isNull, desc, sql, eq } from "drizzle-orm";
import { ProjectCard } from "@/components/projects/project-card";
import { Button } from "@/components/ui/button";
import { Plus, ClipboardList, ListChecks } from "lucide-react";
import Link from "next/link";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Progress } from "@/components/ui/progress";

async function getProjects() {
  const result = await db
    .select({
      project: projects,
      budgetSummary: sql<number>`COALESCE(SUM(${projectBudgets.allocatedAmount}), 0)`,
      milestonesCount: sql<number>`COUNT(DISTINCT ${projectMilestones.id})`,
      deliverablesCount: sql<number>`COUNT(DISTINCT ${projectDeliverables.id})`,
    })
    .from(projects)
    .leftJoin(projectBudgets, eq(projects.id, projectBudgets.projectId))
    .leftJoin(projectMilestones, eq(projects.id, projectMilestones.projectId))
    .leftJoin(projectDeliverables, eq(projects.id, projectDeliverables.projectId))
    .where(isNull(projects.deletedAt))
    .groupBy(projects.id)
    .orderBy(desc(projects.createdAt));

  return result.map((row) => ({
    ...row.project,
    budgetSummary: Number(row.budgetSummary),
    milestonesCount: Number(row.milestonesCount),
    deliverablesCount: Number(row.deliverablesCount),
  }));
}

async function getBudgetRollup() {
  const rows = await db
    .select({
      projectId: projects.id,
      projectName: projects.name,
      status: projects.status,
      currency: projects.currency,
      budgetAmount: projects.budgetAmount,
      spentAmount: projects.spentAmount,
      clientName: clients.companyName,
      allocatedSum: sql<string>`COALESCE(SUM(${projectBudgets.allocatedAmount}), 0)`,
      categorySpentSum: sql<string>`COALESCE(SUM(${projectBudgets.spentAmount}), 0)`,
    })
    .from(projects)
    .leftJoin(clients, eq(projects.clientId, clients.id))
    .leftJoin(projectBudgets, eq(projects.id, projectBudgets.projectId))
    .where(isNull(projects.deletedAt))
    .groupBy(projects.id, clients.id)
    .orderBy(desc(projects.createdAt));

  return rows.map((r) => {
    const allocated = Number(r.allocatedSum) || Number(r.budgetAmount) || 0;
    const spent = Number(r.categorySpentSum) || Number(r.spentAmount) || 0;
    const remaining = allocated - spent;
    const percent = allocated > 0 ? Math.min(100, Math.round((spent / allocated) * 100)) : 0;
    return {
      projectId: r.projectId,
      projectName: r.projectName,
      clientName: r.clientName ?? "—",
      status: r.status,
      currency: r.currency ?? "USD",
      allocated,
      spent,
      remaining,
      percent,
    };
  });
}

function fmt(amount: number, currency: string) {
  try {
    return new Intl.NumberFormat("en-US", { style: "currency", currency }).format(amount);
  } catch {
    return `${currency} ${amount.toFixed(2)}`;
  }
}

function ProjectsLoading() {
  return (
    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      {[...Array(6)].map((_, i) => (
        <div key={i} className="h-64 rounded-lg border bg-muted/50 animate-pulse" />
      ))}
    </div>
  );
}

async function ProjectsList() {
  const allProjects = await getProjects();

  const projectsByStatus = {
    planning: allProjects.filter((p) => p.status === "planning"),
    active: allProjects.filter((p) => p.status === "active"),
    on_hold: allProjects.filter((p) => p.status === "on_hold"),
    completed: allProjects.filter((p) => p.status === "completed"),
    cancelled: allProjects.filter((p) => p.status === "cancelled"),
  };

  return (
    <Tabs defaultValue="all" className="w-full">
      <TabsList>
        <TabsTrigger value="all">All ({allProjects.length})</TabsTrigger>
        <TabsTrigger value="planning">Planning ({projectsByStatus.planning.length})</TabsTrigger>
        <TabsTrigger value="active">Active ({projectsByStatus.active.length})</TabsTrigger>
        <TabsTrigger value="on_hold">On Hold ({projectsByStatus.on_hold.length})</TabsTrigger>
        <TabsTrigger value="completed">Completed ({projectsByStatus.completed.length})</TabsTrigger>
      </TabsList>

      <TabsContent value="all" className="mt-6">
        {allProjects.length === 0 ? (
          <div className="text-center py-12 border rounded-lg">
            <h3 className="text-lg font-semibold mb-2">No projects yet</h3>
            <p className="text-muted-foreground mb-4">Create your first project to get started</p>
            <Button asChild>
              <Link href="/projects/new">
                <Plus className="h-4 w-4 mr-2" />
                New Project
              </Link>
            </Button>
          </div>
        ) : (
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            {allProjects.map((project) => (
              <ProjectCard key={project.id} project={project} />
            ))}
          </div>
        )}
      </TabsContent>

      <TabsContent value="planning" className="mt-6">
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          {projectsByStatus.planning.map((project) => (
            <ProjectCard key={project.id} project={project} />
          ))}
        </div>
      </TabsContent>

      <TabsContent value="active" className="mt-6">
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          {projectsByStatus.active.map((project) => (
            <ProjectCard key={project.id} project={project} />
          ))}
        </div>
      </TabsContent>

      <TabsContent value="on_hold" className="mt-6">
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          {projectsByStatus.on_hold.map((project) => (
            <ProjectCard key={project.id} project={project} />
          ))}
        </div>
      </TabsContent>

      <TabsContent value="completed" className="mt-6">
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          {projectsByStatus.completed.map((project) => (
            <ProjectCard key={project.id} project={project} />
          ))}
        </div>
      </TabsContent>
    </Tabs>
  );
}

async function BudgetRollup() {
  const rows = await getBudgetRollup();
  const baseCurrency = rows[0]?.currency ?? "USD";
  const totals = rows.reduce(
    (acc, r) => {
      if (r.currency === baseCurrency) {
        acc.allocated += r.allocated;
        acc.spent += r.spent;
      }
      return acc;
    },
    { allocated: 0, spent: 0 },
  );
  const totalRemaining = totals.allocated - totals.spent;
  const totalPercent = totals.allocated > 0
    ? Math.min(100, Math.round((totals.spent / totals.allocated) * 100))
    : 0;

  if (rows.length === 0) {
    return (
      <div className="text-center py-12 border rounded-lg">
        <h3 className="text-lg font-semibold mb-2">No project budgets yet</h3>
        <p className="text-muted-foreground">
          Set a budget on a project to see it rolled up here.
        </p>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
        <Card>
          <CardHeader className="pb-2">
            <CardTitle className="text-sm font-medium text-muted-foreground">
              Total allocated ({baseCurrency})
            </CardTitle>
          </CardHeader>
          <CardContent>
            <p className="text-2xl font-bold">{fmt(totals.allocated, baseCurrency)}</p>
          </CardContent>
        </Card>
        <Card>
          <CardHeader className="pb-2">
            <CardTitle className="text-sm font-medium text-muted-foreground">
              Total spent
            </CardTitle>
          </CardHeader>
          <CardContent>
            <p className="text-2xl font-bold">{fmt(totals.spent, baseCurrency)}</p>
            <Progress value={totalPercent} className="mt-2 h-2" />
            <p className="mt-1 text-xs text-muted-foreground">{totalPercent}% used</p>
          </CardContent>
        </Card>
        <Card>
          <CardHeader className="pb-2">
            <CardTitle className="text-sm font-medium text-muted-foreground">
              Remaining
            </CardTitle>
          </CardHeader>
          <CardContent>
            <p className={`text-2xl font-bold ${totalRemaining < 0 ? "text-destructive" : ""}`}>
              {fmt(totalRemaining, baseCurrency)}
            </p>
          </CardContent>
        </Card>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Per-project budgets</CardTitle>
        </CardHeader>
        <CardContent className="overflow-x-auto p-0">
          <table className="w-full text-sm">
            <thead>
              <tr className="border-b text-left text-xs uppercase text-muted-foreground">
                <th className="px-4 py-3 font-medium">Project</th>
                <th className="px-4 py-3 font-medium">Client</th>
                <th className="px-4 py-3 font-medium">Status</th>
                <th className="px-4 py-3 font-medium text-right">Allocated</th>
                <th className="px-4 py-3 font-medium text-right">Spent</th>
                <th className="px-4 py-3 font-medium text-right">Remaining</th>
                <th className="px-4 py-3 font-medium">Used</th>
              </tr>
            </thead>
            <tbody>
              {rows.map((r) => (
                <tr key={r.projectId} className="border-b last:border-0 hover:bg-muted/40">
                  <td className="px-4 py-3 font-medium">
                    <Link href={`/projects/${r.projectId}/budget`} className="hover:underline">
                      {r.projectName}
                    </Link>
                  </td>
                  <td className="px-4 py-3 text-muted-foreground">{r.clientName}</td>
                  <td className="px-4 py-3">
                    <Badge variant="outline" className="capitalize">
                      {r.status.replace("_", " ")}
                    </Badge>
                  </td>
                  <td className="px-4 py-3 text-right tabular-nums">
                    {fmt(r.allocated, r.currency)}
                  </td>
                  <td className="px-4 py-3 text-right tabular-nums">
                    {fmt(r.spent, r.currency)}
                  </td>
                  <td className={`px-4 py-3 text-right tabular-nums ${r.remaining < 0 ? "text-destructive" : ""}`}>
                    {fmt(r.remaining, r.currency)}
                  </td>
                  <td className="px-4 py-3">
                    <div className="flex items-center gap-2">
                      <Progress value={r.percent} className="h-2 w-24" />
                      <span className="text-xs text-muted-foreground tabular-nums">
                        {r.percent}%
                      </span>
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </CardContent>
      </Card>
    </div>
  );
}

export default async function ProjectsPage({
  searchParams,
}: {
  searchParams: Promise<{ view?: string }>;
}) {
  const { view } = await searchParams;
  const isBudgetsView = view === "budgets";

  return (
    <div className="container mx-auto py-8 px-4">
      <div className="flex justify-between items-center mb-8">
        <div>
          <h1 className="text-3xl font-bold">
            {isBudgetsView ? "Project Budgets" : "Projects"}
          </h1>
          <p className="text-muted-foreground mt-1">
            {isBudgetsView
              ? "Roll-up of allocated vs spent budget across all active projects."
              : "Manage active projects, milestones, deliverables, and incoming client project requests."}
          </p>
        </div>
        <div className="flex gap-2">
          {isBudgetsView ? (
            <Button variant="outline" asChild>
              <Link href="/projects">Back to projects</Link>
            </Button>
          ) : (
            <>
              <Button variant="outline" asChild>
                <Link href="/projects/templates">
                  <ListChecks className="h-4 w-4 mr-2" />
                  Task Templates
                </Link>
              </Button>
              <Button variant="outline" asChild>
                <Link href="/projects/requests">
                  <ClipboardList className="h-4 w-4 mr-2" />
                  Project Requests
                </Link>
              </Button>
              <Button asChild>
                <Link href="/projects/new">
                  <Plus className="h-4 w-4 mr-2" />
                  New Project
                </Link>
              </Button>
            </>
          )}
        </div>
      </div>

      <Suspense fallback={<ProjectsLoading />}>
        {isBudgetsView ? <BudgetRollup /> : <ProjectsList />}
      </Suspense>
    </div>
  );
}
