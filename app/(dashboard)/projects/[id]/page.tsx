import { Suspense } from "react";
import { notFound } from "next/navigation";
import { db } from "@/lib/db";
import {
  projects,
  projectBudgets,
  projectMilestones,
  projectDeliverables,
  projectCostEntries,
} from "@/lib/db/schema/projects";
import { eq, and, isNull } from "drizzle-orm";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Progress } from "@/components/ui/progress";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { MilestoneList } from "@/components/projects/milestone-list";
import { DeliverableList } from "@/components/projects/deliverable-list";
import { ProjectReviewPanel } from "@/components/projects/project-review-panel";
import { formatCurrency } from "@/lib/utils";
import {
  Calendar,
  Clock,
  Columns3,
  DollarSign,
  Edit,
  MessageCircleMore,
  MessageSquareText,
  Target,
  Timer,
  TrendingUp,
  Users,
} from "lucide-react";
import Link from "next/link";
import { format } from "date-fns";
import { createClient } from "@/lib/supabase/server";
import { isAdminUser } from "@/lib/rbac/check";
import { DeleteProjectButton } from "@/components/projects/delete-project-button";
import { ApplyTemplateButton } from "@/components/projects/apply-template-button";

async function getProject(id: string) {
  const [project] = await db
    .select()
    .from(projects)
    .where(and(eq(projects.id, id), isNull(projects.deletedAt)))
    .limit(1);

  if (!project) {
    return null;
  }

  const [budgets, milestones, deliverables, costEntries] = await Promise.all([
    db.select().from(projectBudgets).where(eq(projectBudgets.projectId, id)),
    db.select().from(projectMilestones).where(eq(projectMilestones.projectId, id)).orderBy(projectMilestones.sortOrder),
    db
      .select()
      .from(projectDeliverables)
      .where(eq(projectDeliverables.projectId, id))
      .orderBy(projectDeliverables.sortOrder),
    db
      .select()
      .from(projectCostEntries)
      .where(eq(projectCostEntries.projectId, id))
      .orderBy(projectCostEntries.entryDate),
  ]);

  return {
    ...project,
    budgets,
    milestones,
    deliverables,
    costEntries,
  };
}

const statusColors = {
  planning: "bg-blue-500",
  active: "bg-green-500",
  on_hold: "bg-yellow-500",
  completed: "bg-gray-500",
  cancelled: "bg-red-500",
};

const statusLabels = {
  planning: "Planning",
  active: "Active",
  on_hold: "On Hold",
  completed: "Completed",
  cancelled: "Cancelled",
};

function ProjectLoading() {
  return (
    <div className="container mx-auto py-8 px-4">
      <div className="h-64 rounded-lg border bg-muted/50 animate-pulse" />
    </div>
  );
}

async function ProjectDetails({ id }: { id: string }) {
  const project = await getProject(id);

  if (!project) {
    notFound();
  }

  const supabase = await createClient();
  const { data: { user } } = await supabase.auth.getUser();
  const [{ data: dbUser }, { data: roleRows }] = user
    ? await Promise.all([
        supabase.from("users").select("is_super_admin").eq("id", user.id).maybeSingle(),
        supabase.from("user_roles").select("role:roles(name)").eq("user_id", user.id),
      ])
    : [{ data: null }, { data: [] }];
  const isAdmin = user ? isAdminUser(user, dbUser, roleRows) : false;

  const budgetAmount = parseFloat(project.budgetAmount || "0");
  const spentAmount = parseFloat(project.spentAmount || "0");
  const budgetPercentage = budgetAmount > 0 ? (spentAmount / budgetAmount) * 100 : 0;
  const isOverBudget = budgetPercentage > 100;

  const totalBudget = project.budgets.reduce((sum, b) => sum + parseFloat(b.allocatedAmount ?? "0"), 0);
  const totalSpent = project.costEntries.reduce((sum, e) => sum + parseFloat(e.amount ?? "0"), 0);

  const completedMilestones = project.milestones.filter((m) => m.completedAt !== null).length;
  const completedDeliverables = project.deliverables.filter((d) => d.status === "completed").length;

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex justify-between items-start">
        <div>
          <div className="flex items-center gap-3 mb-2">
            <h1 className="text-3xl font-bold">{project.name}</h1>
            <Badge className={statusColors[project.status]}>{statusLabels[project.status]}</Badge>
          </div>
          <p className="text-muted-foreground">{project.description}</p>
        </div>
        <div className="flex gap-2">
          <ApplyTemplateButton projectId={project.id} projectName={project.name} />
          <Button variant="outline" size="sm">
            <Edit className="h-4 w-4 mr-2" />
            Edit
          </Button>
          {isAdmin && <DeleteProjectButton projectId={project.id} />}
        </div>
      </div>

      {/* Stats Cards */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <Card>
          <CardHeader className="pb-3">
            <CardTitle className="text-sm font-medium text-muted-foreground">Progress</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{project.progressPercent}%</div>
            <Progress value={project.progressPercent} className="mt-2" />
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="pb-3">
            <CardTitle className="text-sm font-medium text-muted-foreground">Budget</CardTitle>
          </CardHeader>
          <CardContent>
            <div className={`text-2xl font-bold ${isOverBudget ? "text-red-600" : ""}`}>
              {formatCurrency(totalSpent || spentAmount, project.currency)}
            </div>
            <div className="text-xs text-muted-foreground mt-1">
              of {formatCurrency(totalBudget || budgetAmount, project.currency)}
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="pb-3">
            <CardTitle className="text-sm font-medium text-muted-foreground">Milestones</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">
              {completedMilestones} / {project.milestones.length}
            </div>
            <div className="text-xs text-muted-foreground mt-1">Completed</div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="pb-3">
            <CardTitle className="text-sm font-medium text-muted-foreground">Deliverables</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">
              {completedDeliverables} / {project.deliverables.length}
            </div>
            <div className="text-xs text-muted-foreground mt-1">Completed</div>
          </CardContent>
        </Card>
      </div>

      {/* Main Content Tabs */}
      <Tabs defaultValue="overview" className="w-full">
        <TabsList>
          <TabsTrigger value="overview">Overview</TabsTrigger>
          <TabsTrigger value="milestones">Milestones ({project.milestones.length})</TabsTrigger>
          <TabsTrigger value="deliverables">Deliverables ({project.deliverables.length})</TabsTrigger>
          <TabsTrigger value="reviews">Reviews</TabsTrigger>
          <TabsTrigger value="team">Team</TabsTrigger>
        </TabsList>

        <TabsContent value="overview" className="mt-6 space-y-6">
          {/* Project Details */}
          <Card>
            <CardHeader>
              <CardTitle>Project Details</CardTitle>
            </CardHeader>
            <CardContent>
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                {project.startDate && (
                  <div className="flex items-center gap-2">
                    <Calendar className="h-4 w-4 text-muted-foreground" />
                    <div>
                      <div className="text-sm text-muted-foreground">Start Date</div>
                      <div className="font-medium">{format(new Date(project.startDate), "MMM dd, yyyy")}</div>
                    </div>
                  </div>
                )}
                {project.endDate && (
                  <div className="flex items-center gap-2">
                    <Target className="h-4 w-4 text-muted-foreground" />
                    <div>
                      <div className="text-sm text-muted-foreground">End Date</div>
                      <div className="font-medium">{format(new Date(project.endDate), "MMM dd, yyyy")}</div>
                    </div>
                  </div>
                )}
                {project.estimatedHours && (
                  <div className="flex items-center gap-2">
                    <Clock className="h-4 w-4 text-muted-foreground" />
                    <div>
                      <div className="text-sm text-muted-foreground">Estimated Hours</div>
                      <div className="font-medium">{project.estimatedHours} hrs</div>
                    </div>
                  </div>
                )}
                {project.actualHours && (
                  <div className="flex items-center gap-2">
                    <Clock className="h-4 w-4 text-muted-foreground" />
                    <div>
                      <div className="text-sm text-muted-foreground">Actual Hours</div>
                      <div className="font-medium">{project.actualHours} hrs</div>
                    </div>
                  </div>
                )}
              </div>

              {project.metadata?.tags && project.metadata.tags.length > 0 && (
                <div className="mt-4">
                  <div className="text-sm text-muted-foreground mb-2">Tags</div>
                  <div className="flex flex-wrap gap-2">
                    {project.metadata.tags.map((tag) => (
                      <Badge key={tag} variant="outline">
                        {tag}
                      </Badge>
                    ))}
                  </div>
                </div>
              )}
            </CardContent>
          </Card>

          {/* Quick Links */}
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <Button variant="outline" asChild className="h-auto py-4">
              <Link href={`/projects/${project.id}/timeline`}>
                <div className="flex flex-col items-center gap-2">
                  <TrendingUp className="h-6 w-6" />
                  <span>View Timeline</span>
                </div>
              </Link>
            </Button>
            <Button variant="outline" asChild className="h-auto py-4">
              <Link href={`/projects/${project.id}/budget`}>
                <div className="flex flex-col items-center gap-2">
                  <DollarSign className="h-6 w-6" />
                  <span>Budget Details</span>
                </div>
              </Link>
            </Button>
            <Button variant="outline" asChild className="h-auto py-4">
              <Link href={`/projects/${project.id}/messages`}>
                <div className="flex flex-col items-center gap-2">
                  <MessageSquareText className="h-6 w-6" />
                  <span>Messages</span>
                </div>
              </Link>
            </Button>
            <Button variant="outline" asChild className="h-auto py-4">
              <Link href={`/projects/${project.id}/feedback`}>
                <div className="flex flex-col items-center gap-2">
                  <MessageCircleMore className="h-6 w-6" />
                  <span>Feedback</span>
                </div>
              </Link>
            </Button>
            <Button variant="outline" asChild className="h-auto py-4">
              <Link href={`/time-tracking?projectId=${project.id}`}>
                <div className="flex flex-col items-center gap-2">
                  <Timer className="h-6 w-6" />
                  <span>Time Tracking</span>
                </div>
              </Link>
            </Button>
            <Button variant="outline" asChild className="h-auto py-4">
              <Link href={`/projects/${project.id}/tasks`}>
                <div className="flex flex-col items-center gap-2">
                  <Columns3 className="h-6 w-6" />
                  <span>Task Board</span>
                </div>
              </Link>
            </Button>
            <Button variant="outline" asChild className="h-auto py-4">
              <Link href={`/projects/${project.id}/team`}>
                <div className="flex flex-col items-center gap-2">
                  <Users className="h-6 w-6" />
                  <span>Team Members</span>
                </div>
              </Link>
            </Button>
          </div>
        </TabsContent>

        <TabsContent value="milestones" className="mt-6">
          <MilestoneList milestones={project.milestones} />
        </TabsContent>

        <TabsContent value="deliverables" className="mt-6">
          <DeliverableList deliverables={project.deliverables} />
        </TabsContent>

        <TabsContent value="reviews" className="mt-6">
          <ProjectReviewPanel projectId={project.id} />
        </TabsContent>

        <TabsContent value="team" className="mt-6">
          <Card>
            <CardHeader>
              <CardTitle>Team Members</CardTitle>
              <CardDescription>People working on this project</CardDescription>
            </CardHeader>
            <CardContent>
              {project.teamMembers && project.teamMembers.length > 0 ? (
                <div className="space-y-3">
                  {project.teamMembers.map((member) => (
                    <div key={member.userId} className="flex items-center justify-between p-3 rounded-lg border">
                      <div>
                        <div className="font-medium">{member.name}</div>
                        <div className="text-sm text-muted-foreground">{member.role}</div>
                      </div>
                      {member.hourlyRate && (
                        <div className="text-sm text-muted-foreground">
                          {formatCurrency(member.hourlyRate, project.currency)}/hr
                        </div>
                      )}
                    </div>
                  ))}
                </div>
              ) : (
                <div className="text-center py-8 text-muted-foreground">No team members assigned yet</div>
              )}
            </CardContent>
          </Card>
        </TabsContent>
      </Tabs>
    </div>
  );
}

export default function ProjectPage({ params }: { params: Promise<{ id: string }> }) {
  return (
    <div className="container mx-auto py-8 px-4">
      <Suspense fallback={<ProjectLoading />}>
        <AsyncProjectDetails params={params} />
      </Suspense>
    </div>
  );
}

async function AsyncProjectDetails({ params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;
  return <ProjectDetails id={id} />;
}
