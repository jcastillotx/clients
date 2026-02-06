import { Suspense } from "react";
import { notFound } from "next/navigation";
import { db } from "@/lib/db";
import { projects, projectMilestones, projectDeliverables } from "@/lib/db/schema/projects";
import { eq, and, isNull } from "drizzle-orm";
import { ProjectTimeline } from "@/components/projects/project-timeline";
import { Button } from "@/components/ui/button";
import { ArrowLeft } from "lucide-react";
import Link from "next/link";

async function getProjectTimeline(id: string) {
  const [project] = await db
    .select()
    .from(projects)
    .where(and(eq(projects.id, id), isNull(projects.deletedAt)))
    .limit(1);

  if (!project) {
    return null;
  }

  const milestones = await db
    .select()
    .from(projectMilestones)
    .where(eq(projectMilestones.projectId, id))
    .orderBy(projectMilestones.sortOrder);

  // Fetch deliverables for each milestone
  const milestonesWithDeliverables = await Promise.all(
    milestones.map(async (milestone) => {
      const deliverables = await db
        .select()
        .from(projectDeliverables)
        .where(eq(projectDeliverables.milestoneId, milestone.id))
        .orderBy(projectDeliverables.sortOrder);

      return {
        ...milestone,
        deliverables,
      };
    }),
  );

  return {
    project,
    milestones: milestonesWithDeliverables,
  };
}

function TimelineLoading() {
  return (
    <div className="container mx-auto py-8 px-4">
      <div className="h-96 rounded-lg border bg-muted/50 animate-pulse" />
    </div>
  );
}

async function TimelineContent({ id }: { id: string }) {
  const data = await getProjectTimeline(id);

  if (!data) {
    notFound();
  }

  return (
    <div className="space-y-6">
      <div className="flex items-center gap-4">
        <Button variant="ghost" size="sm" asChild>
          <Link href={`/projects/${id}`}>
            <ArrowLeft className="h-4 w-4 mr-2" />
            Back to Project
          </Link>
        </Button>
      </div>

      <div>
        <h1 className="text-3xl font-bold">{data.project.name}</h1>
        <p className="text-muted-foreground mt-1">Project Timeline</p>
      </div>

      <ProjectTimeline milestones={data.milestones} />
    </div>
  );
}

export default function ProjectTimelinePage({ params }: { params: Promise<{ id: string }> }) {
  return (
    <div className="container mx-auto py-8 px-4">
      <Suspense fallback={<TimelineLoading />}>
        <AsyncTimelineContent params={params} />
      </Suspense>
    </div>
  );
}

async function AsyncTimelineContent({ params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;
  return <TimelineContent id={id} />;
}
