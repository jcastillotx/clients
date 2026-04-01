import { Suspense } from "react";
import { db } from "@/lib/db";
import { projects, projectBudgets, projectMilestones, projectDeliverables } from "@/lib/db/schema/projects";
import { isNull, desc, sql, eq } from "drizzle-orm";
import { ProjectCard } from "@/components/projects/project-card";
import { Button } from "@/components/ui/button";
import { Plus, ClipboardList, ListChecks } from "lucide-react";
import Link from "next/link";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";

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

export default function ProjectsPage() {
  return (
    <div className="container mx-auto py-8 px-4">
      <div className="flex justify-between items-center mb-8">
        <div>
          <h1 className="text-3xl font-bold">Projects</h1>
          <p className="text-muted-foreground mt-1">
            Manage active projects, milestones, deliverables, and incoming client project requests.
          </p>
        </div>
        <div className="flex gap-2">
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
        </div>
      </div>

      <Suspense fallback={<ProjectsLoading />}>
        <ProjectsList />
      </Suspense>
    </div>
  );
}
